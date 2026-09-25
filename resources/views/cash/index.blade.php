@extends('layouts.app')
@section('title', $title.' | XYZ Transport')
@section('page-nav-target', '#nav-payments')

@push('styles')
<style>
.cash-page{min-height:calc(100vh - 56px);background:#f8fafc;padding:16px}
.cash-card{background:#fff;border:1px solid #cbd5e1}
.cash-head{display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid #cbd5e1;padding:12px 14px}
.cash-title{font-size:18px;font-weight:900}.cash-sub{font-size:11px;font-weight:700;color:#64748b}
.cash-filter{display:grid;grid-template-columns:145px 145px repeat(2,minmax(180px,1fr)) auto auto;gap:8px;align-items:end;padding:12px 14px;border-bottom:1px solid #cbd5e1;background:#f8fafc}
.cash-label{display:block;margin-bottom:4px;font-size:10px;font-weight:900;text-transform:uppercase;color:#475569}
.cash-input{height:36px;width:100%;border:1px solid #94a3b8;background:#fff;padding:0 9px;font-weight:800;outline:none}
.cash-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}
.cash-btn{height:36px;border:1px solid #94a3b8;background:#fff;padding:0 14px;font-weight:900}.cash-btn.primary{border-color:#08765b;background:#08765b;color:#fff}
.cash-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;padding:12px 14px;border-bottom:1px solid #cbd5e1}
.cash-summary>div{border:1px solid #cbd5e1;background:#f8fafc;padding:10px}.cash-summary small{display:block;font-size:10px;font-weight:900;text-transform:uppercase;color:#64748b}.cash-summary b{display:block;margin-top:2px;font-size:16px}
.cash-table-wrap{overflow:auto}.cash-table{width:100%;min-width:1000px;border-collapse:collapse;font-size:12px}
.cash-table th{background:#dfe7f1;border-right:1px solid #aebccd;border-bottom:1px solid #94a3b8;padding:7px 8px;font-size:10px;font-weight:900;text-transform:uppercase;text-align:left;white-space:nowrap}
.cash-table td{border-right:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;padding:8px;vertical-align:top}
.cash-table .money{text-align:right;font-weight:900;white-space:nowrap}.cash-table .debit{color:#b91c1c}.cash-table .credit{color:#047857}.cash-table .balance{background:#f8fafc}
.cash-table .total-row td{background:#eef6ff;font-weight:900;border-top:2px solid #64748b}.cash-table .previous-row td{background:#fff7cc;font-weight:900;border-top:1px solid #c59b19}
.cash-table .empty-row td{padding:34px;text-align:center;color:#64748b;font-weight:700}
.cash-note{font-size:10px;color:#64748b}.closing-positive{color:#047857}.closing-negative{color:#b91c1c}
@media(max-width:1000px){.cash-page{padding:8px}.cash-filter{grid-template-columns:1fr 1fr}.cash-filter .wide{grid-column:1/-1}.cash-summary{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="app-workspace cash-page">
    <section class="cash-card">
        <div class="cash-head">
            <div>
                <h1 class="cash-title">{{ $title }}</h1>
                <p class="cash-sub">{{ $subtitle }}</p>
            </div>
            <button type="button" class="cash-btn" onclick="window.AppPageExit?.('#nav-payments')">×</button>
        </div>

        <form method="GET" action="{{ $indexRoute }}" class="cash-filter">
            <label>
                <span class="cash-label">From Date</span>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="cash-input">
            </label>
            <label>
                <span class="cash-label">To Date</span>
                <input type="date" name="to_date" value="{{ $toDate }}" class="cash-input">
            </label>

            @if($bookType === 'bank')
                <label class="wide">
                    <span class="cash-label">Company</span>
                    <select name="company_id" class="cash-input" onchange="this.form.submit()">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((int)$selectedCompanyId === (int)$company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="wide">
                    <span class="cash-label">Bank</span>
                    <select name="bank_id" class="cash-input">
                        <option value="">All Banks</option>
                        @foreach($banks as $bank)
                            @if(!$selectedCompanyId || (int)$bank->company_id === (int)$selectedCompanyId)
                                <option value="{{ $bank->id }}" @selected((int)$selectedBankId === (int)$bank->id)>
                                    {{ $bank->company?->name ? $bank->company->name.' · ' : '' }}{{ $bank->name }}
                                    @if($bank->account_number) · {{ $bank->account_number }} @endif
                                    @if($bank->is_default) · Default @endif
                                </option>
                            @endif
                        @endforeach
                    </select>
                </label>
            @else
                <div></div><div></div>
            @endif

            <button class="cash-btn primary" type="submit">Show</button>
            <a href="{{ $indexRoute }}" class="cash-btn inline-flex items-center justify-center">Reset</a>
        </form>

        <div class="cash-summary">
            <div>
                <small>Previous Balance</small>
                <b class="{{ $previousBalance >= 0 ? 'closing-positive' : 'closing-negative' }}">
                    ₹{{ number_format(abs($previousBalance),2) }}{{ $previousBalance < 0 ? ' Cr/Negative' : '' }}
                </b>
            </div>
            <div><small>Period Debit</small><b>₹{{ number_format($debitTotal,2) }}</b></div>
            <div><small>Period Credit</small><b>₹{{ number_format($creditTotal,2) }}</b></div>
            <div>
                <small>Balance as on {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}</small>
                <b class="{{ $closingBalance >= 0 ? 'closing-positive' : 'closing-negative' }}">
                    ₹{{ number_format(abs($closingBalance),2) }}{{ $closingBalance < 0 ? ' Negative' : '' }}
                </b>
            </div>
        </div>

        <div class="cash-table-wrap">
            <table class="cash-table">
                <thead>
                    <tr>
                        <th style="width:105px">Date</th>
                        @if($bookType === 'bank')
                            <th style="width:110px">Company</th>
                            <th style="width:150px">Bank</th>
                        @endif
                        <th style="width:170px">Particular</th>
                        <th style="width:180px">Reference</th>
                        <th>Remarks</th>
                        <th style="width:135px;text-align:right">Debit</th>
                        <th style="width:135px;text-align:right">Credit</th>
                        <th style="width:150px;text-align:right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d-m-Y') }}</td>
                            @if($bookType === 'bank')
                                <td><b>{{ $entry['company'] }}</b></td>
                                <td>{{ $entry['bank'] }}</td>
                            @endif
                            <td><b>{{ $entry['particular'] }}</b></td>
                            <td>{{ $entry['reference'] ?: '-' }}</td>
                            <td>{{ $entry['remarks'] ?: '-' }}</td>
                            <td class="money debit">{{ $entry['debit'] > 0 ? '₹'.number_format($entry['debit'],2) : '-' }}</td>
                            <td class="money credit">{{ $entry['credit'] > 0 ? '₹'.number_format($entry['credit'],2) : '-' }}</td>
                            <td class="money balance">₹{{ number_format($entry['balance'],2) }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="{{ $bookType === 'bank' ? 9 : 7 }}">No transactions found in this date range.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="{{ $bookType === 'bank' ? 5 : 3 }}">TOTAL</td>
                        <td class="money debit">₹{{ number_format($debitTotal,2) }}</td>
                        <td class="money credit">₹{{ number_format($creditTotal,2) }}</td>
                        <td class="money">-</td>
                    </tr>
                    <tr class="previous-row">
                        <td>{{ \Carbon\Carbon::parse($fromDate)->subDay()->format('d-m-Y') }}</td>
                        <td colspan="{{ $bookType === 'bank' ? 6 : 4 }}">Previous Balance</td>
                        <td class="money">-</td>
                        <td class="money">₹{{ number_format($previousBalance,2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>
@endsection
