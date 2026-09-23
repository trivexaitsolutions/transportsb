<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\CashTransaction;
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
    private const PAYMENT_MODES = ['Cash', 'NEFT', 'RTGS', 'UPI', 'Bank', 'Bank Transfer', 'Cheque', 'Other'];
    private const BANK_MODES = ['neft', 'rtgs', 'upi', 'bank', 'bank transfer'];

    public function supplierIndex(Request $request): View
    {
        [$fromDate, $toDate] = $this->dateRange($request);
        $supplierId = $request->integer('supplier_id') ?: null;
        $modeFilter = $this->filterMode($request->string('payment_mode')->toString());
        $chequeStatusFilter = $this->filterChequeStatus($request->string('cheque_status')->toString());

        $query = SupplierPartyPayment::query()
            ->with(['supplier:id,code,name', 'voucher:id,lr_no,sr_no', 'bank:id,name'])
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($modeFilter, fn ($q) => $q->whereRaw('LOWER(COALESCE(payment_mode, "")) = ?', [strtolower($modeFilter)]))
            ->when($chequeStatusFilter, fn ($q) => $q->whereRaw('LOWER(COALESCE(payment_mode, "")) = ?', ['cheque'])->where('cheque_status', $chequeStatusFilter));

        $totalAmount = (float) (clone $query)->sum('amount');
        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(50)->withQueryString();
        $parties = Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'opening_balance']);

        $supplierIds = $parties->pluck('id');
        $freightTotals = Voucher::query()->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, COALESCE(SUM(supplier_freight), 0) as total')->groupBy('supplier_id')->pluck('total', 'supplier_id');
        $advanceTotals = Voucher::query()->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, COALESCE(SUM(advance_paid), 0) as total')->groupBy('supplier_id')->pluck('total', 'supplier_id');
        $partyPaymentTotals = SupplierPartyPayment::query()->posted()->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, COALESCE(SUM(amount), 0) as total')->groupBy('supplier_id')->pluck('total', 'supplier_id');
        $legacyPaymentTotals = SupplierPayment::query()
            ->join('vouchers', 'supplier_payments.voucher_id', '=', 'vouchers.id')
            ->whereIn('vouchers.supplier_id', $supplierIds)
            ->selectRaw('vouchers.supplier_id as supplier_id, COALESCE(SUM(supplier_payments.amount), 0) as total')
            ->groupBy('vouchers.supplier_id')->pluck('total', 'supplier_id');

        $supplierOutstandings = [];
        $parties->each(function (Supplier $party) use ($freightTotals, $advanceTotals, $partyPaymentTotals, $legacyPaymentTotals, &$supplierOutstandings) {
            $balance = round((float) $party->opening_balance
                + (float) ($freightTotals[$party->id] ?? 0)
                - (float) ($advanceTotals[$party->id] ?? 0)
                - (float) ($partyPaymentTotals[$party->id] ?? 0)
                - (float) ($legacyPaymentTotals[$party->id] ?? 0), 2);
            $outstanding = max(0, $balance);
            $party->setAttribute('outstanding_amount', $outstanding);
            $supplierOutstandings[(string) $party->id] = $outstanding;
        });

        $pendingSupplierBills = Voucher::query()
            ->select(['vouchers.id', 'vouchers.sr_no', 'vouchers.lr_date', 'vouchers.lr_no', 'vouchers.supplier_id', 'vouchers.supplier_freight', 'vouchers.advance_paid'])
            ->selectSub(SupplierPayment::query()->selectRaw('COALESCE(SUM(amount), 0)')->whereColumn('voucher_id', 'vouchers.id'), 'legacy_paid_amount')
            ->selectSub(
                SupplierPartyPayment::query()->posted()->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('voucher_id', 'vouchers.id')->where('payment_type', 'against_bill'),
                'allocated_paid_amount'
            )
            ->whereNotNull('supplier_id')->where('supplier_freight', '>', 0)
            ->orderByDesc('lr_date')->orderByDesc('id')->get()
            ->map(function (Voucher $voucher) {
                $paid = round((float) $voucher->advance_paid + (float) $voucher->legacy_paid_amount + (float) $voucher->allocated_paid_amount, 2);
                $pending = max(0, round((float) $voucher->supplier_freight - $paid, 2));
                return [
                    'id' => $voucher->id,
                    'supplier_id' => $voucher->supplier_id,
                    'bill_no' => $voucher->lr_no ?: ($voucher->sr_no ? 'SR-'.$voucher->sr_no : 'Voucher #'.$voucher->id),
                    'invoice_date' => $voucher->lr_date?->format('d-m-Y'),
                    'total_amount' => round((float) $voucher->supplier_freight, 2),
                    'paid_amount' => $paid,
                    'pending_amount' => $pending,
                ];
            })->filter(fn (array $bill) => $bill['pending_amount'] > 0.004)->values();

        return view('payments.index', $this->viewPayload(
            'supplier', 'Supplier Payment', 'Supplier / Transporter', 'supplier_id', 'supplier', $parties, $payments,
            $totalAmount, $fromDate, $toDate, $supplierId, $modeFilter, $chequeStatusFilter,
            route('payments.suppliers.store'), route('payments.suppliers.index'), 'payments.suppliers.attachment',
            'payments.suppliers.destroy', 'payments.suppliers.cheque-status',
            ['pendingSupplierBills' => $pendingSupplierBills, 'supplierOutstandings' => $supplierOutstandings]
        ));
    }

    public function supplierStore(Request $request): RedirectResponse
    {
        $validated = $this->validatePayment($request, 'supplier');
        $paymentType = $validated['payment_type'];
        if ($paymentType === 'against_bill' && empty($validated['voucher_id'])) {
            throw ValidationException::withMessages(['voucher_id' => 'Please select a pending supplier bill / LR for Against Bill payment.']);
        }
        $this->validateModePosting($validated);
        $attachment = $this->storeAttachment($request, 'supplier-payment-receipts');

        try {
            DB::transaction(function () use ($request, $validated, $paymentType, $attachment) {
                $voucherId = null;
                $amount = round((float) $validated['amount'], 2);
                if ($paymentType === 'against_bill') {
                    $voucher = Voucher::query()->whereKey((int) $validated['voucher_id'])
                        ->where('supplier_id', (int) $validated['supplier_id'])->lockForUpdate()->first();
                    if (! $voucher) throw ValidationException::withMessages(['voucher_id' => 'Selected bill / LR does not belong to the selected supplier.']);
                    $outstanding = $this->supplierBillOutstanding($voucher);
                    if ($outstanding <= 0.004) throw ValidationException::withMessages(['voucher_id' => 'Selected supplier bill / LR is already fully paid.']);
                    if ($amount > $outstanding + 0.004) throw ValidationException::withMessages(['amount' => 'Payment cannot exceed bill outstanding amount ₹'.number_format($outstanding, 2).'.']);
                    $voucherId = $voucher->id;
                }

                $payment = SupplierPartyPayment::query()->create([
                    'supplier_id' => $validated['supplier_id'], 'payment_type' => $paymentType, 'voucher_id' => $voucherId,
                    'payment_date' => $validated['payment_date'], 'amount' => $amount,
                    'payment_mode' => $validated['payment_mode'], 'bank_id' => $validated['bank_id'] ?? null,
                    'cheque_status' => $this->isCheque($validated['payment_mode']) ? $validated['cheque_status'] : null,
                    'cheque_cleared_date' => $this->isCheque($validated['payment_mode']) && ($validated['cheque_status'] ?? null) === 'cleared' ? $validated['payment_date'] : null,
                    'reference' => $this->nullableText($validated['reference'] ?? null), 'remarks' => $this->nullableText($validated['remarks'] ?? null),
                    ...$attachment, 'created_by' => $request->user()?->id,
                ]);
                $this->syncPosting($payment, 'supplier', $request->user()?->id);
            });
        } catch (\Throwable $exception) {
            if ($attachment['attachment_path']) Storage::disk('local')->delete($attachment['attachment_path']);
            throw $exception;
        }

        return redirect()->route('payments.suppliers.index')->with('success', 'Supplier payment added successfully.');
    }

    public function supplierChequeStatus(Request $request, SupplierPartyPayment $payment): RedirectResponse
    {
        $status = $this->validatedChequeStatus($request, $payment->payment_mode);
        DB::transaction(function () use ($request, $payment, $status) {
            $locked = SupplierPartyPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($status === 'cleared' && ! $locked->bank_id) throw ValidationException::withMessages(['cheque_status' => 'Select a bank before clearing this cheque.']);
            if ($status === 'cleared' && $locked->payment_type === 'against_bill' && $locked->voucher_id) {
                $voucher = Voucher::query()->lockForUpdate()->findOrFail($locked->voucher_id);
                $outstanding = $this->supplierBillOutstanding($voucher, $locked->id);
                if ((float) $locked->amount > $outstanding + 0.004) throw ValidationException::withMessages(['cheque_status' => 'Cheque cannot be cleared because the bill pending amount is now ₹'.number_format($outstanding, 2).'.']);
            }
            $locked->update(['cheque_status' => $status, 'cheque_cleared_date' => $status === 'cleared' ? now()->toDateString() : null]);
            $this->syncPosting($locked, 'supplier', $request->user()?->id);
        });
        return back()->with('success', 'Supplier cheque status updated.');
    }

    public function supplierAttachment(SupplierPartyPayment $payment): BinaryFileResponse
    {
        return $this->attachmentResponse($payment->attachment_path, $payment->attachment_name, $payment->attachment_mime);
    }

    public function supplierDestroy(SupplierPartyPayment $payment): RedirectResponse
    {
        $path = $payment->attachment_path;
        DB::transaction(function () use ($payment) { $this->removePosting('supplier', $payment->id); $payment->delete(); });
        if ($path) Storage::disk('local')->delete($path);
        return back()->with('success', 'Supplier payment deleted.');
    }

    public function customerIndex(Request $request): View
    {
        [$fromDate, $toDate] = $this->dateRange($request);
        $customerId = $request->integer('customer_id') ?: null;
        $modeFilter = $this->filterMode($request->string('payment_mode')->toString());
        $chequeStatusFilter = $this->filterChequeStatus($request->string('cheque_status')->toString());

        $query = CustomerPartyPayment::query()
            ->with(['customer:id,code,name', 'invoiceBatch:id,bill_no', 'bank:id,name'])
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($modeFilter, fn ($q) => $q->whereRaw('LOWER(COALESCE(payment_mode, "")) = ?', [strtolower($modeFilter)]))
            ->when($chequeStatusFilter, fn ($q) => $q->whereRaw('LOWER(COALESCE(payment_mode, "")) = ?', ['cheque'])->where('cheque_status', $chequeStatusFilter));

        $totalAmount = (float) (clone $query)->sum('amount');
        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(50)->withQueryString();
        $parties = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'opening_balance']);

        $customerIds = $parties->pluck('id');
        $invoiceTotals = InvoiceBatch::query()->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, COALESCE(SUM(total_amount), 0) as total')->groupBy('customer_id')->pluck('total', 'customer_id');
        $partyPaymentTotals = CustomerPartyPayment::query()->posted()->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, COALESCE(SUM(amount), 0) as total')->groupBy('customer_id')->pluck('total', 'customer_id');
        $legacyPaymentTotals = CustomerPayment::query()
            ->join('invoice_batches', 'customer_payments.invoice_batch_id', '=', 'invoice_batches.id')
            ->whereIn('invoice_batches.customer_id', $customerIds)
            ->selectRaw('invoice_batches.customer_id as customer_id, COALESCE(SUM(customer_payments.amount), 0) as total')
            ->groupBy('invoice_batches.customer_id')->pluck('total', 'customer_id');

        $customerOutstandings = [];
        $parties->each(function (Customer $party) use ($invoiceTotals, $partyPaymentTotals, $legacyPaymentTotals, &$customerOutstandings) {
            $balance = round((float) $party->opening_balance + (float) ($invoiceTotals[$party->id] ?? 0)
                - (float) ($partyPaymentTotals[$party->id] ?? 0) - (float) ($legacyPaymentTotals[$party->id] ?? 0), 2);
            $outstanding = max(0, $balance);
            $party->setAttribute('outstanding_amount', $outstanding);
            $customerOutstandings[(string) $party->id] = $outstanding;
        });

        $pendingBills = InvoiceBatch::query()
            ->select(['invoice_batches.id', 'invoice_batches.bill_no', 'invoice_batches.invoice_date', 'invoice_batches.customer_id', 'invoice_batches.total_amount'])
            ->selectSub(CustomerPayment::query()->selectRaw('COALESCE(SUM(amount), 0)')->whereColumn('invoice_batch_id', 'invoice_batches.id'), 'legacy_paid_amount')
            ->selectSub(
                CustomerPartyPayment::query()->posted()->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('invoice_batch_id', 'invoice_batches.id')->where('payment_type', 'against_bill'),
                'allocated_paid_amount'
            )
            ->orderByDesc('invoice_date')->orderByDesc('id')->get()
            ->map(function (InvoiceBatch $bill) {
                $paid = round((float) $bill->legacy_paid_amount + (float) $bill->allocated_paid_amount, 2);
                return [
                    'id' => $bill->id, 'customer_id' => $bill->customer_id, 'bill_no' => $bill->bill_no,
                    'invoice_date' => $bill->invoice_date?->format('d-m-Y'), 'total_amount' => round((float) $bill->total_amount, 2),
                    'paid_amount' => $paid, 'pending_amount' => max(0, round((float) $bill->total_amount - $paid, 2)),
                ];
            })->filter(fn (array $bill) => $bill['pending_amount'] > 0.004)->values();

        return view('payments.index', $this->viewPayload(
            'customer', 'Customer Receipt', 'Customer', 'customer_id', 'customer', $parties, $payments,
            $totalAmount, $fromDate, $toDate, $customerId, $modeFilter, $chequeStatusFilter,
            route('payments.customers.store'), route('payments.customers.index'), 'payments.customers.attachment',
            'payments.customers.destroy', 'payments.customers.cheque-status',
            ['pendingBills' => $pendingBills, 'customerOutstandings' => $customerOutstandings]
        ));
    }

    public function customerStore(Request $request): RedirectResponse
    {
        $validated = $this->validatePayment($request, 'customer');
        $paymentType = $validated['payment_type'];
        if ($paymentType === 'against_bill' && empty($validated['invoice_batch_id'])) {
            throw ValidationException::withMessages(['invoice_batch_id' => 'Please select a pending bill for Against Bill payment.']);
        }
        $this->validateModePosting($validated);
        $attachment = $this->storeAttachment($request, 'customer-payment-receipts');

        try {
            DB::transaction(function () use ($request, $validated, $paymentType, $attachment) {
                $invoiceId = null;
                $amount = round((float) $validated['amount'], 2);
                $tdsPercent = $paymentType === 'on_account' ? round((float) ($validated['tds_percent'] ?? 0), 2) : 0.0;
                $tdsAmount = round($amount * $tdsPercent / 100, 2);
                $netAmount = max(0, round($amount - $tdsAmount, 2));
                if ($paymentType === 'against_bill') {
                    $invoice = InvoiceBatch::query()->whereKey((int) $validated['invoice_batch_id'])
                        ->where('customer_id', (int) $validated['customer_id'])->lockForUpdate()->first();
                    if (! $invoice) throw ValidationException::withMessages(['invoice_batch_id' => 'Selected bill does not belong to the selected customer.']);
                    $outstanding = $this->customerBillOutstanding($invoice);
                    if ($outstanding <= 0.004) throw ValidationException::withMessages(['invoice_batch_id' => 'Selected bill is already fully paid.']);
                    if ($amount > $outstanding + 0.004) throw ValidationException::withMessages(['amount' => 'Payment cannot exceed bill outstanding amount ₹'.number_format($outstanding, 2).'.']);
                    $invoiceId = $invoice->id;
                }

                $payment = CustomerPartyPayment::query()->create([
                    'customer_id' => $validated['customer_id'], 'payment_type' => $paymentType, 'invoice_batch_id' => $invoiceId,
                    'payment_date' => $validated['payment_date'], 'amount' => $amount,
                    'tds_percent' => $tdsPercent, 'tds_amount' => $tdsAmount, 'net_amount' => $netAmount,
                    'payment_mode' => $validated['payment_mode'], 'bank_id' => $validated['bank_id'] ?? null,
                    'cheque_status' => $this->isCheque($validated['payment_mode']) ? $validated['cheque_status'] : null,
                    'cheque_cleared_date' => $this->isCheque($validated['payment_mode']) && ($validated['cheque_status'] ?? null) === 'cleared' ? $validated['payment_date'] : null,
                    'reference' => $this->nullableText($validated['reference'] ?? null), 'remarks' => $this->nullableText($validated['remarks'] ?? null),
                    ...$attachment, 'created_by' => $request->user()?->id,
                ]);
                $this->syncPosting($payment, 'customer', $request->user()?->id);
            });
        } catch (\Throwable $exception) {
            if ($attachment['attachment_path']) Storage::disk('local')->delete($attachment['attachment_path']);
            throw $exception;
        }

        return redirect()->route('payments.customers.index')->with('success', 'Customer receipt added successfully.');
    }

    public function customerChequeStatus(Request $request, CustomerPartyPayment $payment): RedirectResponse
    {
        $status = $this->validatedChequeStatus($request, $payment->payment_mode);
        DB::transaction(function () use ($request, $payment, $status) {
            $locked = CustomerPartyPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($status === 'cleared' && ! $locked->bank_id) throw ValidationException::withMessages(['cheque_status' => 'Select a bank before clearing this cheque.']);
            if ($status === 'cleared' && $locked->payment_type === 'against_bill' && $locked->invoice_batch_id) {
                $invoice = InvoiceBatch::query()->lockForUpdate()->findOrFail($locked->invoice_batch_id);
                $outstanding = $this->customerBillOutstanding($invoice, $locked->id);
                if ((float) $locked->amount > $outstanding + 0.004) throw ValidationException::withMessages(['cheque_status' => 'Cheque cannot be cleared because the bill pending amount is now ₹'.number_format($outstanding, 2).'.']);
            }
            $locked->update(['cheque_status' => $status, 'cheque_cleared_date' => $status === 'cleared' ? now()->toDateString() : null]);
            $this->syncPosting($locked, 'customer', $request->user()?->id);
        });
        return back()->with('success', 'Customer receipt cheque status updated.');
    }

    public function customerAttachment(CustomerPartyPayment $payment): BinaryFileResponse
    {
        return $this->attachmentResponse($payment->attachment_path, $payment->attachment_name, $payment->attachment_mime);
    }

    public function customerDestroy(CustomerPartyPayment $payment): RedirectResponse
    {
        $path = $payment->attachment_path;
        DB::transaction(function () use ($payment) { $this->removePosting('customer', $payment->id); $payment->delete(); });
        if ($path) Storage::disk('local')->delete($path);
        return back()->with('success', 'Customer receipt deleted.');
    }

    private function validatePayment(Request $request, string $type): array
    {
        $partyField = $type === 'customer' ? 'customer_id' : 'supplier_id';
        $partyTable = $type === 'customer' ? 'customers' : 'suppliers';
        $allocationField = $type === 'customer' ? 'invoice_batch_id' : 'voucher_id';
        $allocationTable = $type === 'customer' ? 'invoice_batches' : 'vouchers';
        $rules = [
            $partyField => ['required', 'integer', 'exists:'.$partyTable.',id'],
            'payment_type' => ['required', 'in:on_account,against_bill'],
            $allocationField => ['nullable', 'integer', 'exists:'.$allocationTable.',id'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['required', 'string', 'in:'.implode(',', self::PAYMENT_MODES)],
            'bank_id' => ['nullable', 'integer', 'exists:banks,id'],
            'cheque_status' => ['nullable', 'in:deposited,cleared'],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
        if ($type === 'customer') {
            $rules['tds_percent'] = ['nullable', 'numeric', 'min:0', 'max:100'];
        }
        return $request->validate($rules);
    }

    private function validateModePosting(array $validated): void
    {
        $mode = $validated['payment_mode'] ?? '';
        if (($this->isBankMode($mode) || $this->isCheque($mode)) && empty($validated['bank_id'])) {
            throw ValidationException::withMessages(['bank_id' => 'Please select the bank used for this payment.']);
        }
        if ($this->isCheque($mode) && empty($validated['cheque_status'])) {
            throw ValidationException::withMessages(['cheque_status' => 'Please select Cheque Status: Deposited or Cleared.']);
        }
    }

    private function validatedChequeStatus(Request $request, ?string $mode): string
    {
        if (! $this->isCheque($mode)) throw ValidationException::withMessages(['cheque_status' => 'Cheque status can only be changed for Cheque payments.']);
        return $request->validate(['cheque_status' => ['required', 'in:deposited,cleared']])['cheque_status'];
    }

    private function customerBillOutstanding(InvoiceBatch $invoice, ?int $excludePaymentId = null): float
    {
        $legacyPaid = (float) CustomerPayment::query()->where('invoice_batch_id', $invoice->id)->sum('amount');
        $allocated = CustomerPartyPayment::query()->posted()->where('invoice_batch_id', $invoice->id)->where('payment_type', 'against_bill')
            ->when($excludePaymentId, fn ($q) => $q->where('id', '!=', $excludePaymentId))->sum('amount');
        return max(0, round((float) $invoice->total_amount - $legacyPaid - (float) $allocated, 2));
    }

    private function supplierBillOutstanding(Voucher $voucher, ?int $excludePaymentId = null): float
    {
        $legacyPaid = (float) SupplierPayment::query()->where('voucher_id', $voucher->id)->sum('amount');
        $allocated = SupplierPartyPayment::query()->posted()->where('voucher_id', $voucher->id)->where('payment_type', 'against_bill')
            ->when($excludePaymentId, fn ($q) => $q->where('id', '!=', $excludePaymentId))->sum('amount');
        return max(0, round((float) $voucher->supplier_freight - (float) $voucher->advance_paid - $legacyPaid - (float) $allocated, 2));
    }

    private function syncPosting(CustomerPartyPayment|SupplierPartyPayment $payment, string $type, ?int $userId): void
    {
        $this->removePosting($type, $payment->id);
        if (! $this->paymentIsPosted($payment->payment_mode, $payment->cheque_status)) return;

        $isCustomer = $type === 'customer';
        $transactionType = $isCustomer ? 'deposit' : 'withdraw';
        $partyName = $isCustomer ? ($payment->customer?->name ?: 'Customer') : ($payment->supplier?->name ?: 'Supplier');
        $remarks = trim(($isCustomer ? 'Customer Receipt' : 'Supplier Payment').' · '.$partyName
            .($payment->reference ? ' · Ref: '.$payment->reference : '')
            .($payment->remarks ? ' · '.$payment->remarks : ''));

        $sourceType = $type.'_party_payment';
        $postingAmount = $isCustomer ? (float) ($payment->net_amount ?? $payment->amount) : (float) $payment->amount;
        if ($postingAmount <= 0) return;
        if (strtolower((string) $payment->payment_mode) === 'cash') {
            CashTransaction::query()->create([
                'transaction_date' => $payment->payment_date?->toDateString() ?: now()->toDateString(),
                'type' => $transactionType, 'amount' => $postingAmount, 'remarks' => $remarks,
                'source_type' => $sourceType, 'source_id' => $payment->id, 'created_by' => $userId,
            ]);
            return;
        }

        if ($this->isBankMode($payment->payment_mode) || $this->isCheque($payment->payment_mode)) {
            if (! $payment->bank_id) return;
            BankTransaction::query()->create([
                'bank_id' => $payment->bank_id,
                'transaction_date' => ($this->isCheque($payment->payment_mode) && $payment->cheque_cleared_date)
                    ? $payment->cheque_cleared_date->toDateString()
                    : ($payment->payment_date?->toDateString() ?: now()->toDateString()),
                'type' => $transactionType, 'amount' => $postingAmount, 'remarks' => $remarks,
                'source_type' => $sourceType, 'source_id' => $payment->id, 'created_by' => $userId,
            ]);
        }
    }

    private function removePosting(string $type, int $id): void
    {
        $sourceType = $type.'_party_payment';
        BankTransaction::query()->where('source_type', $sourceType)->where('source_id', $id)->delete();
        CashTransaction::query()->where('source_type', $sourceType)->where('source_id', $id)->delete();
    }

    private function paymentIsPosted(?string $mode, ?string $chequeStatus): bool
    {
        return ! $this->isCheque($mode) || $chequeStatus === 'cleared';
    }

    private function isCheque(?string $mode): bool
    {
        return strtolower(trim((string) $mode)) === 'cheque';
    }

    private function isBankMode(?string $mode): bool
    {
        return in_array(strtolower(trim((string) $mode)), self::BANK_MODES, true);
    }

    private function viewPayload(string $paymentType, string $title, string $partyLabel, string $partyField, string $partyRelation,
        $parties, $payments, float $totalAmount, string $fromDate, string $toDate, ?int $selectedPartyId,
        ?string $modeFilter, ?string $chequeStatusFilter, string $storeRoute, string $indexRoute,
        string $attachmentRouteName, string $destroyRouteName, string $chequeStatusRouteName, array $extra): array
    {
        $banks = Bank::query()->with('transportName:id,name')->where('is_active', true)->orderBy('name')->get(['id', 'transport_name_id', 'name']);
        return array_merge([
            'paymentType' => $paymentType, 'title' => $title, 'partyLabel' => $partyLabel, 'partyField' => $partyField,
            'partyRelation' => $partyRelation, 'parties' => $parties, 'payments' => $payments, 'banks' => $banks,
            'defaultBankId' => $banks->count() === 1 ? (int) $banks->first()->id : null,
            'paymentModes' => self::PAYMENT_MODES, 'modeFilter' => $modeFilter, 'chequeStatusFilter' => $chequeStatusFilter,
            'totalAmount' => $totalAmount, 'fromDate' => $fromDate, 'toDate' => $toDate, 'selectedPartyId' => $selectedPartyId,
            'storeRoute' => $storeRoute, 'indexRoute' => $indexRoute, 'attachmentRouteName' => $attachmentRouteName,
            'destroyRouteName' => $destroyRouteName, 'chequeStatusRouteName' => $chequeStatusRouteName,
        ], $extra);
    }

    private function dateRange(Request $request): array
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());
        if ($fromDate > $toDate) [$fromDate, $toDate] = [$toDate, $fromDate];
        return [$fromDate, $toDate];
    }

    private function filterMode(string $value): ?string
    {
        foreach (self::PAYMENT_MODES as $mode) if (strcasecmp($value, $mode) === 0) return $mode;
        return null;
    }

    private function filterChequeStatus(string $value): ?string
    {
        return in_array($value, ['deposited', 'cleared'], true) ? $value : null;
    }

    /** @return array{attachment_name:?string,attachment_path:?string,attachment_mime:?string,attachment_size:int} */
    private function storeAttachment(Request $request, string $directory): array
    {
        $file = $request->file('attachment');
        if (! $file) return ['attachment_name' => null, 'attachment_path' => null, 'attachment_mime' => null, 'attachment_size' => 0];
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory, $filename, 'local');
        if (! $path) abort(500, 'Payment attachment could not be stored.');
        return ['attachment_name' => $file->getClientOriginalName(), 'attachment_path' => $path,
            'attachment_mime' => $file->getMimeType(), 'attachment_size' => (int) ($file->getSize() ?: 0)];
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
        if ($value === '') return $fallback;
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
