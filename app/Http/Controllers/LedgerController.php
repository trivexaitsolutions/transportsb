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
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LedgerController extends Controller
{
    public function customerIndex(Request $request): View
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $partyId = $request->integer('customer_id') ?: null;
        $parties = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'opening_balance']);
        $party = $partyId ? Customer::query()->find($partyId) : null;

        $ledger = $party
            ? $this->customerLedger($party, $fromDate, $toDate)
            : $this->emptyLedger();

        return view('ledgers.index', array_merge($ledger, [
            'ledgerType' => 'customer',
            'title' => 'Customer Ledger',
            'partyLabel' => 'Customer',
            'partyField' => 'customer_id',
            'parties' => $parties,
            'selectedPartyId' => $partyId,
            'selectedParty' => $party,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'indexRoute' => route('ledgers.customers.index'),
        ]));
    }

    public function supplierIndex(Request $request): View
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $partyId = $request->integer('supplier_id') ?: null;
        $parties = Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'opening_balance']);
        $party = $partyId ? Supplier::query()->find($partyId) : null;

        $ledger = $party
            ? $this->supplierLedger($party, $fromDate, $toDate)
            : $this->emptyLedger();

        return view('ledgers.index', array_merge($ledger, [
            'ledgerType' => 'supplier',
            'title' => 'Supplier Ledger',
            'partyLabel' => 'Supplier / Transporter',
            'partyField' => 'supplier_id',
            'parties' => $parties,
            'selectedPartyId' => $partyId,
            'selectedParty' => $party,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'indexRoute' => route('ledgers.suppliers.index'),
        ]));
    }

    private function customerLedger(Customer $customer, string $fromDate, string $toDate): array
    {
        $previousOutstanding = (float) $customer->opening_balance;

        $previousOutstanding += (float) InvoiceBatch::query()
            ->where('customer_id', $customer->id)
            ->whereDate('invoice_date', '<', $fromDate)
            ->sum('total_amount');

        $previousOutstanding -= (float) CustomerPartyPayment::query()
            ->where('customer_id', $customer->id)
            ->whereDate('payment_date', '<', $fromDate)
            ->sum('amount');

        $previousOutstanding -= (float) CustomerPayment::query()
            ->whereHas('invoiceBatch', fn ($q) => $q->where('customer_id', $customer->id))
            ->whereDate('payment_date', '<', $fromDate)
            ->sum('amount');

        $entries = collect();

        InvoiceBatch::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get(['id', 'bill_no', 'invoice_date', 'total_amount', 'remarks', 'created_at'])
            ->each(function ($bill) use ($entries) {
                $entries->push($this->entry(
                    $bill->invoice_date?->toDateString(),
                    'Bill / Invoice',
                    $bill->bill_no,
                    (float) $bill->total_amount,
                    0,
                    $bill->remarks,
                    $bill->created_at,
                    'bill-'.$bill->id
                ));
            });

        CustomerPartyPayment::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(function ($payment) use ($entries) {
                $entries->push($this->entry(
                    $payment->payment_date?->toDateString(),
                    'Payment',
                    $payment->reference ?: ('PAY-'.$payment->id),
                    0,
                    (float) $payment->amount,
                    $this->paymentNote($payment->payment_mode, $payment->remarks),
                    $payment->created_at,
                    'party-payment-'.$payment->id
                ));
            });

        CustomerPayment::query()
            ->with('invoiceBatch:id,bill_no,customer_id')
            ->whereHas('invoiceBatch', fn ($q) => $q->where('customer_id', $customer->id))
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(function ($payment) use ($entries) {
                $entries->push($this->entry(
                    $payment->payment_date?->toDateString(),
                    'Payment',
                    $payment->reference ?: ($payment->invoiceBatch?->bill_no ? 'Against '.$payment->invoiceBatch->bill_no : 'PAY-'.$payment->id),
                    0,
                    (float) $payment->amount,
                    $this->paymentNote($payment->payment_mode, $payment->remarks),
                    $payment->created_at,
                    'legacy-payment-'.$payment->id
                ));
            });

        return $this->finishLedger($entries, $previousOutstanding);
    }

    private function supplierLedger(Supplier $supplier, string $fromDate, string $toDate): array
    {
        $previousOutstanding = (float) $supplier->opening_balance;

        $previousOutstanding += (float) Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->whereDate('lr_date', '<', $fromDate)
            ->sum('supplier_freight');

        $previousOutstanding -= (float) Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->whereDate('lr_date', '<', $fromDate)
            ->sum('advance_paid');

        $previousOutstanding -= (float) SupplierPartyPayment::query()
            ->where('supplier_id', $supplier->id)
            ->whereDate('payment_date', '<', $fromDate)
            ->sum('amount');

        $previousOutstanding -= (float) SupplierPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->whereDate('payment_date', '<', $fromDate)
            ->sum('amount');

        $entries = collect();

        Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->whereBetween('lr_date', [$fromDate, $toDate])
            ->orderBy('lr_date')
            ->orderBy('id')
            ->get(['id', 'sr_no', 'lr_date', 'lr_no', 'supplier_freight', 'advance_paid', 'remarks', 'created_at'])
            ->each(function ($voucher) use ($entries) {
                if ((float) $voucher->supplier_freight > 0) {
                    $entries->push($this->entry(
                        $voucher->lr_date?->toDateString(),
                        'Supplier Freight',
                        $voucher->lr_no ?: ('SR-'.$voucher->sr_no),
                        (float) $voucher->supplier_freight,
                        0,
                        $voucher->remarks,
                        $voucher->created_at,
                        'voucher-'.$voucher->id.'-freight'
                    ));
                }

                if ((float) $voucher->advance_paid > 0) {
                    $entries->push($this->entry(
                        $voucher->lr_date?->toDateString(),
                        'Advance Payment',
                        $voucher->lr_no ?: ('SR-'.$voucher->sr_no),
                        0,
                        (float) $voucher->advance_paid,
                        'Advance paid on voucher',
                        $voucher->created_at,
                        'voucher-'.$voucher->id.'-advance'
                    ));
                }
            });

        SupplierPartyPayment::query()
            ->where('supplier_id', $supplier->id)
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(function ($payment) use ($entries) {
                $entries->push($this->entry(
                    $payment->payment_date?->toDateString(),
                    'Payment',
                    $payment->reference ?: ('PAY-'.$payment->id),
                    0,
                    (float) $payment->amount,
                    $this->paymentNote($payment->payment_mode, $payment->remarks),
                    $payment->created_at,
                    'party-payment-'.$payment->id
                ));
            });

        SupplierPayment::query()
            ->with('voucher:id,supplier_id,lr_no,sr_no')
            ->whereHas('voucher', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->each(function ($payment) use ($entries) {
                $ref = $payment->reference
                    ?: ($payment->voucher?->lr_no ?: ($payment->voucher?->sr_no ? 'SR-'.$payment->voucher->sr_no : 'PAY-'.$payment->id));

                $entries->push($this->entry(
                    $payment->payment_date?->toDateString(),
                    'Payment',
                    $ref,
                    0,
                    (float) $payment->amount,
                    $this->paymentNote($payment->payment_mode, $payment->remarks),
                    $payment->created_at,
                    'legacy-payment-'.$payment->id
                ));
            });

        return $this->finishLedger($entries, $previousOutstanding);
    }

    private function finishLedger(Collection $entries, float $previousOutstanding): array
    {
        $chronological = $entries
            ->sortBy(fn (array $entry) => sprintf('%s|%s|%s', $entry['date'], $entry['created_at'], $entry['key']))
            ->values();

        $running = $previousOutstanding;
        $debitTotal = 0.0;
        $creditTotal = 0.0;

        $chronological = $chronological->map(function (array $entry) use (&$running, &$debitTotal, &$creditTotal) {
            $debitTotal += $entry['debit'];
            $creditTotal += $entry['credit'];
            $running += $entry['debit'] - $entry['credit'];
            $entry['balance'] = $running;

            return $entry;
        });

        return [
            'entries' => $chronological->reverse()->values(),
            'previousOutstanding' => round($previousOutstanding, 2),
            'debitTotal' => round($debitTotal, 2),
            'creditTotal' => round($creditTotal, 2),
            'closingOutstanding' => round($running, 2),
        ];
    }

    private function entry(?string $date, string $particular, string $reference, float $debit, float $credit, ?string $remarks, mixed $createdAt, string $key): array
    {
        return [
            'date' => $date ?: now()->toDateString(),
            'particular' => $particular,
            'reference' => $reference,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'remarks' => trim((string) ($remarks ?? '')),
            'created_at' => $createdAt ? (string) $createdAt : '00:00:00',
            'key' => $key,
            'balance' => 0.0,
        ];
    }

    private function paymentNote(?string $mode, ?string $remarks): string
    {
        return collect([$mode, $remarks])->filter(fn ($v) => trim((string) $v) !== '')->implode(' · ');
    }

    private function emptyLedger(): array
    {
        return [
            'entries' => collect(),
            'previousOutstanding' => 0.0,
            'debitTotal' => 0.0,
            'creditTotal' => 0.0,
            'closingOutstanding' => 0.0,
        ];
    }

    private function filterDate(string $value, string $fallback): string
    {
        if ($value === '') {
            return $fallback;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }
}
