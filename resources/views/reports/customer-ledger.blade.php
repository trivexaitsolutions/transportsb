@extends('layouts.app')
@section('title','Customer Ledger | XYZ Transport')
@push('styles')
@include('reports._styles')
<style>
.ledger-toggle{height:2.4rem;display:flex;align-items:center;gap:.45rem;border:1px solid #94a3b8;background:#fff;padding:0 .7rem;font-size:.76rem;font-weight:900;color:#334155;white-space:nowrap}.ledger-toggle input{width:16px;height:16px;accent-color:#047857}.ledger-select{width:17px;height:17px;accent-color:#047857}.print-selected:disabled{opacity:.45;cursor:not-allowed}.voucher-row td{background:#fffdf0}.payment-row td{background:#f8fafc}.ledger-note{font-size:.7rem;color:#64748b;font-weight:700}.ledger-print-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.62);padding:18px}.ledger-print-modal.open{display:flex}.ledger-print-dialog{width:min(1120px,96vw);height:min(88vh,900px);background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35);display:flex;flex-direction:column}.ledger-print-head{height:46px;flex:0 0 46px;display:flex;align-items:center;justify-content:space-between;padding:0 12px;background:#065f46;color:#fff}.ledger-print-head strong{font-size:.9rem}.ledger-print-actions{display:flex;gap:8px}.ledger-print-actions button{border:1px solid rgba(255,255,255,.65);background:#fff;color:#064e3b;padding:6px 12px;font-weight:900;font-size:.75rem}.ledger-print-body{flex:1;min-height:0;background:#cbd5e1}.ledger-print-body iframe{width:100%;height:100%;border:0;background:#fff}
</style>
@endpush
@section('content')
<div class="app-workspace">
    <div class="report-head no-print">
        <div class="title">
            <h1 class="text-xl font-black">Customer Ledger</h1>
            <p class="text-sm text-slate-500">Customer bills, receipts and outstanding balance.</p>
        </div>
        <form method="GET" class="report-filter" id="customerLedgerFilter">
            <label>
                <span>Customer</span>
                <select name="customer_id" data-slim-select data-placeholder="Select Customer">
                    <option value="">Select Customer</option>
                    @foreach($customers as $x)
                        <option value="{{ $x->id }}" @selected($customer?->id === $x->id)>{{ $x->name }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>From</span><input type="date" name="from" value="{{ $from }}" data-ledger-date></label>
            <label><span>To</span><input type="date" name="to" value="{{ $to }}" data-ledger-date></label>
            <label class="ledger-toggle" title="Show individual payment transactions inside the ledger">
                <input type="checkbox" name="show_payments" value="1" id="showPaymentsToggle" @checked($showPayments)>
                Show Payments
            </label>
            <button class="report-btn primary" id="showLedgerBtn">Show</button>
            @if($customer && $showPayments)
                <a class="report-btn inline-flex items-center" href="#" data-ledger-print-url="{{ route('reports.customer-statement',['customer'=>$customer,'from'=>$from,'to'=>$to]) }}">Print Statement</a>
            @endif
        </form>
    </div>

    @if(!$customer)
        <div class="report-panel p-10 text-center text-slate-400">Select a customer to view ledger.</div>
    @else
        @if(!$showPayments)
        <form method="POST" target="ledgerPrintFrame" action="{{ route('reports.customer-ledger.print-selected', ['customer' => $customer]) }}" id="selectedBillsForm">
            @csrf
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">
            <input type="hidden" name="embedded" value="1">
        @endif

        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <div>
                <b>{{ $customer->name }}</b>
                <span class="text-sm text-slate-500">{{ $customer->phone }}</span>
                @if(!$showPayments)<span class="ledger-note ml-2">Select bill rows and use Print Selected.</span>@endif
            </div>
            <div class="flex items-center gap-3">
                <div class="text-sm font-bold">Opening: ₹{{ number_format($opening,2) }}</div>
                @if(!$showPayments)
                    <button type="submit" id="printSelectedBtn" class="report-btn primary print-selected" disabled>Print Selected</button>
                @endif
            </div>
        </div>

        <div class="report-panel overflow-auto">
            @if($showPayments)
                <table class="report-table">
                    <thead><tr><th>Date</th><th>Particulars</th><th>Reference</th><th>Charge</th><th>Paid</th><th>Balance</th></tr></thead>
                    <tbody>
                        <tr class="bg-slate-50"><td>{{ \Carbon\Carbon::parse($from)->format('d-m-Y') }}</td><td class="font-bold">Opening Balance</td><td>-</td><td></td><td></td><td class="num font-black">₹{{ number_format($opening,2) }}</td></tr>
                        @forelse($entries as $r)
                            <tr class="{{ ($r['type'] ?? '') === 'payment' ? 'payment-row' : 'voucher-row' }}">
                                <td>{{ \Carbon\Carbon::parse($r['date'])->format('d-m-Y') }}</td>
                                <td>{{ $r['particulars'] }}</td>
                                <td>{{ $r['reference'] ?: '-' }}</td>
                                <td class="num">{{ $r['charge'] ? ('₹'.number_format($r['charge'],2)) : '-' }}</td>
                                <td class="num">{{ $r['paid'] ? ('₹'.number_format($r['paid'],2)) : '-' }}</td>
                                <td class="num font-bold">₹{{ number_format($r['balance'],2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-slate-400">No transactions in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="report-table" id="customerVoucherLedgerTable">
                    <thead>
                        <tr>
                            <th class="text-center"><input type="checkbox" id="checkAllBills" class="ledger-select" title="Check All"></th>
                            <th>SR</th><th>Bill No</th><th>LR No</th><th>LR Date</th><th>Lorry No</th><th>From</th><th>To</th><th>Amount</th><th>Taxable Amount</th><th>GST</th><th>Total</th><th>Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $index => $r)
                            <tr class="voucher-row">
                                <td class="text-center"><input type="checkbox" class="ledger-select bill-row-check" name="voucher_ids[]" value="{{ $r['voucher_id'] }}"></td>
                                <td>{{ $index + 1 }}</td>
                                <td class="font-bold">{{ $r['bill_no'] }}</td>
                                <td>{{ $r['lr_no'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($r['lr_date'])->format('d-m-Y') }}</td>
                                <td>{{ $r['lorry_no'] }}</td>
                                <td>{{ $r['from_place'] }}</td>
                                <td>{{ $r['to_place'] }}</td>
                                <td class="num">₹{{ number_format($r['amount'],2) }}</td>
                                <td class="num">₹{{ number_format($r['taxable'],2) }}</td>
                                <td class="num">{{ rtrim(rtrim(number_format($r['gst_rate'],2,'.',''),'0'),'.') }}% · ₹{{ number_format($r['gst'],2) }}</td>
                                <td class="num font-bold">₹{{ number_format($r['total'],2) }}</td>
                                <td class="num font-bold {{ $r['voucher_outstanding'] > 0 ? 'text-red-700' : 'text-emerald-700' }}">₹{{ number_format($r['voucher_outstanding'],2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="py-8 text-center text-slate-400">No bills in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        <div class="report-summary">
            <div><small>Closing Outstanding</small><b class="{{ $closing>=0?'negative':'positive' }}">₹{{ number_format($closing,2) }}</b></div>
        </div>

        @if(!$showPayments)</form>@endif
    @endif
</div>

<div class="ledger-print-modal no-print" id="ledgerPrintModal" aria-hidden="true">
    <div class="ledger-print-dialog" role="dialog" aria-modal="true" aria-label="Ledger Print Preview">
        <div class="ledger-print-head">
            <strong>Print Preview</strong>
            <div class="ledger-print-actions">
                <button type="button" id="ledgerPrintAgain">Print</button>
                <button type="button" id="ledgerPrintClose">Close (Esc)</button>
            </div>
        </div>
        <div class="ledger-print-body"><iframe id="ledgerPrintFrame" name="ledgerPrintFrame" title="Ledger Print Preview" src="about:blank"></iframe></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filter = document.getElementById('customerLedgerFilter');
    const toggle = document.getElementById('showPaymentsToggle');
    if (toggle && filter) toggle.addEventListener('change', () => filter.submit());

    document.querySelectorAll('[data-ledger-date]').forEach((input, index, list) => {
        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace') {
                e.preventDefault();
                input.value = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const next = list[index + 1] || document.getElementById('showLedgerBtn');
                next?.focus();
            }
        });
    });

    const all = document.getElementById('checkAllBills');
    const rows = Array.from(document.querySelectorAll('.bill-row-check'));
    const printBtn = document.getElementById('printSelectedBtn');
    const update = () => {
        const checked = rows.filter(x => x.checked).length;
        if (printBtn) printBtn.disabled = checked === 0;
        if (all) {
            all.checked = rows.length > 0 && checked === rows.length;
            all.indeterminate = checked > 0 && checked < rows.length;
        }
    };
    all?.addEventListener('change', () => { rows.forEach(x => x.checked = all.checked); update(); });
    rows.forEach(x => x.addEventListener('change', update));
    update();

    const printModal = document.getElementById('ledgerPrintModal');
    const printFrame = document.getElementById('ledgerPrintFrame');
    const printClose = document.getElementById('ledgerPrintClose');
    const printAgain = document.getElementById('ledgerPrintAgain');
    let printReturnFocus = null;

    const openPrintModal = trigger => {
        printReturnFocus = trigger || document.activeElement;
        printModal?.classList.add('open');
        printModal?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };
    const closePrintModal = () => {
        printModal?.classList.remove('open');
        printModal?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (printFrame) {
            printFrame.dataset.pendingPrint = '';
            printFrame.src = 'about:blank';
        }
        setTimeout(() => printReturnFocus?.focus?.(), 0);
    };
    const handlePrintEscape = e => {
        if (e.key !== 'Escape' || !printModal?.classList.contains('open')) return;
        e.preventDefault();
        e.stopPropagation();
        closePrintModal();
    };
    const bindFrameKeyboard = () => {
        try {
            const frameWindow = printFrame?.contentWindow;
            const frameDocument = printFrame?.contentDocument;
            frameWindow?.removeEventListener('keydown', handlePrintEscape, true);
            frameDocument?.removeEventListener('keydown', handlePrintEscape, true);
            frameWindow?.addEventListener('keydown', handlePrintEscape, true);
            frameDocument?.addEventListener('keydown', handlePrintEscape, true);
        } catch (err) {
            console.warn('Unable to bind print preview keyboard handlers', err);
        }
    };
    const closeAfterNativePrint = () => {
        if (!printModal?.classList.contains('open')) return;
        setTimeout(closePrintModal, 50);
    };
    const printFrameNow = () => {
        try {
            const doc = printFrame?.contentDocument;
            const frameWindow = printFrame?.contentWindow;
            doc?.querySelectorAll('.toolbar,.print-toolbar').forEach(el => el.style.display = 'none');
            bindFrameKeyboard();
            frameWindow?.addEventListener('afterprint', closeAfterNativePrint, { once: true });
            window.addEventListener('afterprint', closeAfterNativePrint, { once: true });
            frameWindow?.focus();
            frameWindow?.print();
        } catch (err) {
            console.error('Unable to print ledger preview', err);
        }
    };

    printFrame?.addEventListener('load', () => {
        bindFrameKeyboard();
        if (printFrame.dataset.pendingPrint !== '1') return;
        setTimeout(printFrameNow, 250);
    });
    printClose?.addEventListener('click', closePrintModal);
    printAgain?.addEventListener('click', printFrameNow);
    printModal?.addEventListener('mousedown', e => { if (e.target === printModal) closePrintModal(); });
    document.addEventListener('keydown', handlePrintEscape, true);
    window.addEventListener('keydown', handlePrintEscape, true);

    document.querySelectorAll('[data-ledger-print-url]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            openPrintModal(link);
            if (printFrame) {
                printFrame.dataset.pendingPrint = '1';
                const join = link.dataset.ledgerPrintUrl.includes('?') ? '&' : '?';
                printFrame.src = link.dataset.ledgerPrintUrl + join + 'embedded=1';
            }
        });
    });

    document.getElementById('selectedBillsForm')?.addEventListener('submit', e => {
        if (!rows.some(x => x.checked)) {
            e.preventDefault();
            alert('Please select at least one bill to print.');
            return;
        }
        openPrintModal(document.getElementById('printSelectedBtn'));
        if (printFrame) printFrame.dataset.pendingPrint = '1';
    });
});
</script>
@endpush
