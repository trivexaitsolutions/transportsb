@extends('layouts.app')
@section('title', 'Bank | XYZ Transport')
@section('page-nav-target', '#nav-payments')

@push('styles')
<style>
.bank-card{border:1px solid #cbd5e1;background:#fff}.bank-input{height:38px;width:100%;border:1px solid #94a3b8;background:#fff;padding:0 9px;outline:none;font-weight:700}.bank-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.bank-table{width:100%;border-collapse:collapse;font-size:12px}.bank-table th{background:#dfe7f1;border:1px solid #aebccd;padding:7px 8px;font-size:10px;font-weight:900;text-transform:uppercase}.bank-table td{border:1px solid #cbd5e1;padding:8px}.bank-table tbody tr:hover td{background:#f8fafc}.bank-num{text-align:right;font-variant-numeric:tabular-nums}.bank-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}.bank-summary>div{border:1px solid #cbd5e1;background:#f8fafc;padding:10px}.bank-summary small{display:block;font-size:10px;font-weight:900;text-transform:uppercase;color:#64748b}.bank-summary b{display:block;margin-top:2px;font-size:16px}.bank-modal{position:fixed;inset:0;z-index:230;background:rgba(15,23,42,.58);display:flex;align-items:center;justify-content:center;padding:16px}.bank-modal.hidden{display:none!important}.bank-modal-card{width:min(680px,96vw);background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}.bank-modal-head{display:flex;align-items:center;justify-content:space-between;background:#055b46;color:#fff;padding:11px 16px}.bank-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.bank-field label{display:block;margin-bottom:4px;font-size:10px;font-weight:900;text-transform:uppercase;color:#475569}.bank-field.full{grid-column:1/-1}.bank-btn{height:38px;border:1px solid #94a3b8;background:#fff;padding:0 14px;font-weight:900}.bank-btn.primary{border-color:#08765b;background:#08765b;color:#fff}.previous-row td{background:#fff9c4!important;font-weight:900}.balance-positive{color:#047857}.balance-negative{color:#b91c1c}.bank-empty{padding:45px 10px;text-align:center;color:#94a3b8;font-weight:700}@media(max-width:800px){.bank-summary{grid-template-columns:1fr 1fr}.bank-grid{grid-template-columns:1fr}.bank-field.full{grid-column:auto}}
</style>
@endpush

@section('content')
<div class="app-workspace bank-card">
    <div class="flex flex-wrap items-end gap-3 border-b border-slate-300 bg-slate-50 p-4">
        <div class="mr-auto">
            <h1 class="text-xl font-black">Bank Transactions</h1>
            <p class="mt-1 text-xs font-semibold text-slate-500">Deposit = Credit · Withdrawal = Debit · newest transaction is shown first.</p>
        </div>
        <button type="button" id="openBankTransaction" class="h-10 bg-emerald-800 px-5 text-sm font-black text-white">+ Add Transaction</button>
    </div>

    <form method="GET" action="{{ route('payments.bank.index') }}" class="flex flex-wrap items-end gap-2 border-b border-slate-300 p-4">
        <label class="w-40"><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">From Date</span><input type="date" name="from_date" value="{{ $fromDate }}" class="bank-input"></label>
        <label class="w-40"><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">To Date</span><input type="date" name="to_date" value="{{ $toDate }}" class="bank-input"></label>
        <label class="min-w-[260px] flex-1 max-w-md"><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Bank</span>
            <select name="bank_id" class="bank-input">
                <option value="">Select Bank</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}" @selected((string)$bankId === (string)$bank->id)>{{ $bank->transportName?->name ? $bank->transportName->name.' · ' : '' }}{{ $bank->name }}{{ $bank->is_active ? '' : ' (Inactive)' }}</option>
                @endforeach
            </select>
        </label>
        <button class="bank-btn primary">Show</button>
        <a href="{{ route('payments.bank.index') }}" class="bank-btn inline-flex items-center">Reset</a>
    </form>

    @if($selectedBank)
    <div class="p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div><span class="text-xs font-black uppercase text-slate-500">Bank</span><div class="text-lg font-black">{{ $selectedBank->transportName?->name ? $selectedBank->transportName->name.' · ' : '' }}{{ $selectedBank->name }}</div></div>
            <div class="text-right"><span class="text-xs font-black uppercase text-slate-500">Balance as on {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}</span><div class="text-xl font-black {{ $currentBalance < 0 ? 'balance-negative' : 'balance-positive' }}">₹{{ number_format($currentBalance,2) }}</div></div>
        </div>

        <div class="bank-summary mb-4">
            <div><small>Previous Balance</small><b>₹{{ number_format($previousBalance,2) }}</b></div>
            <div><small>Period Credit / Deposit</small><b class="text-emerald-700">₹{{ number_format($periodCredit,2) }}</b></div>
            <div><small>Period Debit / Withdrawal</small><b class="text-red-700">₹{{ number_format($periodDebit,2) }}</b></div>
            <div><small>Closing Balance</small><b class="{{ $currentBalance < 0 ? 'balance-negative' : 'balance-positive' }}">₹{{ number_format($currentBalance,2) }}</b></div>
        </div>

        <div class="overflow-auto">
            <table class="bank-table">
                <thead><tr><th style="width:55px">Sr</th><th style="width:120px">Date</th><th>Particular</th><th style="width:155px">Debit</th><th style="width:155px">Credit</th><th style="width:170px">Balance</th><th>Remarks</th><th style="width:80px">Delete</th></tr></thead>
                <tbody>
                @forelse($rows as $i => $row)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="font-bold">{{ $row['transaction_date'] }}</td>
                        <td class="font-black">{{ $row['particular'] }}</td>
                        <td class="bank-num text-red-700">{{ $row['debit'] > 0 ? '₹'.number_format($row['debit'],2) : '-' }}</td>
                        <td class="bank-num text-emerald-700">{{ $row['credit'] > 0 ? '₹'.number_format($row['credit'],2) : '-' }}</td>
                        <td class="bank-num font-black {{ $row['balance'] < 0 ? 'balance-negative' : '' }}">₹{{ number_format($row['balance'],2) }}</td>
                        <td>{{ $row['remarks'] ?: '-' }}</td>
                        <td class="text-center">@if(!empty($row['source_type']))<span class="text-[10px] font-bold text-slate-400">Auto</span>@else<form method="POST" action="{{ route('payments.bank.destroy', $row['id']) }}" onsubmit="return confirm('Delete this bank transaction?')">@csrf @method('DELETE')<button class="font-bold text-red-700 hover:underline">Delete</button></form>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="bank-empty">No bank transactions found for the selected date range.</td></tr>
                @endforelse
                    <tr class="previous-row"><td colspan="5" class="text-right">Previous Balance</td><td class="bank-num">₹{{ number_format($previousBalance,2) }}</td><td colspan="2">Before {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    @else
        <div class="bank-empty">Select a bank to view its transaction ledger. @if($banks->isEmpty())First create a bank from Masters → Bank Master.@endif</div>
    @endif
</div>

<div id="bankTransactionModal" class="bank-modal hidden" role="dialog" aria-modal="true">
    <div class="bank-modal-card">
        <div class="bank-modal-head"><h2 class="font-black">Add Bank Transaction</h2><button type="button" id="closeBankTransaction" class="text-2xl">×</button></div>
        <form id="bankTransactionForm" method="POST" action="{{ route('payments.bank.store') }}" class="p-4">@csrf
            <input type="hidden" name="from_date" value="{{ $fromDate }}">
            <input type="hidden" name="to_date" value="{{ $toDate }}">
            <div class="bank-grid">
                <div class="bank-field full"><label for="transactionBank">Bank *</label>
                    @php($defaultBankId = old('bank_id', $activeBanks->count() === 1 ? $activeBanks->first()->id : ($selectedBank?->is_active ? $selectedBank->id : '')))
                    <select id="transactionBank" name="bank_id" class="bank-input" required>
                        <option value="">Select Bank</option>
                        @foreach($activeBanks as $bank)
                            <option value="{{ $bank->id }}" @selected((string)$defaultBankId === (string)$bank->id)>{{ $bank->transportName?->name ? $bank->transportName->name.' · ' : '' }}{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="bank-field"><label for="transactionDate">Payment Date *</label><input id="transactionDate" type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="bank-input" required></div>
                <div class="bank-field"><label for="transactionType">Type *</label><select id="transactionType" name="type" class="bank-input" required><option value="deposit" @selected(old('type','deposit')==='deposit')>Deposit</option><option value="withdraw" @selected(old('type')==='withdraw')>Withdraw</option></select></div>
                <div class="bank-field"><label for="transactionAmount">Amount *</label><input id="transactionAmount" type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount') }}" class="bank-input bank-num" placeholder="0.00" required></div>
                <div class="bank-field full"><label for="transactionRemarks">Remarks</label><input id="transactionRemarks" name="remarks" value="{{ old('remarks') }}" maxlength="500" class="bank-input" autocomplete="off"></div>
            </div>
            <div class="mt-2 text-right text-xs font-semibold text-slate-500">Enter: Bank → Date → Type → Amount → Remarks → Save · Ctrl+S Save · Esc Close</div>
            <div class="mt-4 flex justify-end gap-2"><button type="button" id="cancelBankTransaction" class="bank-btn">Cancel</button><button type="submit" class="bank-btn primary">Save Transaction</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const modal=document.getElementById('bankTransactionModal'),openBtn=document.getElementById('openBankTransaction'),closeBtn=document.getElementById('closeBankTransaction'),cancelBtn=document.getElementById('cancelBankTransaction'),form=document.getElementById('bankTransactionForm');
    const bank=document.getElementById('transactionBank'),date=document.getElementById('transactionDate'),type=document.getElementById('transactionType'),amount=document.getElementById('transactionAmount'),remarks=document.getElementById('transactionRemarks');
    let opener=null;
    const fields=[bank,date,type,amount,remarks].filter(Boolean);
    function openModal(origin=openBtn){opener=origin||document.activeElement;modal.classList.remove('hidden');document.body.style.overflow='hidden';setTimeout(()=>bank?.focus(),25);}
    function closeModal(){modal.classList.add('hidden');document.body.style.overflow='';const back=opener;opener=null;setTimeout(()=>back?.focus?.(),0);}
    openBtn?.addEventListener('click',()=>openModal(openBtn));closeBtn?.addEventListener('click',closeModal);cancelBtn?.addEventListener('click',closeModal);modal?.addEventListener('mousedown',e=>{if(e.target===modal)closeModal();});
    form?.addEventListener('keydown',e=>{
        if(e.key==='Escape'){e.preventDefault();e.stopPropagation();closeModal();return;}
        if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();form.requestSubmit();return;}
        if(e.key!=='Enter')return;
        const i=fields.indexOf(e.target);if(i<0)return;e.preventDefault();
        if(e.target===date&&!date.value){date.showPicker?.();return;}
        if(i<fields.length-1){fields[i+1].focus();if(fields[i+1] instanceof HTMLInputElement)fields[i+1].select?.();}else form.requestSubmit();
    });
    @if($errors->any()) openModal(document.activeElement); @endif
});
</script>
@endpush
