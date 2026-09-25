<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransaction;
use App\Models\CashTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BankTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $banks = Bank::query()
            ->with('company:id,name')
            ->orderBy('company_id')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'opening_balance', 'is_active']);
        $activeBanks = $banks->where('is_active', true)->values();
        $bankId = $request->integer('bank_id') ?: null;

        if (! $bankId && $activeBanks->count() === 1) {
            $bankId = (int) $activeBanks->first()->id;
        }

        $selectedBank = $bankId ? $banks->firstWhere('id', $bankId) : null;
        $previousBalance = 0.0;
        $currentBalance = 0.0;
        $periodCredit = 0.0;
        $periodDebit = 0.0;
        $rows = collect();

        if ($selectedBank) {
            $previousDeposit = (float) BankTransaction::query()
                ->where('bank_id', $selectedBank->id)
                ->whereDate('transaction_date', '<', $fromDate)
                ->where('type', 'deposit')
                ->sum('amount');

            $previousWithdraw = (float) BankTransaction::query()
                ->where('bank_id', $selectedBank->id)
                ->whereDate('transaction_date', '<', $fromDate)
                ->where('type', 'withdraw')
                ->sum('amount');

            $previousBalance = round((float) $selectedBank->opening_balance + $previousDeposit - $previousWithdraw, 2);

            $transactions = BankTransaction::query()
                ->where('bank_id', $selectedBank->id)
                ->whereBetween('transaction_date', [$fromDate, $toDate])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $balance = $previousBalance;
            $rows = $transactions->map(function (BankTransaction $transaction) use (&$balance, &$periodCredit, &$periodDebit) {
                $amount = round((float) $transaction->amount, 2);

                if ($transaction->type === 'deposit') {
                    $periodCredit += $amount;
                    $balance += $amount;
                } else {
                    $periodDebit += $amount;
                    $balance -= $amount;
                }

                return [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->transaction_date?->format('d-m-Y'),
                    'type' => $transaction->type,
                    'particular' => match ($transaction->source_type) {
                        'customer_party_payment' => 'Customer Payment',
                        'supplier_party_payment' => 'Supplier Payment',
                        default => $transaction->type === 'deposit' ? 'Deposit' : 'Withdrawal',
                    },
                    'debit' => $transaction->type === 'withdraw' ? $amount : 0,
                    'credit' => $transaction->type === 'deposit' ? $amount : 0,
                    'balance' => round($balance, 2),
                    'remarks' => $transaction->remarks,
                    'source_type' => $transaction->source_type,
                ];
            })->reverse()->values();

            $currentBalance = round($previousBalance + $periodCredit - $periodDebit, 2);
        }

        return view('payments.bank', compact(
            'banks',
            'activeBanks',
            'bankId',
            'selectedBank',
            'fromDate',
            'toDate',
            'rows',
            'previousBalance',
            'currentBalance',
            'periodCredit',
            'periodDebit'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_id' => ['required', 'integer', Rule::exists('banks', 'id')->where(fn ($q) => $q->where('is_active', true))],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', Rule::in(['deposit', 'withdraw'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'affect_cash_in_hand' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $affectCash = $request->boolean('affect_cash_in_hand');
            $bank = Bank::query()->with('company:id,name')->findOrFail((int) $validated['bank_id']);
            $remarks = $this->nullableText($validated['remarks'] ?? null);

            $transaction = BankTransaction::query()->create([
                'bank_id' => (int) $validated['bank_id'],
                'transaction_date' => $validated['transaction_date'],
                'type' => $validated['type'],
                'amount' => round((float) $validated['amount'], 2),
                'remarks' => $remarks,
                'affect_cash_in_hand' => $affectCash,
                'created_by' => $request->user()?->id,
            ]);

            if ($affectCash) {
                CashTransaction::query()->create([
                    'transaction_date' => $validated['transaction_date'],
                    // Bank Deposit = cash leaves Cash in Hand.
                    // Bank Withdrawal = cash comes into Cash in Hand.
                    'type' => $validated['type'] === 'deposit' ? 'withdraw' : 'deposit',
                    'amount' => round((float) $validated['amount'], 2),
                    'remarks' => trim(
                        ($validated['type'] === 'deposit' ? 'Bank Deposit' : 'Bank Withdrawal')
                        .' · '.($bank->company?->name ? $bank->company->name.' · ' : '').$bank->name
                        .($remarks ? ' · '.$remarks : '')
                    ),
                    'source_type' => 'bank_transaction',
                    'source_id' => $transaction->id,
                    'created_by' => $request->user()?->id,
                ]);
            }
        });

        return redirect()->route('payments.bank.index', [
            'bank_id' => $validated['bank_id'],
            'from_date' => $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString(),
            'to_date' => $request->string('to_date')->toString() ?: now()->toDateString(),
        ])->with('success', 'Bank transaction added successfully.');
    }

    public function destroy(BankTransaction $transaction): RedirectResponse
    {
        if ($transaction->source_type) {
            return back()->with('error', 'This bank entry was created from a Customer/Supplier Payment. Change or delete it from the Payment page.');
        }

        DB::transaction(function () use ($transaction) {
            CashTransaction::query()
                ->where('source_type', 'bank_transaction')
                ->where('source_id', $transaction->id)
                ->delete();

            $transaction->delete();
        });

        return back()->with('success', 'Bank transaction deleted successfully.');
    }

    private function filterDate(string $value, string $fallback): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
