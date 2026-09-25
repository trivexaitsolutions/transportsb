<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\CashTransaction;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CashBookController extends Controller
{
    public function cashInHand(Request $request): View
    {
        [$fromDate, $toDate] = $this->dateRange($request);

        $previousBalance = $this->cashBalanceBefore($fromDate);

        $transactions = CashTransaction::query()
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $runningBalance = $previousBalance;
        $debitTotal = 0.0;
        $creditTotal = 0.0;
        $entries = [];

        foreach ($transactions as $transaction) {
            $amount = round((float) $transaction->amount, 2);
            $isCredit = $transaction->type === 'deposit';

            if ($isCredit) {
                $creditTotal += $amount;
                $runningBalance += $amount;
            } else {
                $debitTotal += $amount;
                $runningBalance -= $amount;
            }

            $entries[] = [
                'id' => $transaction->id,
                'date' => $transaction->transaction_date?->toDateString(),
                'particular' => $this->cashParticular($transaction),
                'reference' => $this->cashReference($transaction),
                'remarks' => $transaction->remarks,
                'debit' => $isCredit ? 0.0 : $amount,
                'credit' => $isCredit ? $amount : 0.0,
                'balance' => round($runningBalance, 2),
            ];
        }

        $closingBalance = round($runningBalance, 2);

        return view('cash.index', [
            'bookType' => 'cash',
            'title' => 'Cash in Hand',
            'subtitle' => 'Physical cash movement. Cash receipts increase balance; cash payments and bank deposits reduce balance.',
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'entries' => array_reverse($entries),
            'previousBalance' => round($previousBalance, 2),
            'debitTotal' => round($debitTotal, 2),
            'creditTotal' => round($creditTotal, 2),
            'closingBalance' => $closingBalance,
            'indexRoute' => route('cash.hand.index'),
            'companies' => collect(),
            'banks' => collect(),
            'selectedCompanyId' => null,
            'selectedBankId' => null,
        ]);
    }

    public function cashInBank(Request $request): View
    {
        [$fromDate, $toDate] = $this->dateRange($request);

        $selectedCompanyId = $request->integer('company_id') ?: null;
        $selectedBankId = $request->integer('bank_id') ?: null;

        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $banks = Bank::query()
            ->with('company:id,name')
            ->where('is_active', true)
            ->orderBy('company_id')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'account_number', 'opening_balance', 'is_default']);

        if ($selectedBankId && ! $banks->contains('id', $selectedBankId)) {
            $selectedBankId = null;
        }

        if ($selectedCompanyId && ! $companies->contains('id', $selectedCompanyId)) {
            $selectedCompanyId = null;
        }

        $selectedBanks = $banks
            ->when($selectedCompanyId, fn ($collection) => $collection->where('company_id', $selectedCompanyId))
            ->when($selectedBankId, fn ($collection) => $collection->where('id', $selectedBankId))
            ->values();

        $bankIds = $selectedBanks->pluck('id');

        $openingBalance = round((float) $selectedBanks->sum(fn ($bank) => (float) $bank->opening_balance), 2);

        $previousMovement = 0.0;
        if ($bankIds->isNotEmpty()) {
            $previousTransactions = BankTransaction::query()
                ->whereIn('bank_id', $bankIds)
                ->whereDate('transaction_date', '<', $fromDate)
                ->get(['type', 'amount']);

            foreach ($previousTransactions as $transaction) {
                $amount = (float) $transaction->amount;
                $previousMovement += $transaction->type === 'deposit' ? $amount : -$amount;
            }
        }

        $previousBalance = round($openingBalance + $previousMovement, 2);

        $transactions = BankTransaction::query()
            ->with(['bank:id,company_id,name,account_number', 'bank.company:id,name'])
            ->when($bankIds->isNotEmpty(), fn ($query) => $query->whereIn('bank_id', $bankIds))
            ->when($bankIds->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $runningBalance = $previousBalance;
        $debitTotal = 0.0;
        $creditTotal = 0.0;
        $entries = [];

        foreach ($transactions as $transaction) {
            $amount = round((float) $transaction->amount, 2);
            $isCredit = $transaction->type === 'deposit';

            if ($isCredit) {
                $creditTotal += $amount;
                $runningBalance += $amount;
            } else {
                $debitTotal += $amount;
                $runningBalance -= $amount;
            }

            $entries[] = [
                'id' => $transaction->id,
                'date' => $transaction->transaction_date?->toDateString(),
                'particular' => $this->bankParticular($transaction),
                'reference' => $this->bankReference($transaction),
                'remarks' => $transaction->remarks,
                'company' => $transaction->bank?->company?->name ?: '-',
                'bank' => $transaction->bank?->name ?: '-',
                'debit' => $isCredit ? 0.0 : $amount,
                'credit' => $isCredit ? $amount : 0.0,
                'balance' => round($runningBalance, 2),
            ];
        }

        $closingBalance = round($runningBalance, 2);

        return view('cash.index', [
            'bookType' => 'bank',
            'title' => 'Cash in Bank',
            'subtitle' => 'Bank-wise movement. Opening balance comes from Bank Master; deposits increase balance and withdrawals reduce balance.',
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'entries' => array_reverse($entries),
            'previousBalance' => $previousBalance,
            'debitTotal' => round($debitTotal, 2),
            'creditTotal' => round($creditTotal, 2),
            'closingBalance' => $closingBalance,
            'indexRoute' => route('cash.bank.index'),
            'companies' => $companies,
            'banks' => $banks,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedBankId' => $selectedBankId,
        ]);
    }

    private function cashBalanceBefore(string $fromDate): float
    {
        $transactions = CashTransaction::query()
            ->whereDate('transaction_date', '<', $fromDate)
            ->get(['type', 'amount']);

        $balance = 0.0;

        foreach ($transactions as $transaction) {
            $amount = (float) $transaction->amount;
            $balance += $transaction->type === 'deposit' ? $amount : -$amount;
        }

        return round($balance, 2);
    }

    private function cashParticular(CashTransaction $transaction): string
    {
        return match ($transaction->source_type) {
            'customer_party_payment' => 'Customer Receipt',
            'supplier_party_payment' => 'Supplier Payment',
            'bank_transaction' => $transaction->type === 'deposit' ? 'Bank Withdrawal' : 'Bank Deposit',
            default => $transaction->type === 'deposit' ? 'Cash Receipt' : 'Cash Payment',
        };
    }

    private function bankParticular(BankTransaction $transaction): string
    {
        return match ($transaction->source_type) {
            'customer_party_payment' => 'Customer Receipt',
            'supplier_party_payment' => 'Supplier Payment',
            default => $transaction->type === 'deposit' ? 'Bank Deposit' : 'Bank Withdrawal',
        };
    }

    private function cashReference(CashTransaction $transaction): string
    {
        return match ($transaction->source_type) {
            'customer_party_payment' => 'Customer Receipt #'.$transaction->source_id,
            'supplier_party_payment' => 'Supplier Payment #'.$transaction->source_id,
            'bank_transaction' => 'Bank Transaction #'.$transaction->source_id,
            default => '#'.$transaction->id,
        };
    }

    private function bankReference(BankTransaction $transaction): string
    {
        return match ($transaction->source_type) {
            'customer_party_payment' => 'Customer Receipt #'.$transaction->source_id,
            'supplier_party_payment' => 'Supplier Payment #'.$transaction->source_id,
            default => 'Bank Transaction #'.$transaction->id,
        };
    }

    private function dateRange(Request $request): array
    {
        $fromDate = $this->filterDate(
            $request->string('from_date')->toString(),
            now()->startOfMonth()->toDateString()
        );

        $toDate = $this->filterDate(
            $request->string('to_date')->toString(),
            now()->toDateString()
        );

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
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
