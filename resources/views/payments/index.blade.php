@extends('layouts.app')
@section('title', $title.' | XYZ Transport')
@section('page-nav-target', '#nav-payments')

@push('styles')
<style>
.payment-workspace{min-height:calc(100vh - 56px);background:#f8fafc;padding:16px}.payment-panel{border:1px solid #cbd5e1;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.05)}.payment-head{display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid #cbd5e1;background:#f8fafc;padding:11px 14px}.payment-head h1{font-size:17px;font-weight:900}.payment-head p{font-size:11px;font-weight:600;color:#64748b}.payment-head-actions{display:flex;align-items:center;gap:8px}.add-payment-btn{height:34px;border:1px solid #047857;background:#08765b;color:#fff;padding:0 14px;font-size:11px;font-weight:900;white-space:nowrap}.add-payment-btn:focus{outline:none;box-shadow:0 0 0 2px #a7f3d0}.close-workspace-btn{height:34px;width:34px;border:1px solid #cbd5e1;background:#fff;font-weight:900}.history-head{display:flex;align-items:end;gap:9px;flex-wrap:wrap;padding:10px 12px;border-bottom:1px solid #cbd5e1;background:#f8fafc}.filter-field{min-width:140px}.filter-field.party-filter{min-width:250px}.filter-field label{display:block;margin-bottom:4px;font-size:10px;font-weight:900;text-transform:uppercase;color:#475569}.pay-input{width:100%;height:38px;border:1px solid #94a3b8;background:#fff;padding:0 9px;font-size:12px;font-weight:700;outline:none}.pay-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.filter-btn{height:36px;border:1px solid #94a3b8;background:#fff;padding:0 13px;font-size:11px;font-weight:900}.filter-btn.primary{border-color:#047857;background:#ecfdf5;color:#065f46}.history-summary{margin-left:auto;text-align:right;font-size:11px;font-weight:800;color:#475569}.history-summary b{display:block;font-size:16px;color:#0f172a}.payment-table-wrap{overflow:auto}.payment-table{width:100%;min-width:1050px;border-collapse:collapse;font-size:12px}.payment-table th{height:32px;border-right:1px solid #aebccd;border-bottom:1px solid #94a3b8;background:#dfe7f1;padding:5px 7px;text-align:left;font-size:10px;font-weight:900;text-transform:uppercase;white-space:nowrap}.payment-table td{height:36px;border-right:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;padding:6px 7px;font-weight:650;vertical-align:middle}.payment-table tr:hover td{background:#f8fafc}.money{text-align:right;font-weight:900!important;white-space:nowrap}.receipt-link{color:#1d4ed8;font-weight:900;text-decoration:underline}.delete-payment{border:0;background:transparent;color:#b91c1c;font-size:11px;font-weight:900}.empty-row{padding:24px!important;text-align:center;color:#94a3b8}.pagination-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 12px;background:#f8fafc}.pagination-link{border:1px solid #94a3b8;background:#fff;padding:6px 11px;font-size:11px;font-weight:800}.pagination-link.disabled{opacity:.45;pointer-events:none}

.payment-modal-backdrop{position:fixed;inset:0;z-index:240;background:rgba(15,23,42,.58);display:flex;align-items:center;justify-content:center;padding:16px}.payment-modal-backdrop.hidden{display:none!important}.payment-modal{width:min(760px,96vw);max-height:94vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}.payment-modal-head{display:flex;align-items:center;justify-content:space-between;background:#055b46;color:#fff;padding:11px 15px}.payment-modal-head h2{font-size:15px;font-weight:900}.payment-modal-close{height:28px;width:28px;border:0;background:transparent;color:#fff;font-size:21px;font-weight:900;line-height:1}.payment-modal-body{padding:14px}.payment-entry-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:10px}.pay-field.full{grid-column:1/-1}.pay-field label{display:block;margin-bottom:4px;font-size:10px;font-weight:900;text-transform:uppercase;color:#475569}.pay-field-label{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px}.pay-field-label label{margin-bottom:0}.party-outstanding{display:inline-flex;align-items:center;gap:4px;border:1px solid #f59e0b;background:#fffbeb;color:#92400e;padding:3px 7px;font-size:10px;font-weight:900;white-space:nowrap}.party-outstanding.empty{border-color:#cbd5e1;background:#f8fafc;color:#64748b}.party-outstanding b{font-size:11px}.pay-file{padding:7px 6px;font-size:11px}.payment-modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0}.modal-btn{height:36px;border:1px solid #94a3b8;background:#fff;padding:0 15px;font-size:11px;font-weight:900}.modal-btn.primary{border-color:#047857;background:#08765b;color:#fff}.payment-help{margin-top:9px;font-size:10px;font-weight:700;color:#64748b}.party-selector-btn{width:100%;height:38px;border:1px solid #94a3b8;background:#fff;padding:0 34px 0 9px;font-size:12px;font-weight:800;text-align:left;outline:none;position:relative}.party-selector-btn:after{content:'⌄';position:absolute;right:10px;top:50%;transform:translateY(-53%);color:#475569;font-size:14px}.party-selector-btn.empty{color:#64748b;font-weight:700}.party-selector-btn:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5;background:#ecfdf5}.bill-field.hidden{display:none}.bill-summary{grid-column:1/-1;display:grid;grid-template-columns:repeat(3,1fr);gap:8px;border:1px solid #cbd5e1;background:#f8fafc;padding:9px}.bill-summary.hidden{display:none}.bill-summary small{display:block;font-size:9px;font-weight:900;text-transform:uppercase;color:#64748b}.bill-summary b{font-size:13px;color:#0f172a}.validation-box{margin-bottom:12px;border:1px solid #fecaca;background:#fef2f2;padding:9px 11px;color:#991b1b;font-size:11px;font-weight:700}

@media(max-width:850px){.payment-workspace{padding:8px}.history-summary{width:100%;margin-left:0;text-align:left}.payment-entry-grid{grid-template-columns:1fr}}@media(max-width:560px){.payment-head{align-items:flex-start}.payment-head-actions{flex-wrap:wrap;justify-content:flex-end}.filter-field,.filter-field.party-filter{width:100%}}
</style>
@endpush

@section('content')
<div class="app-workspace payment-workspace" id="paymentWorkspace">
    <section class="payment-panel">
        <div class="payment-head">
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $paymentType === 'customer' ? 'Customer payment can be kept On Account or adjusted against a pending bill.' : 'Supplier payment can be kept On Account or adjusted against a pending supplier bill / LR.' }}</p>
            </div>
            <div class="payment-head-actions">
                <button type="button" id="openPaymentModal" class="add-payment-btn">+ Add Payment</button>
                <button type="button" id="closePaymentWorkspace" class="close-workspace-btn">×</button>
            </div>
        </div>

        <div class="history-head">
            <form id="paymentFilterForm" method="GET" action="{{ $indexRoute }}" class="contents">
                <div class="filter-field">
                    <label for="fromDate">From Date</label>
                    <input id="fromDate" name="from_date" type="date" value="{{ $fromDate }}" class="pay-input">
                </div>
                <div class="filter-field">
                    <label for="toDate">To Date</label>
                    <input id="toDate" name="to_date" type="date" value="{{ $toDate }}" class="pay-input">
                </div>
                <div class="filter-field party-filter">
                    <label for="filterParty">{{ $partyLabel }}</label>
                    <select id="filterParty" name="{{ $partyField }}" class="pay-input" data-slim-select data-placeholder="All {{ $partyLabel }}">
                        <option value="">All {{ $partyLabel }}</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" @selected((string) $selectedPartyId === (string) $party->id)>{{ $party->name }}{{ $party->code ? ' ('.$party->code.')' : '' }} — Outstanding ₹{{ number_format((float)($party->outstanding_amount ?? 0), 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="filter-btn primary">Apply</button>
                <a href="{{ $indexRoute }}" class="filter-btn flex items-center">Reset</a>
            </form>
            <div class="history-summary">
                Shown Payment Total
                <b>₹{{ number_format($totalAmount, 2) }}</b>
            </div>
        </div>

        <div class="payment-table-wrap">
            <table class="payment-table">
                <thead>
                    <tr>
                        <th style="width:55px">Sr</th>
                        <th style="width:115px">Date</th>
                        <th>{{ $partyLabel }}</th>
                        <th style="width:190px">Payment Against</th>
                        <th style="width:140px;text-align:right">Amount</th>
                        <th style="width:125px">Mode</th>
                        <th style="width:170px">Reference</th>
                        <th style="width:130px">Receipt</th>
                        <th>Remarks</th>
                        <th style="width:80px;text-align:center">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td class="text-center font-bold">{{ ($payments->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="whitespace-nowrap">{{ $payment->payment_date?->format('d-m-Y') }}</td>
                            <td><b>{{ $payment->{$partyRelation}?->name ?? '-' }}</b>@if($payment->{$partyRelation}?->code)<span class="ml-1 text-[10px] text-slate-500">({{ $payment->{$partyRelation}->code }})</span>@endif</td>
                            <td>
                                @if(($payment->payment_type ?? 'on_account') === 'against_bill')
                                    <b>Against Bill</b><br>
                                    <span class="text-[10px] text-slate-500">
                                        @if($paymentType === 'customer')
                                            {{ $payment->invoiceBatch?->bill_no ?: '-' }}
                                        @else
                                            {{ $payment->voucher?->lr_no ?: ($payment->voucher?->sr_no ? 'SR-'.$payment->voucher->sr_no : '-') }}
                                        @endif
                                    </span>
                                @else
                                    <b>On Account</b>
                                @endif
                            </td>
                            <td class="money">₹{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_mode ?: '-' }}</td>
                            <td>{{ $payment->reference ?: '-' }}</td>
                            <td>
                                @if($payment->attachment_path)
                                    <a class="receipt-link" href="{{ route($attachmentRouteName, $payment) }}" target="_blank" rel="noopener">View Receipt</a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td>{{ $payment->remarks ?: '-' }}</td>
                            <td class="text-center">
                                <form method="POST" action="{{ route($destroyRouteName, $payment) }}" data-delete-payment-form>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="delete-payment">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="empty-row">No payments found for the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <div class="text-[11px] font-bold text-slate-500">{{ $payments->total() }} payment(s)</div>
            <div class="flex gap-2">
                <a href="{{ $payments->previousPageUrl() ?: '#' }}" class="pagination-link {{ $payments->onFirstPage() ? 'disabled' : '' }}">← Previous</a>
                <span class="flex items-center px-2 text-[11px] font-bold">Page {{ $payments->currentPage() }} / {{ max(1, $payments->lastPage()) }}</span>
                <a href="{{ $payments->nextPageUrl() ?: '#' }}" class="pagination-link {{ $payments->hasMorePages() ? '' : 'disabled' }}">Next →</a>
            </div>
        </div>
    </section>
</div>

<div id="paymentModalBackdrop" class="payment-modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
    <div class="payment-modal">
        <div class="payment-modal-head">
            <h2 id="paymentModalTitle">Add {{ $title }}</h2>
            <button type="button" id="closePaymentModal" class="payment-modal-close" aria-label="Close">×</button>
        </div>
        <div class="payment-modal-body">
            @if($errors->any())
                <div class="validation-box">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="paymentEntryForm" method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data">
                @csrf
                <div class="payment-entry-grid">
                    <div class="pay-field">
                        <div class="pay-field-label">
                            <label for="partySelectorBtn">{{ $partyLabel }} *</label>
                            <span id="partyOutstanding" class="party-outstanding empty">Outstanding <b>—</b></span>
                        </div>
                        <input id="partyId" type="hidden" name="{{ $partyField }}" value="{{ old($partyField, $selectedPartyId) }}">
                        <select id="partyOptionsSource" class="hidden" tabindex="-1" aria-hidden="true">
                            <option value="">Select {{ $partyLabel }}</option>
                            @foreach($parties as $party)
                                <option value="{{ $party->id }}" @selected((string) old($partyField, $selectedPartyId) === (string) $party->id)>{{ $party->name }}{{ $party->code ? ' ('.$party->code.')' : '' }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="partySelectorBtn" class="party-selector-btn empty" data-party-selector data-party-type="{{ $paymentType === 'customer' ? 'customers' : 'suppliers' }}" data-party-title="Select {{ $partyLabel }}">
                            Select {{ $partyLabel }}
                        </button>
                    </div>
                    <div class="pay-field">
                        <label for="paymentAgainst">Payment Against *</label>
                        <select id="paymentAgainst" name="payment_type" class="pay-input" required>
                            <option value="on_account" @selected(old('payment_type', 'on_account') === 'on_account')>On Account</option>
                            <option value="against_bill" @selected(old('payment_type') === 'against_bill')>Against Bill</option>
                        </select>
                    </div>
                    <div id="billFieldWrap" class="pay-field full bill-field hidden">
                        <label for="allocationId">{{ $paymentType === 'customer' ? 'Pending Bill' : 'Pending Supplier Bill / LR' }} *</label>
                        <select id="allocationId" name="{{ $paymentType === 'customer' ? 'invoice_batch_id' : 'voucher_id' }}" class="pay-input">
                            <option value="">{{ $paymentType === 'customer' ? 'Select Pending Bill' : 'Select Pending Supplier Bill / LR' }}</option>
                        </select>
                    </div>
                    <div id="billSummary" class="bill-summary hidden">
                        <div><small>{{ $paymentType === 'customer' ? 'Bill Amount' : 'Supplier Freight' }}</small><b id="billTotal">₹0.00</b></div>
                        <div><small>Already Paid</small><b id="billPaid">₹0.00</b></div>
                        <div><small>Pending</small><b id="billPending">₹0.00</b></div>
                    </div>
                    <div class="pay-field">
                        <label for="paymentDate">Payment Date *</label>
                        <input id="paymentDate" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="pay-input" required>
                    </div>
                    <div class="pay-field">
                        <label for="paymentAmount">Amount *</label>
                        <input id="paymentAmount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" class="pay-input money" placeholder="0.00" required>
                    </div>
                    <div class="pay-field">
                        <label for="paymentMode">Payment Mode</label>
                        <select id="paymentMode" name="payment_mode" class="pay-input">
                            <option value="">Select Mode</option>
                            @foreach(['Cash','NEFT','RTGS','UPI','Cheque','Bank Transfer','Other'] as $mode)
                                <option value="{{ $mode }}" @selected(old('payment_mode') === $mode)>{{ $mode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pay-field">
                        <label for="paymentReference">Reference / Txn No.</label>
                        <input id="paymentReference" name="reference" value="{{ old('reference') }}" maxlength="150" class="pay-input" autocomplete="off">
                    </div>
                    <div class="pay-field full">
                        <label for="paymentAttachment">Receipt / Attachment</label>
                        <input id="paymentAttachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="pay-input pay-file">
                    </div>
                    <div class="pay-field full">
                        <label for="paymentRemarks">Remarks</label>
                        <input id="paymentRemarks" name="remarks" value="{{ old('remarks') }}" maxlength="255" class="pay-input" autocomplete="off">
                    </div>
                </div>

                <div class="payment-help">Enter: {{ $paymentType === 'customer' ? 'Customer' : 'Supplier' }} → Payment Against → Bill/LR (when required) → Date → Amount → Mode → Reference → Attachment → Remarks → Save · Esc Close · Ctrl+S Save · Receipt supports PDF/JPG/PNG/WEBP up to 10 MB.</div>

                <div class="payment-modal-actions">
                    <button type="button" id="cancelPaymentModal" class="modal-btn">Cancel</button>
                    <button type="submit" id="savePaymentBtn" class="modal-btn primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('paymentModalBackdrop');
    const openBtn = document.getElementById('openPaymentModal');
    const closeBtn = document.getElementById('closePaymentModal');
    const cancelBtn = document.getElementById('cancelPaymentModal');
    const form = document.getElementById('paymentEntryForm');
    const partyInput = document.getElementById('partyId');
    const partyBtn = document.getElementById('partySelectorBtn');
    const partyOptionsSource = document.getElementById('partyOptionsSource');
    const partyType = partyBtn?.dataset.partyType || '';
    const partyTitle = partyBtn?.dataset.partyTitle || 'Select';
    const date = document.getElementById('paymentDate');
    const amount = document.getElementById('paymentAmount');
    const mode = document.getElementById('paymentMode');
    const reference = document.getElementById('paymentReference');
    const attachment = document.getElementById('paymentAttachment');
    const remarks = document.getElementById('paymentRemarks');
    const save = document.getElementById('savePaymentBtn');
    const isCustomerPayment = @json($paymentType === 'customer');
    const pendingAllocations = isCustomerPayment ? @json($pendingBills ?? []) : @json($pendingSupplierBills ?? []);
    const partyOutstandings = isCustomerPayment ? @json($customerOutstandings ?? []) : @json($supplierOutstandings ?? []);
    const partyOutstanding = document.getElementById('partyOutstanding');
    const paymentAgainst = document.getElementById('paymentAgainst');
    const billFieldWrap = document.getElementById('billFieldWrap');
    const allocationId = document.getElementById('allocationId');
    const billSummary = document.getElementById('billSummary');
    const billTotal = document.getElementById('billTotal');
    const billPaid = document.getElementById('billPaid');
    const billPending = document.getElementById('billPending');
    const oldAllocationId = isCustomerPayment ? @json((string) old('invoice_batch_id', '')) : @json((string) old('voucher_id', ''));
    let modalOpener = null;

    const focusParty = () => partyBtn?.focus();
    const money = value => '₹' + Number(value || 0).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    const partyKey = isCustomerPayment ? 'customer_id' : 'supplier_id';

    const selectedBill = () => pendingAllocations.find(item => String(item.id) === String(allocationId?.value || ''));

    const updateBillSummary = () => {
        if (!billSummary) return;
        const bill = selectedBill();
        if (!bill) {
            billSummary.classList.add('hidden');
            amount?.removeAttribute('max');
            if (amount) amount.placeholder = '0.00';
            return;
        }
        billSummary.classList.remove('hidden');
        billTotal.textContent = money(bill.total_amount);
        billPaid.textContent = money(bill.paid_amount);
        billPending.textContent = money(bill.pending_amount);
        amount.max = Number(bill.pending_amount).toFixed(2);
        amount.placeholder = 'Max ' + money(bill.pending_amount);
    };

    const refreshBillOptions = (preferredId = '') => {
        if (!allocationId) return;
        const selectedParty = String(partyInput?.value || '');
        const options = pendingAllocations.filter(item => String(item[partyKey]) === selectedParty);
        allocationId.replaceChildren();
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = options.length
            ? (isCustomerPayment ? 'Select Pending Bill' : 'Select Pending Supplier Bill / LR')
            : (isCustomerPayment ? 'No pending bills for this customer' : 'No pending bills / LR for this supplier');
        allocationId.appendChild(placeholder);
        options.forEach(item => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = `${item.bill_no} | ${item.invoice_date || '-'} | Pending ${money(item.pending_amount)}`;
            allocationId.appendChild(option);
        });
        const wanted = String(preferredId || '');
        if (wanted && options.some(item => String(item.id) === wanted)) allocationId.value = wanted;
        updateBillSummary();
    };

    const refreshPaymentAgainst = (preferredId = '') => {
        if (!paymentAgainst || !billFieldWrap || !allocationId) return;
        const againstBill = paymentAgainst.value === 'against_bill';
        billFieldWrap.classList.toggle('hidden', !againstBill);
        allocationId.required = againstBill;
        if (againstBill) {
            refreshBillOptions(preferredId || allocationId.value);
        } else {
            allocationId.value = '';
            billSummary?.classList.add('hidden');
            amount?.removeAttribute('max');
            if (amount) amount.placeholder = '0.00';
        }
    };

    const updatePartyOutstanding = id => {
        if (!partyOutstanding) return;
        const hasParty = !!id;
        const value = hasParty ? Number(partyOutstandings[String(id)] || 0) : 0;
        partyOutstanding.classList.toggle('empty', !hasParty);
        const amountNode = partyOutstanding.querySelector('b');
        if (amountNode) amountNode.textContent = hasParty ? money(value) : '—';
    };

    const setParty = (id, label) => {
        partyInput.value = id || '';
        partyBtn.textContent = label || partyTitle;
        partyBtn.classList.toggle('empty', !id);
        if (partyOptionsSource) partyOptionsSource.value = id || '';
        updatePartyOutstanding(id);
        refreshPaymentAgainst();
    };

    const syncInitialParty = () => {
        const id = partyInput?.value || '';
        if (!id || !partyOptionsSource) {
            setParty('', '');
            return;
        }
        const option = Array.from(partyOptionsSource.options).find(item => String(item.value) === String(id));
        setParty(id, option?.textContent?.trim() || partyTitle);
    };

    const openPartySelector = (search = '') => {
        window.MasterSelector.open({
            type: partyType,
            title: partyTitle,
            search,
            opener: partyBtn,
            allowAdd: false,
            onSelect: item => {
                setParty(item.id, item.label || item.name);
                setTimeout(() => paymentAgainst?.focus(), 20);
            },
        });
    };

    const openModal = (opener = openBtn) => {
        modalOpener = opener;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(focusParty, 30);
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        setTimeout(() => modalOpener?.focus?.(), 0);
    };

    syncInitialParty();
    refreshPaymentAgainst(oldAllocationId);

    paymentAgainst?.addEventListener('change', () => {
        refreshPaymentAgainst();
        setTimeout(() => paymentAgainst.value === 'against_bill' ? allocationId?.focus() : date?.focus(), 0);
    });
    allocationId?.addEventListener('change', updateBillSummary);

    openBtn?.addEventListener('click', () => openModal(openBtn));
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal?.addEventListener('mousedown', event => {
        if (event.target === modal) closeModal();
    });

    partyBtn?.addEventListener('click', () => openPartySelector());
    partyBtn?.addEventListener('keydown', event => {
        if (event.key === 'Backspace') {
            event.preventDefault();
            setParty('', '');
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            if (partyInput.value) paymentAgainst?.focus();
            else openPartySelector();
            return;
        }
        if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
            event.preventDefault();
            openPartySelector(event.key);
        }
    });

    const navigationFields = [paymentAgainst, allocationId, date, amount, mode, reference, attachment, remarks, save].filter(Boolean);
    const visibleOrder = () => navigationFields.filter(el => el.offsetParent !== null && !el.disabled);
    navigationFields.forEach(el => el.addEventListener('keydown', event => {
        if (event.key === 'Backspace' && el instanceof HTMLInputElement && el.type === 'date') {
            event.preventDefault();
            el.value = '';
            return;
        }
        if (event.key !== 'Enter') return;
        if (el === attachment) return;
        event.preventDefault();
        if (el === date && !date.value) {
            date.showPicker?.();
            return;
        }
        const order = visibleOrder();
        const index = order.indexOf(el);
        if (index >= 0 && index < order.length - 1) {
            order[index + 1]?.focus();
            if (order[index + 1] instanceof HTMLInputElement && ['text','number'].includes(order[index + 1].type)) order[index + 1].select?.();
        } else {
            form.requestSubmit();
        }
    }));

    window.addEventListener('keydown', event => {
        if (!modal.classList.contains('hidden')) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                closeModal();
                return;
            }
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                form.requestSubmit();
            }
            return;
        }
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            openModal(openBtn);
        }
    });

    document.querySelectorAll('[data-delete-payment-form]').forEach(deleteForm => {
        deleteForm.addEventListener('submit', event => {
            if (!confirm('Delete this payment entry?')) event.preventDefault();
        });
    });

    document.getElementById('closePaymentWorkspace')?.addEventListener('click', () => window.AppPageExit?.('#nav-payments'));

    @if($errors->any())
        openModal(openBtn);
    @endif
});
</script>
@endpush
