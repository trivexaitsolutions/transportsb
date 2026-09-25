<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SupplierPayment;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $today = now()->toDateString();
        $from = $request->string('from_date')->toString() ?: $today;
        $to = $request->string('to_date')->toString() ?: $today;
        if (!$this->validDate($from) || !$this->validDate($to) || $to < $from) {
            $from = $today;
            $to = $today;
        }

        $initialOrder = null;
        $initialOrderId = $request->integer('sales_order_id') ?: null;
        if ($initialOrderId) {
            $initialOrder = SalesOrder::query()
                ->with('customer:id,name,code')
                ->find($initialOrderId);
        }

        $entries = Voucher::query()
            ->whereBetween('lr_date', [$from, $to])
            ->with([
                'salesOrder:id,so_number,customer_id,from_location,to_location,per_trip_cost,trips_quantity',
                'salesOrder.customer:id,name,code',
                'company:id,name,gst_no,address',
                'vehicleType:id,name',
                'supplier:id,name,code',
            ])
            ->withSum('supplierPayments as supplier_payments_total', 'amount')
            ->withSum('supplierPartyPayments as supplier_party_payments_total', 'amount')
            ->orderBy('lr_date')->orderBy('id')->get();

        $rows = $entries->map(fn (Voucher $voucher) => $this->rowPayload($voucher))->values();

        if ($request->expectsJson()) {
            return response()->json([
                'from_date' => $from,
                'to_date' => $to,
                'rows' => $rows,
            ]);
        }

        return view('sale.vouchers.index', [
            'fromDate' => $from,
            'toDate' => $to,
            'rows' => $rows,
            'initialOrder' => $initialOrder ? [
                'id' => $initialOrder->id,
                'so_number' => $initialOrder->so_number,
                'customer_id' => $initialOrder->customer_id,
                'customer_name' => $initialOrder->customer?->name,
                'from_location' => $initialOrder->from_location,
                'to_location' => $initialOrder->to_location,
                'per_trip_cost' => (float) $initialOrder->per_trip_cost,
            ] : null,
        ]);
    }

    public function saveAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'rows' => ['array'],
            'rows.*.id' => ['nullable', 'integer', 'distinct', 'exists:vouchers,id'],
            'rows.*.lr_date' => ['required', 'date_format:Y-m-d'],
            'rows.*.sales_order_id' => ['required', 'integer', 'exists:sales_orders,id'],
            'rows.*.company_id' => ['nullable', 'integer', 'exists:companies,id'],
            // Temporary request alias for an old compiled Voucher view. It also points to companies.
            'rows.*.transport_name_id' => ['nullable', 'integer', 'exists:companies,id'],
            'rows.*.lr_no' => ['nullable', 'string', 'max:100'],
            'rows.*.vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'rows.*.lorry_number' => ['nullable', 'string', 'max:100'],
            'rows.*.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'rows.*.supplier_freight' => ['nullable', 'numeric', 'min:0'],
            'rows.*.advance_paid' => ['nullable', 'numeric', 'min:0'],
            'rows.*.advance_mode' => ['nullable', 'string', 'max:50', 'in:Cash,NEFT,RTGS,UPI,Cheque,Bank Transfer,Other'],
            'rows.*.hamali_loading' => ['nullable', 'numeric', 'min:0'],
            'rows.*.hamali_unloading' => ['nullable', 'numeric', 'min:0'],
            'rows.*.other_charges' => ['nullable', 'numeric', 'min:0'],
            'rows.*.remarks' => ['nullable', 'string', 'max:2000'],
            'rows.*.description' => ['nullable', 'string', 'max:4000'],
        ]);

        foreach ($validated['rows'] ?? [] as $index => $row) {
            $companyId = (int) ($row['company_id'] ?? $row['transport_name_id'] ?? 0);
            if ($companyId <= 0) {
                throw ValidationException::withMessages([
                    'rows.'.$index.'.company_id' => 'Select Company.',
                ]);
            }
            $validated['rows'][$index]['company_id'] = $companyId;

            if ($row['lr_date'] < $validated['from_date'] || $row['lr_date'] > $validated['to_date']) {
                throw ValidationException::withMessages([
                    'rows.'.$index.'.lr_date' => 'LR Date must be within the selected From Date and To Date.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $request): void {
            foreach ($validated['rows'] ?? [] as $index => $row) {
                $voucher = !empty($row['id']) ? Voucher::query()->lockForUpdate()->findOrFail((int) $row['id']) : null;
                $order = SalesOrder::query()->lockForUpdate()->findOrFail((int) $row['sales_order_id']);

                $usedOtherTrips = Voucher::query()
                    ->where('sales_order_id', $order->id)
                    ->when($voucher, fn ($q) => $q->where('id', '!=', $voucher->id))
                    ->count();

                if (!$order->is_active && (!$voucher || $voucher->sales_order_id !== $order->id)) {
                    throw ValidationException::withMessages(['rows.'.$index.'.sales_order_id' => 'Selected SO is inactive.']);
                }

                if ($usedOtherTrips >= (int) $order->trips_quantity) {
                    throw ValidationException::withMessages([
                        'rows.'.$index.'.sales_order_id' => 'Selected SO has already used all '.$order->trips_quantity.' truck/trip slots.',
                    ]);
                }

                $supplierFreight = $this->num($row['supplier_freight'] ?? null);
                $advance = $this->num($row['advance_paid'] ?? null);
                if ($advance > $supplierFreight) {
                    throw ValidationException::withMessages(['rows.'.$index.'.advance_paid' => 'Advance cannot exceed Supplier Freight.']);
                }
                if ($advance > 0 && empty($row['advance_mode'])) {
                    throw ValidationException::withMessages(['rows.'.$index.'.advance_mode' => 'Select Advance Mode when an advance amount is entered.']);
                }

                if ($voucher) {
                    $paid = (float) $voucher->supplierPayments()->sum('amount')
                        + (float) $voucher->supplierPartyPayments()->sum('amount');
                    if ($advance + $paid > $supplierFreight) {
                        throw ValidationException::withMessages(['rows.'.$index.'.supplier_freight' => 'Supplier Freight cannot be lower than Advance + Supplier Payments already recorded.']);
                    }
                }

                $payload = [
                    'lr_date' => $row['lr_date'],
                    'sales_order_id' => $order->id,
                    'company_id' => (int) $row['company_id'],
                    'lr_no' => $this->text($row['lr_no'] ?? null),
                    'vehicle_type_id' => $row['vehicle_type_id'] ?? null,
                    'lorry_number' => (($lorry = $this->text($row['lorry_number'] ?? null)) !== null ? strtoupper($lorry) : null),
                    'supplier_id' => $row['supplier_id'] ?? null,
                    'supplier_freight' => $supplierFreight,
                    'advance_paid' => $advance,
                    'advance_mode' => $advance > 0 ? $this->text($row['advance_mode'] ?? null) : null,
                    'hamali_loading' => $this->num($row['hamali_loading'] ?? null),
                    'hamali_unloading' => $this->num($row['hamali_unloading'] ?? null),
                    'other_charges' => $this->num($row['other_charges'] ?? null),
                    'remarks' => $this->text($row['remarks'] ?? null),
                    'description' => $this->text($row['description'] ?? null),
                ];

                if ($voucher) {
                    $voucher->fill($payload);
                    if ($voucher->invoiceItem()->exists() && $voucher->isDirty([
                        'lr_date', 'sales_order_id', 'company_id', 'lr_no', 'vehicle_type_id',
                        'lorry_number', 'supplier_id', 'other_charges', 'remarks', 'description',
                    ])) {
                        throw ValidationException::withMessages([
                            'rows.'.$index => 'This trip is already included in a customer bill. Edit the bill acknowledgement instead of changing its LR details.',
                        ]);
                    }
                    $voucher->update($payload);
                } else {
                    $payload['sr_no'] = ((int) Voucher::query()->lockForUpdate()->max('sr_no')) + 1;
                    $payload['created_by'] = $request->user()?->id;
                    Voucher::query()->create($payload);
                }
            }
        });

        return response()->json([
            'message' => 'Voucher entries saved successfully.',
            'redirect' => route('sale.vouchers.index', ['from_date' => $validated['from_date'], 'to_date' => $validated['to_date']]),
        ]);
    }

    public function destroy(Voucher $voucher): JsonResponse
    {
        if ($voucher->invoiceItem()->exists()) {
            return response()->json([
                'message' => 'This trip is already included in a customer bill and cannot be deleted.',
            ], 422);
        }

        if ($voucher->supplierPayments()->exists() || $voucher->supplierPartyPayments()->exists()) {
            return response()->json([
                'message' => 'Supplier payments exist for this trip. Delete those payments first.',
            ], 422);
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher trip deleted.']);
    }

    public function payments(Voucher $voucher): JsonResponse
    {
        $items = $voucher->supplierPayments()->orderBy('payment_date')->orderBy('id')->get()->map(fn (SupplierPayment $payment) => [
            'id' => $payment->id,
            'payment_date' => $payment->payment_date?->format('Y-m-d'),
            'amount' => (float) $payment->amount,
            'payment_mode' => $payment->payment_mode,
            'reference' => $payment->reference,
            'remarks' => $payment->remarks,
        ]);

        return response()->json($this->paymentPayload($voucher, $items));
    }

    public function storePayment(Request $request, Voucher $voucher): JsonResponse
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($voucher, $data, $request): void {
            $locked = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);
            $alreadyPaid = (float) $locked->supplierPayments()->sum('amount')
                + (float) $locked->supplierPartyPayments()->sum('amount');
            $balance = max(0, (float) $locked->supplier_freight - (float) $locked->advance_paid - $alreadyPaid);
            if ((float) $data['amount'] > $balance + 0.0001) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed current Supplier Balance of ₹'.number_format($balance, 2).'.']);
            }
            SupplierPayment::query()->create([
                'voucher_id' => $locked->id,
                'payment_date' => $data['payment_date'],
                'amount' => (float) $data['amount'],
                'payment_mode' => $this->text($data['payment_mode'] ?? null),
                'reference' => $this->text($data['reference'] ?? null),
                'remarks' => $this->text($data['remarks'] ?? null),
                'created_by' => $request->user()?->id,
            ]);
        });

        return $this->payments($voucher->fresh());
    }

    public function destroyPayment(Voucher $voucher, SupplierPayment $payment): JsonResponse
    {
        abort_unless($payment->voucher_id === $voucher->id, 404);
        $payment->delete();
        return $this->payments($voucher->fresh());
    }

    private function paymentPayload(Voucher $voucher, $items): array
    {
        $voucher->refresh();
        $paid = (float) $voucher->supplierPayments()->sum('amount')
            + (float) $voucher->supplierPartyPayments()->sum('amount');
        $freight = (float) $voucher->supplier_freight;
        $advance = (float) $voucher->advance_paid;
        return [
            'voucher_id' => $voucher->id,
            'supplier_freight' => $freight,
            'advance_paid' => $advance,
            'paid' => $paid,
            'balance' => max(0, $freight - $advance - $paid),
            'items' => $items,
        ];
    }

    private function rowPayload(Voucher $voucher): array
    {
        $paid = (float) ($voucher->supplier_payments_total ?? 0)
            + (float) ($voucher->supplier_party_payments_total ?? 0);
        $freight = (float) $voucher->supplier_freight;
        $advance = (float) $voucher->advance_paid;
        return [
            'id' => $voucher->id,
            'sr_no' => $voucher->sr_no,
            'lr_date' => $voucher->lr_date?->format('Y-m-d'),
            'sales_order_id' => $voucher->sales_order_id,
            'so_number' => $voucher->salesOrder?->so_number,
            'customer_name' => $voucher->salesOrder?->customer?->name,
            'from_location' => $voucher->salesOrder?->from_location,
            'to_location' => $voucher->salesOrder?->to_location,
            'per_trip_cost' => (float) ($voucher->salesOrder?->per_trip_cost ?? 0),
            'company_id' => $voucher->company_id,
            'company_name' => $voucher->company?->name,
            // Response aliases keep an old compiled view functional until its cache is cleared.
            'transport_name_id' => $voucher->company_id,
            'transport_name' => $voucher->company?->name,
            'lr_no' => $voucher->lr_no,
            'vehicle_type_id' => $voucher->vehicle_type_id,
            'vehicle_type_name' => $voucher->vehicleType?->name,
            'lorry_number' => $voucher->lorry_number,
            'supplier_id' => $voucher->supplier_id,
            'supplier_name' => $voucher->supplier?->name,
            'supplier_freight' => $freight,
            'advance_paid' => $advance,
            'advance_mode' => $voucher->advance_mode,
            'supplier_paid' => $paid,
            'supplier_balance' => max(0, $freight - $advance - $paid),
            'hamali_loading' => (float) $voucher->hamali_loading,
            'hamali_unloading' => (float) $voucher->hamali_unloading,
            'other_charges' => (float) $voucher->other_charges,
            'remarks' => $voucher->remarks,
            'description' => $voucher->description,
        ];
    }

    private function num(mixed $value): float
    {
        if ($value === '' || $value === null) return 0.0;
        return round((float) $value, 2);
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function validDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}
