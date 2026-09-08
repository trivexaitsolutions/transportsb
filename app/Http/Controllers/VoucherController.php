<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TransportCompany;
use App\Models\VehicleType;
use App\Models\Voucher;
use App\Models\VoucherDay;
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
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        $selectedDay = VoucherDay::query()
            ->whereDate('entry_date', $date)
            ->first();

        $initialDay = $selectedDay
            ? $this->dayPayload($selectedDay)
            : $this->newDayPayload($this->nextDayNumber(), $date);

        return view('vouchers.index', [
            'initialDay' => $initialDay,
        ]);
    }

    public function day(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'day_number' => ['required', 'integer', 'min:1', 'max:99999'],
        ]);

        $dayNumber = (int) $validated['day_number'];

        $day = VoucherDay::query()
            ->where('day_number', $dayNumber)
            ->first();

        if ($day) {
            return response()->json([
                'day' => $this->dayPayload($day),
            ]);
        }

        $nextDayNumber = $this->nextDayNumber();

        if ($dayNumber !== $nextDayNumber) {
            throw ValidationException::withMessages([
                'day_number' => sprintf(
                    'Voucher Number %05d does not exist. The next new Voucher Number is %05d.',
                    $dayNumber,
                    $nextDayNumber
                ),
            ]);
        }

        return response()->json([
            'day' => $this->newDayPayload(
                $nextDayNumber,
                now()->toDateString()
            ),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'day_number' => ['required', 'integer', 'min:1', 'max:99999'],
            'entry_date' => ['required', 'date'],
            'rows' => ['array'],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.transport_company_id' => ['required', 'integer', 'exists:transport_companies,id'],
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
            'rows.*.bill_no' => ['nullable', 'string', 'max:100'],
            'rows.*.gst' => ['nullable', 'numeric', 'min:0'],
            'rows.*.remarks' => ['nullable', 'string', 'max:2000'],
            'deleted_ids' => ['array'],
            'deleted_ids.*' => ['integer', 'exists:vouchers,id'],
        ]);

        $day = DB::transaction(function () use ($validated, $request): VoucherDay {
            $dayNumber = (int) $validated['day_number'];

            $day = VoucherDay::query()
                ->where('day_number', $dayNumber)
                ->lockForUpdate()
                ->first();

            // Voucher Number owns its date. Existing voucher days always keep
            // their stored date; a brand-new voucher day always uses today.
            // The browser only displays this value and cannot change it.
            $entryDate = $day
                ? $day->entry_date->toDateString()
                : now()->toDateString();

            if (! $day) {
                if (VoucherDay::query()->whereDate('entry_date', $entryDate)->exists()) {
                    throw ValidationException::withMessages([
                        'day_number' => 'A Voucher Number already exists for today.',
                    ]);
                }

                $lastDay = VoucherDay::query()
                    ->orderByDesc('day_number')
                    ->lockForUpdate()
                    ->first();

                $expectedDayNumber = $lastDay
                    ? ((int) $lastDay->day_number + 1)
                    : 1;

                if ($dayNumber !== $expectedDayNumber) {
                    throw ValidationException::withMessages([
                        'day_number' => sprintf(
                            'The next new Voucher Number must be %05d.',
                            $expectedDayNumber
                        ),
                    ]);
                }

                $day = VoucherDay::query()->create([
                    'day_number' => $expectedDayNumber,
                    'entry_date' => $entryDate,
                    'created_by' => $request->user()?->id,
                ]);
            }

            // Keep the legacy lr_date column synchronized because existing reports,
            // ledgers and PDFs intentionally continue to read that column.
            Voucher::query()
                ->where('voucher_day_id', $day->id)
                ->update(['lr_date' => $entryDate]);

            $existingRows = Voucher::query()
                ->where('voucher_day_id', $day->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $submittedExistingIds = collect($validated['rows'] ?? [])
                ->pluck('id')
                ->filter()
                ->map(fn ($id): int => (int) $id);

            $invalidSubmittedIds = $submittedExistingIds
                ->diff($existingRows->keys());

            if ($invalidSubmittedIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'rows' => 'One or more voucher rows do not belong to the selected Voucher Number.',
                ]);
            }

            $deletedIds = collect($validated['deleted_ids'] ?? [])
                ->map(fn ($id): int => (int) $id);

            $invalidDeletedIds = $deletedIds
                ->diff($existingRows->keys());

            if ($invalidDeletedIds->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'deleted_ids' => 'One or more deleted voucher rows do not belong to the selected Voucher Number.',
                ]);
            }

            if ($deletedIds->isNotEmpty()) {
                Voucher::query()
                    ->where('voucher_day_id', $day->id)
                    ->whereIn('id', $deletedIds)
                    ->delete();
            }

            foreach ($validated['rows'] ?? [] as $row) {
                $payload = [
                    'voucher_day_id' => $day->id,
                    'transport_company_id' => $row['transport_company_id'],
                    'lr_date' => $entryDate,
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
                    'customer_freight' => $this->numericValue($row['customer_freight'] ?? null),
                    'hamali_loading' => $this->numericValue($row['hamali_loading'] ?? null),
                    'hamali_unloading' => $this->numericValue($row['hamali_unloading'] ?? null),
                    'bill_no' => $this->nullableText($row['bill_no'] ?? null),
                    'gst' => $this->numericValue($row['gst'] ?? null),
                    'remarks' => $this->nullableText($row['remarks'] ?? null),
                ];

                if (! empty($row['id'])) {
                    $voucher = $existingRows->get((int) $row['id']);
                    $voucher?->update($payload);
                    continue;
                }

                $nextSr = ((int) Voucher::query()->lockForUpdate()->max('sr_no')) + 1;
                $payload['sr_no'] = $nextSr;
                $payload['created_by'] = $request->user()?->id;
                Voucher::query()->create($payload);
            }

            return $day->fresh();
        });

        return response()->json([
            'message' => 'Voucher entries saved successfully.',
            'day' => $this->dayPayload($day),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['companies', 'customers', 'suppliers', 'vehicle-types'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $query = match ($validated['type']) {
            'companies' => TransportCompany::query()->where('is_active', true),
            'customers' => Customer::query()->where('is_active', true),
            'suppliers' => Supplier::query()->where('is_active', true),
            'vehicle-types' => VehicleType::query()->where('is_active', true),
        };

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search, $validated) {
                $q->where('name', 'like', '%'.$search.'%');

                if (in_array($validated['type'], ['customers', 'suppliers'], true)) {
                    $q->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                }
            });
        }

        $items = $query->orderBy('name')->limit(100)->get()->map(function ($item) use ($validated) {
            $subtitle = null;

            if (in_array($validated['type'], ['customers', 'suppliers'], true)) {
                $parts = array_filter([$item->code, $item->phone]);
                $subtitle = implode(' · ', $parts);
            }

            return [
                'id' => $item->id,
                'name' => $item->name,
                'subtitle' => $subtitle,
            ];
        });

        return response()->json(['items' => $items]);
    }

    public function payments(Voucher $voucher, string $type): JsonResponse
    {
        abort_unless(in_array($type, ['supplier', 'customer'], true), 404);

        if ($type === 'supplier') {
            $items = $voucher->supplierPayments()->orderBy('payment_date')->orderBy('id')->get();
        } else {
            $items = $voucher->customerPayments()->orderBy('payment_date')->orderBy('id')->get();
        }

        return response()->json([
            'voucher' => $this->serializeVoucher($voucher->fresh([
                'transportCompany', 'vehicleType', 'supplier', 'customer',
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
                return response()->json([
                    'message' => 'Payment cannot be greater than supplier balance ₹'.number_format($balance, 2).'.',
                ], 422);
            }

            $voucher->supplierPayments()->create($validated + ['created_by' => $request->user()?->id]);
        } else {
            $already = (float) $voucher->customerPayments()->sum('amount');
            $receivable = (float) $voucher->customer_freight;
            $balance = max(0, $receivable - $already);

            if ($amount > $balance + 0.0001) {
                return response()->json([
                    'message' => 'Payment cannot be greater than customer balance ₹'.number_format($balance, 2).'.',
                ], 422);
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

    private function dayPayload(VoucherDay $day): array
    {
        return [
            'exists' => true,
            'id' => $day->id,
            'day_number' => (int) $day->day_number,
            'day_number_formatted' => sprintf('%05d', $day->day_number),
            'entry_date' => optional($day->entry_date)->format('Y-m-d'),
            'rows' => $this->vouchersForDay($day),
        ];
    }

    private function newDayPayload(int $dayNumber, string $entryDate): array
    {
        return [
            'exists' => false,
            'id' => null,
            'day_number' => $dayNumber,
            'day_number_formatted' => sprintf('%05d', $dayNumber),
            'entry_date' => $entryDate,
            'rows' => [],
        ];
    }

    private function nextDayNumber(): int
    {
        return ((int) VoucherDay::query()->max('day_number')) + 1;
    }

    private function vouchersForDay(VoucherDay $day): array
    {
        return Voucher::query()
            ->where(function (Builder $query) use ($day) {
                $query->where('voucher_day_id', $day->id)
                    ->orWhere(function (Builder $fallback) use ($day) {
                        $fallback->whereNull('voucher_day_id')
                            ->whereDate('lr_date', $day->entry_date);
                    });
            })
            ->with(['transportCompany', 'vehicleType', 'supplier', 'customer'])
            ->withSum('supplierPayments as supplier_payment_total', 'amount')
            ->withSum('customerPayments as customer_paid_total', 'amount')
            ->orderBy('sr_no')
            ->get()
            ->map(fn (Voucher $voucher) => $this->serializeVoucher($voucher))
            ->values()
            ->all();
    }

    private function serializeVoucher(Voucher $voucher): array
    {
        $supplierPayments = (float) ($voucher->supplier_payment_total ?? 0);
        $customerPaid = (float) ($voucher->customer_paid_total ?? 0);
        $supplierFreight = (float) $voucher->supplier_freight;
        $supplierAdvance = (float) $voucher->supplier_advance;
        $customerFreight = (float) $voucher->customer_freight;
        $gst = (float) $voucher->gst;

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
            'customer_balance' => max(0, $customerFreight - $customerPaid),
            'hamali_loading' => (float) $voucher->hamali_loading,
            'hamali_unloading' => (float) $voucher->hamali_unloading,
            'profit' => $customerFreight - $supplierFreight,
            'bill_no' => $voucher->bill_no,
            'gst' => $gst,
            'remarks' => $voucher->remarks,
        ];
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
