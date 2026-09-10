<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\GstRate;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TransportCompany;
use App\Models\VehicleType;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();
        $from = $request->string('from_date')->toString() ?: $today;
        $to = $request->string('to_date')->toString() ?: $today;

        if (! $this->validDateRange($from, $to)) {
            $from = $today;
            $to = $today;
        }

        return view('vouchers.index', [
            'initialRange' => $this->rangePayload($from, $to),
        ]);
    }

    public function range(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);

        return response()->json([
            'range' => $this->rangePayload($validated['from_date'], $validated['to_date']),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'rows' => ['array'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.transport_company_id' => ['required', 'integer', 'exists:transport_companies,id'],
            'rows.*.lr_date' => ['required', 'date_format:Y-m-d'],
            'rows.*.lr_no' => ['nullable', 'string', 'max:100'],
            'rows.*.vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'rows.*.lorry_no' => ['nullable', 'string', 'max:100'],
            'rows.*.so_ref_no' => ['nullable', 'string', 'max:150'],
            'rows.*.from_place' => ['nullable', 'string', 'max:150'],
            'rows.*.to_place' => ['nullable', 'string', 'max:150'],
            'rows.*.supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'rows.*.supplier_freight' => ['nullable', 'numeric', 'min:0'],
            'rows.*.supplier_advance' => ['nullable', 'numeric', 'min:0'],
            'rows.*.customer_id' => ['required', 'integer', 'exists:customers,id'],
            'rows.*.customer_freight' => ['nullable', 'numeric', 'min:0'],
            'rows.*.hamali_loading' => ['nullable', 'numeric', 'min:0'],
            'rows.*.hamali_unloading' => ['nullable', 'numeric', 'min:0'],
            'rows.*.other_charges' => ['nullable', 'numeric', 'min:0'],
            'rows.*.gst_rate_id' => ['nullable', 'integer', 'exists:gst_rates,id'],
            'rows.*.remarks' => ['nullable', 'string', 'max:2000'],
            'deleted_ids' => ['array'],
            'deleted_ids.*' => ['integer', 'exists:vouchers,id'],
        ]);

        foreach ($validated['rows'] ?? [] as $index => $row) {
            $rowDate = (string) ($row['lr_date'] ?? '');
            if ($rowDate < $validated['from_date'] || $rowDate > $validated['to_date']) {
                throw ValidationException::withMessages([
                    'rows.'. $index .'.lr_date' => 'LR Date must be within the selected From Date and To Date range.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $request): void {
            $zeroRateId = GstRate::query()->where('rate', 0)->value('id');
            $gstRates = GstRate::query()->pluck('rate', 'id');

            $existingIds = collect($validated['rows'] ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id);
            $existingRows = Voucher::query()->whereIn('id', $existingIds)->lockForUpdate()->get()->keyBy('id');

            $invalidIds = $existingIds->diff($existingRows->keys());
            if ($invalidIds->isNotEmpty()) {
                throw ValidationException::withMessages(['rows' => 'One or more voucher rows no longer exist. Reload and try again.']);
            }

            $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id);
            if ($deletedIds->isNotEmpty()) {
                Voucher::query()->whereIn('id', $deletedIds)->delete();
            }

            foreach ($validated['rows'] ?? [] as $row) {
                $customerFreight = $this->numericValue($row['customer_freight'] ?? null);
                $gstRateId = $row['gst_rate_id'] ?? $zeroRateId;
                $gstRate = (float) ($gstRates[$gstRateId] ?? 0);
                $gstAmount = round($customerFreight * $gstRate / 100, 2);

                $payload = [
                    'voucher_day_id' => null,
                    'transport_company_id' => $row['transport_company_id'],
                    'lr_date' => $row['lr_date'],
                    'lr_no' => $this->nullableText($row['lr_no'] ?? null),
                    'vehicle_type_id' => $row['vehicle_type_id'] ?? null,
                    'lorry_no' => $this->nullableText($row['lorry_no'] ?? null),
                    'so_ref_no' => $this->nullableText($row['so_ref_no'] ?? null),
                    'from_place' => $this->nullableText($row['from_place'] ?? null),
                    'to_place' => $this->nullableText($row['to_place'] ?? null),
                    'supplier_id' => $row['supplier_id'] ?? null,
                    'supplier_freight' => $this->numericValue($row['supplier_freight'] ?? null),
                    'supplier_advance' => $this->numericValue($row['supplier_advance'] ?? null),
                    'customer_id' => $row['customer_id'],
                    'customer_freight' => $customerFreight,
                    'hamali_loading' => $this->numericValue($row['hamali_loading'] ?? null),
                    'hamali_unloading' => $this->numericValue($row['hamali_unloading'] ?? null),
                    'other_charges' => $this->numericValue($row['other_charges'] ?? null),
                    'gst_rate_id' => $gstRateId,
                    'gst' => $gstAmount,
                    'remarks' => $this->nullableText($row['remarks'] ?? null),
                ];

                if (! empty($row['id'])) {
                    $voucher = $existingRows->get((int) $row['id']);
                    if ($voucher) {
                        if (! $voucher->bill_no) {
                            $payload['bill_no'] = $this->billNumber((int) $voucher->sr_no);
                        }
                        $voucher->update($payload);
                    }
                    continue;
                }

                $nextSr = ((int) Voucher::query()->lockForUpdate()->max('sr_no')) + 1;
                $payload['sr_no'] = $nextSr;
                $payload['bill_no'] = $this->billNumber($nextSr);
                $payload['created_by'] = $request->user()?->id;
                Voucher::query()->create($payload);
            }
        });

        return response()->json([
            'message' => 'Voucher entries saved successfully.',
            'range' => $this->rangePayload($validated['from_date'], $validated['to_date']),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['companies', 'customers', 'suppliers', 'vehicle-types', 'gst-rates'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = match ($validated['type']) {
            'companies' => TransportCompany::query()->where('is_active', true),
            'customers' => Customer::query()->where('is_active', true),
            'suppliers' => Supplier::query()->where('is_active', true),
            'vehicle-types' => VehicleType::query()->where('is_active', true),
            'gst-rates' => GstRate::query()->where('is_active', true),
        };

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search, $validated) {
                $q->where('name', 'like', '%'.$search.'%');
                if (in_array($validated['type'], ['customers', 'suppliers'], true)) {
                    $q->orWhere('code', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%');
                }
            });
        }

        if ($validated['type'] === 'gst-rates') {
            $items = $query->orderBy('rate')->limit(100)->get()->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'subtitle' => rtrim(rtrim(number_format((float) $item->rate, 2, '.', ''), '0'), '.').'%',
                'rate' => (float) $item->rate,
            ]);
        } else {
            $items = $query->orderBy('name')->limit(100)->get()->map(function ($item) use ($validated) {
                $subtitle = null;
                if (in_array($validated['type'], ['customers', 'suppliers'], true)) {
                    $subtitle = implode(' · ', array_filter([$item->code, $item->phone]));
                }
                return ['id' => $item->id, 'name' => $item->name, 'subtitle' => $subtitle];
            });
        }

        return response()->json(['items' => $items]);
    }

    public function payments(Voucher $voucher, string $type): JsonResponse
    {
        abort_unless(in_array($type, ['supplier', 'customer'], true), 404);
        $items = $type === 'supplier'
            ? $voucher->supplierPayments()->orderBy('payment_date')->orderBy('id')->get()
            : $voucher->customerPayments()->orderBy('payment_date')->orderBy('id')->get();

        return response()->json([
            'voucher' => $this->serializeVoucher($voucher->fresh([
                'transportCompany', 'vehicleType', 'supplier', 'customer', 'gstRate',
            ])->loadSum('supplierPayments as supplier_payment_total', 'amount')
              ->loadSum('customerPayments as customer_paid_total', 'amount')),
            'payments' => $items->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
                'amount' => (float) $payment->amount,
                'payment_mode' => $payment->payment_mode,
                'reference' => $payment->reference,
                'remarks' => $payment->remarks,
            ]),
        ]);
    }

    public function storePayment(Request $request, Voucher $voucher, string $type): JsonResponse
    {
        abort_unless(in_array($type, ['supplier', 'customer'], true), 404);

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['nullable', Rule::in(['Cash', 'RTGS', 'NEFT', 'Bank Transfer', 'UPI', 'Cheque', 'Other'])],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (float) $validated['amount'];

        if ($type === 'supplier') {
            $already = (float) $voucher->supplierPayments()->sum('amount');
            $balance = max(0, (float) $voucher->supplier_freight - (float) $voucher->supplier_advance - $already);
            if ($amount > $balance + 0.0001) {
                return response()->json(['message' => 'Payment cannot be greater than supplier balance ₹'.number_format($balance, 2).'.'], 422);
            }
            $voucher->supplierPayments()->create($validated + ['created_by' => $request->user()?->id]);
        } else {
            $already = (float) $voucher->customerPayments()->sum('amount');
            $receivable = (float) $voucher->customer_freight
                + (float) $voucher->hamali_loading
                + (float) $voucher->hamali_unloading
                + (float) $voucher->other_charges
                + (float) $voucher->gst;
            $balance = max(0, $receivable - $already);
            if ($amount > $balance + 0.0001) {
                return response()->json(['message' => 'Payment cannot be greater than customer balance ₹'.number_format($balance, 2).'.'], 422);
            }
            $voucher->customerPayments()->create($validated + ['created_by' => $request->user()?->id]);
        }

        return $this->payments($voucher->fresh(), $type);
    }

    public function deletePayment(Voucher $voucher, string $type, int $payment): JsonResponse
    {
        abort_unless(in_array($type, ['supplier', 'customer'], true), 404);
        $model = $type === 'supplier'
            ? SupplierPayment::query()->where('voucher_id', $voucher->id)->findOrFail($payment)
            : CustomerPayment::query()->where('voucher_id', $voucher->id)->findOrFail($payment);
        $model->delete();
        return $this->payments($voucher->fresh(), $type);
    }

    private function rangePayload(string $from, string $to): array
    {
        $zero = GstRate::query()->where('rate', 0)->first();

        return [
            'from_date' => $from,
            'to_date' => $to,
            'default_gst_rate_id' => $zero?->id,
            'default_gst_rate_name' => $zero?->name ?? '0%',
            'default_gst_rate' => (float) ($zero?->rate ?? 0),
            'rows' => Voucher::query()
                ->whereBetween('lr_date', [$from, $to])
                ->with(['transportCompany', 'vehicleType', 'supplier', 'customer', 'gstRate'])
                ->withSum('supplierPayments as supplier_payment_total', 'amount')
                ->withSum('customerPayments as customer_paid_total', 'amount')
                ->orderBy('lr_date')
                ->orderBy('sr_no')
                ->get()
                ->map(fn (Voucher $voucher) => $this->serializeVoucher($voucher))
                ->values()
                ->all(),
        ];
    }

    private function serializeVoucher(Voucher $voucher): array
    {
        $supplierPayments = (float) ($voucher->supplier_payment_total ?? 0);
        $customerPaid = (float) ($voucher->customer_paid_total ?? 0);
        $supplierFreight = (float) $voucher->supplier_freight;
        $supplierAdvance = (float) $voucher->supplier_advance;
        $customerFreight = (float) $voucher->customer_freight;
        $hamaliLoading = (float) $voucher->hamali_loading;
        $hamaliUnloading = (float) $voucher->hamali_unloading;
        $otherCharges = (float) $voucher->other_charges;
        $gst = (float) $voucher->gst;
        $customerAmount = $customerFreight + $hamaliLoading + $hamaliUnloading + $otherCharges;
        $invoiceTotal = $customerAmount + $gst;

        return [
            'id' => $voucher->id,
            'sr_no' => $voucher->sr_no,
            'transport_company_id' => $voucher->transport_company_id,
            'transport_company_name' => $voucher->transportCompany?->name,
            'lr_date' => optional($voucher->lr_date)->format('Y-m-d'),
            'lr_no' => $voucher->lr_no,
            'vehicle_type_id' => $voucher->vehicle_type_id,
            'vehicle_type_name' => $voucher->vehicleType?->name,
            'lorry_no' => $voucher->lorry_no,
            'so_ref_no' => $voucher->so_ref_no,
            'from_place' => $voucher->from_place,
            'to_place' => $voucher->to_place,
            'supplier_id' => $voucher->supplier_id,
            'supplier_name' => $voucher->supplier?->name,
            'supplier_freight' => $supplierFreight,
            'supplier_advance' => $supplierAdvance,
            'supplier_balance' => max(0, $supplierFreight - $supplierAdvance - $supplierPayments),
            'supplier_payment' => $supplierPayments,
            'customer_id' => $voucher->customer_id,
            'customer_name' => $voucher->customer?->name,
            'customer_freight' => $customerFreight,
            'customer_paid' => $customerPaid,
            'customer_balance' => max(0, $invoiceTotal - $customerPaid),
            'hamali_loading' => $hamaliLoading,
            'hamali_unloading' => $hamaliUnloading,
            'other_charges' => $otherCharges,
            'customer_amount' => $customerAmount,
            'profit' => $customerFreight - $supplierFreight,
            'bill_no' => $voucher->bill_no ?: $this->billNumber((int) $voucher->sr_no),
            'gst_rate_id' => $voucher->gst_rate_id,
            'gst_rate_name' => $voucher->gstRate?->name ?: '0%',
            'gst_rate' => (float) ($voucher->gstRate?->rate ?? 0),
            'gst' => $gst,
            'invoice_total' => $invoiceTotal,
            'remarks' => $voucher->remarks,
        ];
    }

    private function billNumber(int $srNo): string
    {
        return 'BILL-'.str_pad((string) $srNo, 6, '0', STR_PAD_LEFT);
    }

    private function validDateRange(string $from, string $to): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)
            && $from <= $to;
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function numericValue(mixed $value): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }
        return (float) $value;
    }
}
