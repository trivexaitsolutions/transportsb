@extends('layouts.app')
@section('title', $title.' | XYZ Transport')
@section('page-nav-target', '#nav-payments')

@push('styles')
<style>
.ledger-page{min-height:calc(100vh - 56px);background:#f8fafc;padding:16px}.ledger-card{background:#fff;border:1px solid #cbd5e1}.ledger-head{display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid #cbd5e1;padding:12px 14px}.ledger-title{font-size:18px;font-weight:900}.ledger-sub{font-size:11px;font-weight:700;color:#64748b}.ledger-filter{display:grid;grid-template-columns:145px 145px minmax(260px,1fr) auto auto;gap:8px;align-items:end;padding:12px 14px;border-bottom:1px solid #cbd5e1;background:#f8fafc}.ledger-label{display:block;margin-bottom:4px;font-size:10px;font-weight:900;text-transform:uppercase;color:#475569}.ledger-input{height:36px;width:100%;border:1px solid #94a3b8;background:#fff;padding:0 9px;font-weight:800;outline:none}.ledger-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.ledger-btn{height:36px;border:1px solid #94a3b8;background:#fff;padding:0 14px;font-weight:900}.ledger-btn.primary{border-color:#08765b;background:#08765b;color:#fff}.ledger-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;padding:12px 14px;border-bottom:1px solid #cbd5e1}.ledger-summary>div{border:1px solid #cbd5e1;background:#f8fafc;padding:10px}.ledger-summary small{display:block;font-size:10px;font-weight:900;text-transform:uppercase;color:#64748b}.ledger-summary b{display:block;margin-top:2px;font-size:16px}.ledger-table-wrap{overflow:auto}.ledger-table{width:100%;min-width:980px;border-collapse:collapse;font-size:12px}.ledger-table th{background:#dfe7f1;border-right:1px solid #aebccd;border-bottom:1px solid #94a3b8;padding:7px 8px;font-size:10px;font-weight:900;text-transform:uppercase;text-align:left;white-space:nowrap}.ledger-table td{border-right:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;padding:8px;vertical-align:top}.ledger-table .money{text-align:right;font-weight:900;white-space:nowrap}.ledger-table .debit{color:#b91c1c}.ledger-table .credit{color:#047857}.ledger-table .balance{background:#f8fafc}.ledger-table .previous-row td{background:#fff7cc;font-weight:900;border-top:2px solid #c59b19}.ledger-table .empty-row td{padding:34px;text-align:center;color:#64748b;font-weight:700}.ledger-party-name{font-size:13px;font-weight:900}.ledger-note{font-size:10px;color:#64748b}.closing-due{color:#b91c1c}.closing-advance{color:#047857}@media(max-width:850px){.ledger-page{padding:8px}.ledger-filter{grid-template-columns:1fr 1fr}.ledger-filter .party-filter{grid-column:1/-1}.ledger-summary{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="app-workspace ledger-page">
    <section class="ledger-card">
        <div class="ledger-head">
            <div>
                <h1 class="ledger-title">{{ $title }}</h1>
                <p class="ledger-sub">Newest entry is shown first. Bill/Freight = Debit · Payment = Credit · Previous outstanding stays at the bottom.</p>
            </div>
            <button type="button" class="ledger-btn" onclick="window.AppPageExit?.('#nav-payments')">×</button>
        </div>

        <form method="GET" action="{{ $indexRoute }}" class="ledger-filter">
            <label>
                <span class="ledger-label">From Date</span>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="ledger-input">
            </label>
            <label>
                <span class="ledger-label">To Date</span>
                <input type="date" name="to_date" value="{{ $toDate }}" class="ledger-input">
            </label>
            <label class="party-filter">
                <span class="ledger-label">{{ $partyLabel }}</span>
                <input type="hidden" name="{{ $partyField }}" id="ledgerPartyId" value="{{ $selectedPartyId ?: '' }}">
                <button type="button" id="ledgerPartyButton" class="ledger-input text-left" data-party-selector>
                    <span id="ledgerPartyText">{{ $selectedParty ? $selectedParty->name.($selectedParty->code ? ' ('.$selectedParty->code.')' : '') : 'Select '.$partyLabel }}</span>
                </button>
            </label>
            <button class="ledger-btn primary" id="showLedgerBtn" type="submit">Show Ledger</button>
            <a href="{{ $indexRoute }}" class="ledger-btn inline-flex items-center justify-center">Reset</a>
        </form>

        @if($selectedParty)
            <div class="ledger-summary">
                <div><small>{{ $partyLabel }}</small><b class="ledger-party-name">{{ $selectedParty->name }}</b></div>
                <div><small>Previous Outstanding</small><b>₹{{ number_format(abs($previousOutstanding),2) }}{{ $previousOutstanding < 0 ? ' Cr' : '' }}</b></div>
                <div><small>Period Debit / Credit</small><b>₹{{ number_format($debitTotal,2) }} / ₹{{ number_format($creditTotal,2) }}</b></div>
                <div>
                    <small>Pending as on {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}</small>
                    <b class="{{ $closingOutstanding >= 0 ? 'closing-due' : 'closing-advance' }}">
                        ₹{{ number_format(abs($closingOutstanding),2) }} {{ $closingOutstanding >= 0 ? 'Due' : 'Advance' }}
                    </b>
                </div>
            </div>

            <div class="ledger-table-wrap">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th style="width:110px">Date</th>
                            <th style="width:170px">Particular</th>
                            <th style="width:170px">Reference</th>
                            <th>Remarks</th>
                            <th style="width:135px;text-align:right">Debit</th>
                            <th style="width:135px;text-align:right">Credit</th>
                            <th style="width:150px;text-align:right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d-m-Y') }}</td>
                                <td><b>{{ $entry['particular'] }}</b></td>
                                <td>{{ $entry['reference'] ?: '-' }}</td>
                                <td>{{ $entry['remarks'] ?: '-' }}</td>
                                <td class="money debit">{{ $entry['debit'] > 0 ? '₹'.number_format($entry['debit'],2) : '-' }}</td>
                                <td class="money credit">{{ $entry['credit'] > 0 ? '₹'.number_format($entry['credit'],2) : '-' }}</td>
                                <td class="money balance">
                                    ₹{{ number_format(abs($entry['balance']),2) }}
                                    <span class="ledger-note">{{ $entry['balance'] >= 0 ? 'Due' : 'Advance' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="7">No Debit/Credit entries found in this date range.</td></tr>
                        @endforelse
                        <tr class="previous-row">
                            <td>{{ \Carbon\Carbon::parse($fromDate)->subDay()->format('d-m-Y') }}</td>
                            <td colspan="3">Previous Outstanding</td>
                            <td class="money">-</td>
                            <td class="money">-</td>
                            <td class="money">₹{{ number_format(abs($previousOutstanding),2) }} <span class="ledger-note">{{ $previousOutstanding >= 0 ? 'Due' : 'Advance' }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-10 text-center text-sm font-bold text-slate-500">Select {{ strtolower($partyLabel) }} and click <b>Show Ledger</b>.</div>
        @endif
    </section>
</div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const partyButton = document.getElementById('ledgerPartyButton');
    const partyText = document.getElementById('ledgerPartyText');
    const partyId = document.getElementById('ledgerPartyId');
    const showLedgerBtn = document.getElementById('showLedgerBtn');
    if (!partyButton || !partyText || !partyId) return;

    const selectorType = @json($ledgerType === 'customer' ? 'customers' : 'suppliers');
    const selectorTitle = @json($ledgerType === 'customer' ? 'Select Customer' : 'Select Supplier / Transporter');
    const placeholder = @json('Select '.$partyLabel);

    function openPartySelector(search = '') {
        if (!window.MasterSelector) return;
        window.MasterSelector.open({
            type: selectorType,
            title: selectorTitle,
            opener: partyButton,
            search: search,
            allowAdd: false,
            onSelect: (item) => {
                partyId.value = item.id || '';
                partyText.textContent = item.label || item.name || placeholder;
                setTimeout(() => showLedgerBtn?.focus(), 20);
            },
        });
    }

    partyButton.addEventListener('click', () => openPartySelector());
    partyButton.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openPartySelector();
            return;
        }
        if (event.key === 'Backspace' || event.key === 'Delete') {
            event.preventDefault();
            partyId.value = '';
            partyText.textContent = placeholder;
            return;
        }
        if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
            event.preventDefault();
            openPartySelector(event.key);
        }
    });
});
</script>
@endpush
