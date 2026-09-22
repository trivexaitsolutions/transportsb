<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPartyPayment;
use App\Models\CustomerPayment;
use App\Models\InvoiceBatch;
use App\Models\Supplier;
use App\Models\SupplierPartyPayment;
use App\Models\SupplierPayment;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    public function supplierIndex(Request $request): View
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        $supplierId = $request->integer('supplier_id') ?: null;

        $query = SupplierPartyPayment::query()
            ->with(['supplier:id,code,name', 'voucher:id,lr_no,sr_no'])
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));

        $totalAmount = (float) (clone $query)->sum('amount');
        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(50)->withQueryString();
        $parties = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'opening_balance']);

        $supplierIds = $parties->pluck('id');
        $freightTotals = Voucher::query()
            ->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, COALESCE(SUM(supplier_freight), 0) as total')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');
        $advanceTotals = Voucher::query()
            ->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, COALESCE(SUM(advance_paid), 0) as total')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');
        $partyPaymentTotals = SupplierPartyPayment::query()
            ->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('supplier_id')
            ->pluck('total', 'supplier_id');
        $legacyPaymentTotals = SupplierPayment::query()
            ->join('vouchers', 'supplier_payments.voucher_id', '=', 'vouchers.id')
            ->whereIn('vouchers.supplier_id', $supplierIds)
            ->selectRaw('vouchers.supplier_id as supplier_id, COALESCE(SUM(supplier_payments.amount), 0) as total')
            ->groupBy('vouchers.supplier_id')
            ->pluck('total', 'supplier_id');

        $supplierOutstandings = [];
        $parties->each(function (Supplier $party) use ($freightTotals, $advanceTotals, $partyPaymentTotals, $legacyPaymentTotals, &$supplierOutstandings) {
            $balance = round(
                (float) $party->opening_balance
                + (float) ($freightTotals[$party->id] ?? 0)
                - (float) ($advanceTotals[$party->id] ?? 0)
                - (float) ($partyPaymentTotals[$party->id] ?? 0)
                - (float) ($legacyPaymentTotals[$party->id] ?? 0),
                2
            );

            $outstanding = max(0, $balance);
            $party->setAttribute('outstanding_amount', $outstanding);
            $supplierOutstandings[(string) $party->id] = $outstanding;
        });

        $pendingSupplierBills = Voucher::query()
            ->select([
                'vouchers.id',
                'vouchers.sr_no',
                'vouchers.lr_date',
                'vouchers.lr_no',
                'vouchers.supplier_id',
                'vouchers.supplier_freight',
                'vouchers.advance_paid',
            ])
            ->selectSub(
                SupplierPayment::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('voucher_id', 'vouchers.id'),
                'legacy_paid_amount'
            )
            ->selectSub(
                SupplierPartyPayment::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('voucher_id', 'vouchers.id')
                    ->where('payment_type', 'against_bill'),
                'allocated_paid_amount'
            )
            ->whereNotNull('supplier_id')
            ->where('supplier_freight', '>', 0)
            ->orderByDesc('lr_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Voucher $voucher) {
                $paid = round(
                    (float) $voucher->advance_paid
                    + (float) $voucher->legacy_paid_amount
                    + (float) $voucher->allocated_paid_amount,
                    2
                );
                $pending = max(0, round((float) $voucher->supplier_freight - $paid, 2));
                $reference = $voucher->lr_no ?: ($voucher->sr_no ? 'SR-'.$voucher->sr_no : 'Voucher #'.$voucher->id);

                return [
                    'id' => $voucher->id,
                    'supplier_id' => $voucher->supplier_id,
                    'bill_no' => $reference,
                    'invoice_date' => $voucher->lr_date?->format('d-m-Y'),
                    'total_amount' => round((float) $voucher->supplier_freight, 2),
                    'paid_amount' => $paid,
                    'pending_amount' => $pending,
                ];
            })
            ->filter(fn (array $bill) => $bill['pending_amount'] > 0.004)
            ->values();

        return view('payments.index', [
            'paymentType' => 'supplier',
            'title' => 'Supplier Payment',
            'partyLabel' => 'Supplier / Transporter',
            'partyField' => 'supplier_id',
            'partyRelation' => 'supplier',
            'parties' => $parties,
            'payments' => $payments,
            'pendingSupplierBills' => $pendingSupplierBills,
            'supplierOutstandings' => $supplierOutstandings,
            'totalAmount' => $totalAmount,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedPartyId' => $supplierId,
            'storeRoute' => route('payments.suppliers.store'),
            'indexRoute' => route('payments.suppliers.index'),
            'attachmentRouteName' => 'payments.suppliers.attachment',
            'destroyRouteName' => 'payments.suppliers.destroy',
        ]);
    }

    public function supplierStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'payment_type' => ['required', 'in:on_account,against_bill'],
            'voucher_id' => ['nullable', 'integer', 'exists:vouchers,id'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $paymentType = $validated['payment_type'];
        if ($paymentType === 'against_bill' && empty($validated['voucher_id'])) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Please select a pending supplier bill / LR for Against Bill payment.',
            ]);
        }

        $attachment = $this->storeAttachment($request, 'supplier-payment-receipts');

        try {
            DB::transaction(function () use ($request, $validated, $paymentType, $attachment) {
                $voucherId = null;
                $amount = round((float) $validated['amount'], 2);

                if ($paymentType === 'against_bill') {
                    $voucher = Voucher::query()
                        ->whereKey((int) $validated['voucher_id'])
                        ->where('supplier_id', (int) $validated['supplier_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $voucher) {
                        throw ValidationException::withMessages([
                            'voucher_id' => 'Selected bill / LR does not belong to the selected supplier.',
                        ]);
                    }

                    $legacyPaid = (float) SupplierPayment::query()
                        ->where('voucher_id', $voucher->id)
                        ->sum('amount');

                    $allocatedPaid = (float) SupplierPartyPayment::query()
                        ->where('voucher_id', $voucher->id)
                        ->where('payment_type', 'against_bill')
                        ->sum('amount');

                    $outstanding = max(0, round(
                        (float) $voucher->supplier_freight
                        - (float) $voucher->advance_paid
                        - $legacyPaid
                        - $allocatedPaid,
                        2
                    ));

                    if ($outstanding <= 0.004) {
                        throw ValidationException::withMessages([
                            'voucher_id' => 'Selected supplier bill / LR is already fully paid.',
                        ]);
                    }

                    if ($amount > $outstanding + 0.004) {
                        throw ValidationException::withMessages([
                            'amount' => 'Payment cannot exceed bill outstanding amount ₹'.number_format($outstanding, 2).'.',
                        ]);
                    }

                    $voucherId = $voucher->id;
                }

                SupplierPartyPayment::query()->create([
                    'supplier_id' => $validated['supplier_id'],
                    'payment_type' => $paymentType,
                    'voucher_id' => $voucherId,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $amount,
                    'payment_mode' => $this->nullableText($validated['payment_mode'] ?? null),
                    'reference' => $this->nullableText($validated['reference'] ?? null),
                    'remarks' => $this->nullableText($validated['remarks'] ?? null),
                    ...$attachment,
                    'created_by' => $request->user()?->id,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($attachment['attachment_path']) {
                Storage::disk('local')->delete($attachment['attachment_path']);
            }

            throw $exception;
        }

        return redirect()->route('payments.suppliers.index')->with('success', 'Supplier payment added successfully.');
    }

    public function supplierAttachment(SupplierPartyPayment $payment): BinaryFileResponse
    {
        return $this->attachmentResponse($payment->attachment_path, $payment->attachment_name, $payment->attachment_mime);
    }

    public function supplierDestroy(SupplierPartyPayment $payment): RedirectResponse
    {
        $path = $payment->attachment_path;
        $payment->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'Supplier payment deleted.');
    }

    public function customerIndex(Request $request): View
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        $customerId = $request->integer('customer_id') ?: null;

        $query = CustomerPartyPayment::query()
            ->with(['customer:id,code,name', 'invoiceBatch:id,bill_no'])
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId));

        $totalAmount = (float) (clone $query)->sum('amount');
        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(50)->withQueryString();
        $parties = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'opening_balance']);

        $customerIds = $parties->pluck('id');
        $invoiceTotals = InvoiceBatch::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy('customer_id')
            ->pluck('total', 'customer_id');
        $partyPaymentTotals = CustomerPartyPayment::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('customer_id')
            ->pluck('total', 'customer_id');
        $legacyPaymentTotals = CustomerPayment::query()
            ->join('invoice_batches', 'customer_payments.invoice_batch_id', '=', 'invoice_batches.id')
            ->whereIn('invoice_batches.customer_id', $customerIds)
            ->selectRaw('invoice_batches.customer_id as customer_id, COALESCE(SUM(customer_payments.amount), 0) as total')
            ->groupBy('invoice_batches.customer_id')
            ->pluck('total', 'customer_id');

        $customerOutstandings = [];
        $parties->each(function (Customer $party) use ($invoiceTotals, $partyPaymentTotals, $legacyPaymentTotals, &$customerOutstandings) {
            $balance = round(
                (float) $party->opening_balance
                + (float) ($invoiceTotals[$party->id] ?? 0)
                - (float) ($partyPaymentTotals[$party->id] ?? 0)
                - (float) ($legacyPaymentTotals[$party->id] ?? 0),
                2
            );

            $outstanding = max(0, $balance);
            $party->setAttribute('outstanding_amount', $outstanding);
            $customerOutstandings[(string) $party->id] = $outstanding;
        });

        $pendingBills = InvoiceBatch::query()
            ->select(['invoice_batches.id', 'invoice_batches.bill_no', 'invoice_batches.invoice_date', 'invoice_batches.customer_id', 'invoice_batches.total_amount'])
            ->selectSub(
                CustomerPayment::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('invoice_batch_id', 'invoice_batches.id'),
                'legacy_paid_amount'
            )
            ->selectSub(
                CustomerPartyPayment::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('invoice_batch_id', 'invoice_batches.id')
                    ->where('payment_type', 'against_bill'),
                'allocated_paid_amount'
            )
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (InvoiceBatch $bill) {
                $paid = round((float) $bill->legacy_paid_amount + (float) $bill->allocated_paid_amount, 2);
                $pending = max(0, round((float) $bill->total_amount - $paid, 2));

                return [
                    'id' => $bill->id,
                    'customer_id' => $bill->customer_id,
                    'bill_no' => $bill->bill_no,
                    'invoice_date' => $bill->invoice_date?->format('d-m-Y'),
                    'total_amount' => round((float) $bill->total_amount, 2),
                    'paid_amount' => $paid,
                    'pending_amount' => $pending,
                ];
            })
            ->filter(fn (array $bill) => $bill['pending_amount'] > 0.004)
            ->values();

        return view('payments.index', [
            'paymentType' => 'customer',
            'title' => 'Customer Payment',
            'partyLabel' => 'Customer',
            'partyField' => 'customer_id',
            'partyRelation' => 'customer',
            'parties' => $parties,
            'payments' => $payments,
            'pendingBills' => $pendingBills,
            'customerOutstandings' => $customerOutstandings,
            'totalAmount' => $totalAmount,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedPartyId' => $customerId,
            'storeRoute' => route('payments.customers.store'),
            'indexRoute' => route('payments.customers.index'),
            'attachmentRouteName' => 'payments.customers.attachment',
            'destroyRouteName' => 'payments.customers.destroy',
        ]);
    }

    public function customerStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_type' => ['required', 'in:on_account,against_bill'],
            'invoice_batch_id' => ['nullable', 'integer', 'exists:invoice_batches,id'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $paymentType = $validated['payment_type'];
        if ($paymentType === 'against_bill' && empty($validated['invoice_batch_id'])) {
            throw ValidationException::withMessages([
                'invoice_batch_id' => 'Please select a pending bill for Against Bill payment.',
            ]);
        }

        $attachment = $this->storeAttachment($request, 'customer-payment-receipts');

        try {
            DB::transaction(function () use ($request, $validated, $paymentType, $attachment) {
                $invoiceId = null;
                $amount = round((float) $validated['amount'], 2);

                if ($paymentType === 'against_bill') {
                    $invoice = InvoiceBatch::query()
                        ->whereKey((int) $validated['invoice_batch_id'])
                        ->where('customer_id', (int) $validated['customer_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $invoice) {
                        throw ValidationException::withMessages([
                            'invoice_batch_id' => 'Selected bill does not belong to the selected customer.',
                        ]);
                    }

                    $legacyPaid = (float) CustomerPayment::query()
                        ->where('invoice_batch_id', $invoice->id)
                        ->sum('amount');

                    $allocatedPaid = (float) CustomerPartyPayment::query()
                        ->where('invoice_batch_id', $invoice->id)
                        ->where('payment_type', 'against_bill')
                        ->sum('amount');

                    $outstanding = max(0, round((float) $invoice->total_amount - $legacyPaid - $allocatedPaid, 2));
                    if ($amount > $outstanding + 0.004) {
                        throw ValidationException::withMessages([
                            'amount' => 'Payment cannot exceed bill outstanding amount ₹'.number_format($outstanding, 2).'.',
                        ]);
                    }

                    if ($outstanding <= 0.004) {
                        throw ValidationException::withMessages([
                            'invoice_batch_id' => 'Selected bill is already fully paid.',
                        ]);
                    }

                    $invoiceId = $invoice->id;
                }

                CustomerPartyPayment::query()->create([
                    'customer_id' => $validated['customer_id'],
                    'payment_type' => $paymentType,
                    'invoice_batch_id' => $invoiceId,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $amount,
                    'payment_mode' => $this->nullableText($validated['payment_mode'] ?? null),
                    'reference' => $this->nullableText($validated['reference'] ?? null),
                    'remarks' => $this->nullableText($validated['remarks'] ?? null),
                    ...$attachment,
                    'created_by' => $request->user()?->id,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($attachment['attachment_path']) {
                Storage::disk('local')->delete($attachment['attachment_path']);
            }

            throw $exception;
        }

        return redirect()->route('payments.customers.index')->with('success', 'Customer payment added successfully.');
    }

    public function customerAttachment(CustomerPartyPayment $payment): BinaryFileResponse
    {
        return $this->attachmentResponse($payment->attachment_path, $payment->attachment_name, $payment->attachment_mime);
    }

    public function customerDestroy(CustomerPartyPayment $payment): RedirectResponse
    {
        $path = $payment->attachment_path;
        $payment->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'Customer payment deleted.');
    }

    /**
     * @return array{attachment_name:?string,attachment_path:?string,attachment_mime:?string,attachment_size:int}
     */
    private function storeAttachment(Request $request, string $directory): array
    {
        $file = $request->file('attachment');
        if (! $file) {
            return [
                'attachment_name' => null,
                'attachment_path' => null,
                'attachment_mime' => null,
                'attachment_size' => 0,
            ];
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory, $filename, 'local');

        if (! $path) {
            abort(500, 'Payment attachment could not be stored.');
        }

        return [
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_path' => $path,
            'attachment_mime' => $file->getMimeType(),
            'attachment_size' => (int) ($file->getSize() ?: 0),
        ];
    }

    private function attachmentResponse(?string $path, ?string $name, ?string $mime): BinaryFileResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($name ?: 'receipt').'"',
        ]);
    }

    private function filterDate(string $value, string $fallback): string
    {
        if ($value === '') {
            return $fallback;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
