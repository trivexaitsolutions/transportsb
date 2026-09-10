@extends('layouts.app')

@section('title', 'Voucher Entry | XYZ Transport')

@push('styles')
<style>
    .app-main{padding:0!important}
    .voucher-workspace{position:fixed;inset-inline:0;top:3.5rem;bottom:0;z-index:40;display:flex;flex-direction:column;background:#f1f5f9}
    .voucher-header{flex:0 0 auto;border-bottom:1px solid #cbd5e1;background:#fff;padding:.72rem 1.25rem;box-shadow:0 1px 2px rgba(15,23,42,.05)}
    .voucher-filter-date{height:2.5rem;width:11rem;border:1px solid #cbd5e1;background:#fffbeb;padding:0 .65rem;font-weight:800;color:#0f172a;outline:none}
    .voucher-filter-date:focus{border-color:#047857;background:#fff;box-shadow:0 0 0 2px #d1fae5}
    .status-msg{min-width:160px;text-align:right;font-size:.75rem;font-weight:700;color:#64748b}.status-msg.ok{color:#047857}.status-msg.error{color:#b91c1c}
    .voucher-help{margin-top:.55rem;display:flex;flex-wrap:wrap;gap:.2rem 1.15rem;font-size:.69rem;color:#64748b}.voucher-help strong{color:#475569}
    .voucher-grid-wrap{min-height:0;flex:1;padding:.75rem 1rem .65rem}.voucher-grid-card{display:flex;height:100%;min-height:0;flex-direction:column;border:1px solid #cbd5e1;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.05)}
    .transport-grid-scroll{min-height:0;flex:1;overflow:auto;background:#fff;position:relative}
    .app-transport-table{width:3790px;min-width:3790px;border-collapse:separate;border-spacing:0;table-layout:fixed;font-size:13px}
    .app-transport-table th{position:sticky;top:0;z-index:8;height:38px;background:#e2e8f0;border-right:1px solid #94a3b8;border-bottom:1px solid #64748b;padding:3px 5px;text-align:center;vertical-align:middle;font-size:10.5px;line-height:1.1;font-weight:900;color:#0f172a;white-space:normal;text-transform:uppercase;letter-spacing:.025em}
    .app-transport-table td{height:35px;border-right:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;background:#fff;padding:0;vertical-align:middle}
    .app-transport-table tr.is-selected td{background:#fef9c3}
    .app-transport-table input,.cell-button{width:100%;height:34px;border:0;background:transparent;padding:0 6px;font-size:13px;color:#0f172a;outline:none;border-radius:0;text-align:left}
    .app-transport-table input:focus,.cell-button:focus{box-shadow:inset 0 0 0 2px #059669;background:#ecfdf5;position:relative;z-index:3}
    .app-transport-table input[type=number]{text-align:right;font-variant-numeric:tabular-nums}
    .cell-button{display:flex;align-items:center;gap:4px;cursor:pointer;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;font-weight:700}
    .cell-button span{overflow:hidden;text-overflow:ellipsis}.cell-button.empty{color:#94a3b8;font-weight:600}.cell-button::after{content:'▾';margin-left:auto;color:#64748b;font-size:10px}
    .computed{height:34px;display:flex;align-items:center;justify-content:flex-end;padding:0 6px;background:#f8fafc;font-weight:700;font-variant-numeric:tabular-nums;color:#334155}
    .payment-button{height:34px;width:100%;border:0;background:#eff6ff;color:#1d4ed8;text-decoration:underline;text-underline-offset:2px;text-align:right;padding:0 6px;font-weight:800;cursor:pointer}.payment-button:focus{outline:2px solid #2563eb;outline-offset:-2px;background:#dbeafe}
    .row-action{height:28px;min-width:58px;border:1px solid #94a3b8;background:#fff;padding:0 7px;font-size:11px;font-weight:800}.row-action:hover,.row-action:focus{outline:none;border-color:#047857;background:#ecfdf5;color:#065f46}.row-action.delete{border-color:#fecaca;color:#b91c1c}.row-action.delete:hover,.row-action.delete:focus{background:#fef2f2;border-color:#ef4444}
    .profit-cell{background:#ecfdf5;color:#047857;font-weight:900}.negative{color:#b91c1c!important;background:#fef2f2!important}
    .sticky-sr{position:sticky;left:0;z-index:5!important}.sticky-company{position:sticky;left:62px;z-index:5!important}.app-transport-table th.sticky-sr,.app-transport-table th.sticky-company{z-index:12!important}.app-transport-table td.sticky-sr,.app-transport-table td.sticky-company{background:inherit}
    .voucher-footer{flex:0 0 auto;border-top:1px solid #cbd5e1;background:#f8fafc;padding:.55rem .8rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;font-size:.76rem;color:#475569}.voucher-footer-stats{display:flex;align-items:center;gap:1.2rem}.voucher-footer b{color:#0f172a}.footer-actions{display:flex;gap:.5rem;align-items:center}
    .toolbar-btn{height:2.35rem;padding:0 .9rem;border:1px solid #94a3b8;background:#fff;font-size:.78rem;font-weight:800;color:#334155;white-space:nowrap}.toolbar-btn:hover,.toolbar-btn:focus{background:#ecfdf5;border-color:#047857;color:#065f46;outline:none}.toolbar-btn.primary{background:#065f46;color:#fff;border-color:#065f46}.toolbar-btn.primary:hover,.toolbar-btn.primary:focus{background:#064e3b}.kbd{border:1px solid currentColor;padding:.08rem .32rem;font-size:.62rem;margin-left:.3rem;opacity:.8}
    .close-workspace{display:flex;height:2.25rem;width:2.25rem;align-items:center;justify-content:center;border:1px solid #cbd5e1;background:#fff;font-size:1.25rem;color:#475569}.close-workspace:hover,.close-workspace:focus{background:#f1f5f9;color:#0f172a;outline:none}
    .modal-backdrop{position:fixed;inset:0;z-index:200;background:rgba(15,23,42,.56);display:flex;align-items:center;justify-content:center;padding:1rem}.modal-backdrop.hidden{display:none!important}.modal-card{width:min(760px,96vw);max-height:90vh;overflow:auto;background:white;border:1px solid #64748b;box-shadow:0 20px 60px rgba(15,23,42,.35)}
    .modal-head{background:#065f46;color:white;padding:.72rem 1rem;display:flex;align-items:center;justify-content:space-between}.modal-head h3{font-weight:900}.modal-body{padding:1rem}.modal-close{font-size:1.35rem;line-height:1;border:0;background:transparent;color:white;padding:.15rem .4rem}
    .selector-search{width:100%;height:2.55rem;border:1px solid #94a3b8;padding:0 .7rem;outline:none}.selector-search:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.selector-list{margin-top:.55rem;border:1px solid #cbd5e1;max-height:360px;overflow:auto}.selector-item{display:block;width:100%;border:0;border-bottom:1px solid #e2e8f0;background:white;text-align:left;padding:.65rem .75rem}.selector-item:hover,.selector-item.active,.selector-item:focus{background:#d1fae5;outline:none}.selector-item strong{display:block;font-size:.9rem;color:#0f172a}.selector-item small{display:block;color:#64748b;margin-top:.1rem}
    .summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem;margin-bottom:.8rem}.summary-card{border:1px solid #cbd5e1;background:#f8fafc;padding:.55rem}.summary-card div:first-child{font-size:.65rem;text-transform:uppercase;font-weight:800;color:#64748b}.summary-card div:last-child{margin-top:.18rem;font-size:1rem;font-weight:900;color:#0f172a}
    .pay-table{width:100%;border-collapse:collapse;font-size:.8rem}.pay-table th,.pay-table td{border:1px solid #cbd5e1;padding:.45rem}.pay-table th{background:#f1f5f9;text-align:left}.pay-form{display:grid;grid-template-columns:1.15fr 1fr 1.15fr 1.2fr 1.4fr auto;gap:.45rem;margin-top:.8rem;align-items:end}.pay-form label{font-size:.65rem;font-weight:800;color:#64748b;text-transform:uppercase}.pay-form input,.pay-form select{width:100%;height:2.35rem;border:1px solid #94a3b8;padding:0 .45rem;background:#fff;outline:none}.pay-form input:focus,.pay-form select:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.pay-form button{height:2.35rem;background:#065f46;color:#fff;font-weight:800;padding:0 .8rem;border:0}.pay-form button:focus{outline:2px solid #fbbf24;outline-offset:2px}
    @media(max-width:900px){.voucher-workspace{top:7rem}.voucher-header{padding:.55rem .7rem}.voucher-header-main{align-items:flex-start!important;flex-direction:column}.voucher-header-left{flex-wrap:wrap}.voucher-grid-wrap{padding:.5rem}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pay-form{grid-template-columns:1fr 1fr}.pay-form .wide{grid-column:1/-1}.voucher-footer{align-items:flex-start;flex-direction:column}.voucher-footer-stats{flex-wrap:wrap}}
</style>
@endpush

@section('content')
<div id="voucherWorkspace" class="app-workspace voucher-workspace">
    <div class="voucher-header">
        <div class="voucher-header-main flex items-center justify-between gap-5">
            <div class="voucher-header-left flex items-center gap-5">
                <div>
                    <div class="text-lg font-bold text-slate-950">Voucher Entry</div>
                    <div class="mt-0.5 text-xs text-slate-500">Date-wise Excel entry for transport jobs</div>
                </div>

                <div class="h-10 w-px bg-slate-200"></div>

                <div>
                    <label for="fromDate" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">From Date</label>
                    <input type="date" id="fromDate" class="voucher-filter-date">
                </div>

                <div>
                    <label for="toDate" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">To Date</label>
                    <input type="date" id="toDate" class="voucher-filter-date">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div id="saveState" class="status-msg">No unsaved changes</div>
                <button type="button" id="closeVoucherWorkspace" class="close-workspace" title="Close Voucher (Esc)">×</button>
            </div>
        </div>

        <div class="voucher-help">
            <span><strong>From / To Date</strong> Change dates to load entries</span>
            <span><strong>Enter on empty selector</strong> Open selector</span>
            <span><strong>Enter on selected selector</strong> Next cell</span>
            <span><strong>Backspace</strong> Clear complete selection / date</span>
            <span><strong>← →</strong> Previous / next cell</span>
            <span><strong>↑ ↓</strong> Same column</span>
            <span><strong>Click Payment</strong> Installments</span>
            <span><strong>Tab</strong> Focus Save All</span>
            <span><strong>Ctrl+S</strong> Save all rows</span>
            <span><strong>Esc</strong> Close modal / page</span>
        </div>
    </div>

    <div class="voucher-grid-wrap">
        <div class="voucher-grid-card">
            <div class="transport-grid-scroll" id="gridScroll">
                <table class="app-transport-table" id="voucherTable">
                    <colgroup>
                        <col style="width:62px"><col style="width:125px"><col style="width:125px"><col style="width:120px"><col style="width:155px"><col style="width:150px"><col style="width:145px"><col style="width:145px"><col style="width:145px">
                        <col style="width:205px"><col style="width:145px"><col style="width:155px"><col style="width:135px"><col style="width:150px"><col style="width:205px"><col style="width:145px"><col style="width:135px"><col style="width:135px">
                        <col style="width:140px"><col style="width:140px"><col style="width:140px"><col style="width:135px"><col style="width:145px"><col style="width:120px"><col style="width:260px"><col style="width:80px"><col style="width:80px">
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="sticky-sr">Sr No</th>
                        <th class="sticky-company">Transport Name</th>
                        <th>LR Date</th>
                        <th>LR No</th>
                        <th>Vehicle Type</th>
                        <th>Lorry Number</th>
                        <th>SO / Ref No</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Supplier / Transporter</th>
                        <th>Supplier Freight</th>
                        <th>Advance Paid to Supplier</th>
                        <th>Balance</th>
                        <th>Supplier Payment</th>
                        <th>Customer</th>
                        <th>Customer Freight</th>
                        <th>Paid Amount</th>
                        <th>Balance</th>
                        <th>Hamali Loading</th>
                        <th>Hamali Unloading</th>
                        <th>Other Charges</th>
                        <th>Profit</th>
                        <th>Bill No</th>
                        <th>GST</th>
                        <th>Remarks</th>
                        <th>Delete</th>
                        <th>Print</th>
                    </tr>
                    </thead>
                    <tbody id="voucherBody"></tbody>
                </table>
            </div>

            <div class="voucher-footer">
                <div class="voucher-footer-stats">
                    <span>Rows: <b id="rowCount">0</b></span>
                    <span>Supplier Freight: <b id="supplierTotal">₹0.00</b></span>
                    <span>Customer Freight: <b id="customerTotal">₹0.00</b></span>
                    <span>Profit: <b id="profitTotal">₹0.00</b></span>
                </div>
                <div class="footer-actions">
                    <button type="button" class="toolbar-btn" id="addRowBtn" title="Insert">+ New Row <span class="kbd">Insert</span></button>
                    <button type="button" class="toolbar-btn" id="deleteRowBtn" title="Ctrl+Delete">Delete Row</button>
                    <button type="button" class="toolbar-btn primary" id="saveAllBtn" title="Ctrl+S">Save All <span class="kbd">Ctrl+S</span></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop hidden" id="selectorModal" role="dialog" aria-modal="true">
    <div class="modal-card">
        <div class="modal-head">
            <h3 id="selectorTitle">Select</h3>
            <button class="modal-close" type="button" id="selectorClose">×</button>
        </div>
        <div class="modal-body">
            <input class="selector-search" id="selectorSearch" type="text" autocomplete="off" placeholder="Type to search...">
            <div class="selector-list" id="selectorList"></div>
            <div class="mt-2 text-xs font-semibold text-slate-500">↑ ↓ Navigate · Enter Select · Esc Close</div>
        </div>
    </div>
</div>

<div class="modal-backdrop hidden" id="paymentModal" role="dialog" aria-modal="true">
    <div class="modal-card" style="width:min(980px,96vw)">
        <div class="modal-head">
            <h3 id="paymentTitle">Payment Installments</h3>
            <button class="modal-close" type="button" id="paymentClose">×</button>
        </div>
        <div class="modal-body">
            <div class="summary-grid">
                <div class="summary-card"><div>Voucher</div><div id="payVoucher">-</div></div>
                <div class="summary-card"><div>Total</div><div id="payTotal">₹0.00</div></div>
                <div class="summary-card"><div>Paid</div><div id="payPaid">₹0.00</div></div>
                <div class="summary-card"><div>Balance</div><div id="payBalance">₹0.00</div></div>
            </div>

            <div class="overflow-x-auto">
                <table class="pay-table">
                    <thead><tr><th>Date</th><th>Amount</th><th>Mode</th><th>Reference</th><th>Remarks</th><th></th></tr></thead>
                    <tbody id="paymentRows"></tbody>
                </table>
            </div>

            <form class="pay-form" id="paymentForm">
                <div><label for="payDate">Date</label><input type="date" id="payDate" required></div>
                <div><label for="payAmount">Amount</label><input type="number" min="0.01" step="0.01" id="payAmount" required></div>
                <div><label for="payMode">Mode</label><select id="payMode"><option>Cash</option><option>RTGS</option><option>NEFT</option><option>Bank Transfer</option><option>UPI</option><option>Cheque</option><option>Other</option></select></div>
                <div><label for="payReference">Reference</label><input type="text" id="payReference" maxlength="150"></div>
                <div><label for="payRemarks">Remarks</label><input type="text" id="payRemarks" maxlength="255"></div>
                <button type="submit" id="payAdd">Add</button>
            </form>
            <div class="mt-2 text-xs font-semibold text-slate-500">Enter = next field / Add · Esc = close and return to the same payment cell.</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrf = @json(csrf_token());
    const routes = {
        save: @json(route('vouchers.save')),
        range: @json(route('vouchers.range')),
        options: @json(route('vouchers.options')),
        base: @json(url('/vouchers')),
        printBase: @json(url('/reports/customer-bill')),
    };

    const initialRange = @json($initialRange);
    const workspace = document.getElementById('voucherWorkspace');
    const fromDateInput = document.getElementById('fromDate');
    const toDateInput = document.getElementById('toDate');
    const body = document.getElementById('voucherBody');
    const gridScroll = document.querySelector('.transport-grid-scroll');
    const saveState = document.getElementById('saveState');
    const saveButton = document.getElementById('saveAllBtn');
    const selectorModal = document.getElementById('selectorModal');
    const selectorSearch = document.getElementById('selectorSearch');
    const selectorList = document.getElementById('selectorList');
    const selectorTitle = document.getElementById('selectorTitle');
    const paymentModal = document.getElementById('paymentModal');
    const paymentRows = document.getElementById('paymentRows');
    const paymentForm = document.getElementById('paymentForm');
    const payDate = document.getElementById('payDate');
    const payAmount = document.getElementById('payAmount');
    const payMode = document.getElementById('payMode');
    const payReference = document.getElementById('payReference');
    const payRemarks = document.getElementById('payRemarks');
    const payAdd = document.getElementById('payAdd');

    let currentRange = null;
    let rows = [];
    let deletedIds = [];
    let selectedIndex = 0;
    let dirty = false;
    let selectorContext = null;
    let selectorItems = [];
    let selectorActive = 0;
    let selectorTimer = null;
    let selectorRequest = 0;
    let paymentContext = null;

    const fieldOrder = [
        'transport_company_id',
        'lr_date',
        'lr_no',
        'vehicle_type_id',
        'lorry_no',
        'so_ref_no',
        'from_place',
        'to_place',
        'supplier_id',
        'supplier_freight',
        'supplier_advance',
        'supplier_payment',
        'customer_id',
        'customer_freight',
        'customer_paid',
        'hamali_loading',
        'hamali_unloading',
        'other_charges',
        'gst_rate_id',
        'remarks',
    ];

    const selectorDefs = {
        transport_company_id: {type:'companies', title:'Select Transport Name', name:'transport_company_name'},
        vehicle_type_id: {type:'vehicle-types', title:'Select Vehicle Type', name:'vehicle_type_name'},
        supplier_id: {type:'suppliers', title:'Select Supplier / Transporter', name:'supplier_name'},
        customer_id: {type:'customers', title:'Select Customer', name:'customer_name'},
        gst_rate_id: {type:'gst-rates', title:'Select GST', name:'gst_rate_name'},
    };

    function num(value) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function normalizeNumberInput(value) {
        if (value === null || value === undefined || value === '') return '';
        const parsed = Number(value);
        if (!Number.isFinite(parsed) || parsed === 0) return '';
        return String(parsed).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
    }

    function money(value) {
        return '₹' + num(value).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({
            '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;'
        }[char]));
    }

    function blankRow() {
        return normalizeRow({
            lr_date: currentRange?.from_date || fromDateInput?.value || '',
            gst_rate_id: currentRange?.default_gst_rate_id ?? null,
            gst_rate_name: currentRange?.default_gst_rate_name || '0%',
            gst_rate: currentRange?.default_gst_rate ?? 0,
        });
    }

    function normalizeRow(row = {}) {
        return {
            id: row.id ?? null,
            sr_no: row.sr_no ?? null,
            transport_company_id: row.transport_company_id ?? null,
            transport_company_name: row.transport_company_name ?? '',
            lr_date: row.lr_date ?? currentRange?.from_date ?? '',
            lr_no: row.lr_no ?? '',
            vehicle_type_id: row.vehicle_type_id ?? null,
            vehicle_type_name: row.vehicle_type_name ?? '',
            lorry_no: row.lorry_no ?? '',
            so_ref_no: row.so_ref_no ?? '',
            from_place: row.from_place ?? '',
            to_place: row.to_place ?? '',
            supplier_id: row.supplier_id ?? null,
            supplier_name: row.supplier_name ?? '',
            supplier_freight: normalizeNumberInput(row.supplier_freight),
            supplier_advance: normalizeNumberInput(row.supplier_advance),
            supplier_payment: num(row.supplier_payment),
            supplier_balance: num(row.supplier_balance),
            customer_id: row.customer_id ?? null,
            customer_name: row.customer_name ?? '',
            customer_freight: normalizeNumberInput(row.customer_freight),
            customer_paid: num(row.customer_paid),
            customer_balance: num(row.customer_balance),
            hamali_loading: normalizeNumberInput(row.hamali_loading),
            hamali_unloading: normalizeNumberInput(row.hamali_unloading),
            other_charges: normalizeNumberInput(row.other_charges),
            profit: num(row.profit),
            bill_no: row.bill_no ?? '',
            gst_rate_id: row.gst_rate_id ?? currentRange?.default_gst_rate_id ?? null,
            gst_rate_name: row.gst_rate_name ?? currentRange?.default_gst_rate_name ?? '0%',
            gst_rate: num(row.gst_rate ?? currentRange?.default_gst_rate ?? 0),
            gst: num(row.gst),
            customer_amount: num(row.customer_amount),
            invoice_total: num(row.invoice_total),
            remarks: row.remarks ?? '',
        };
    }

    function normalizeRange(range = {}) {
        return {
            from_date: range.from_date || '',
            to_date: range.to_date || '',
            default_gst_rate_id: range.default_gst_rate_id ?? null,
            default_gst_rate_name: range.default_gst_rate_name || '0%',
            default_gst_rate: num(range.default_gst_rate),
            rows: Array.isArray(range.rows) ? range.rows : [],
        };
    }

    function activeRow(row) {
        return !!(
            row.id || row.transport_company_id || row.customer_id || row.lr_no || row.vehicle_type_id ||
            row.lorry_no || row.so_ref_no || row.from_place || row.to_place || row.supplier_id ||
            num(row.supplier_freight) || num(row.supplier_advance) || num(row.customer_freight) ||
            num(row.hamali_loading) || num(row.hamali_unloading) || num(row.other_charges) || row.remarks
        );
    }

    function recompute(row) {
        row.supplier_balance = Math.max(0, num(row.supplier_freight) - num(row.supplier_advance) - num(row.supplier_payment));
        // Loading, unloading and other charges are customer-borne but currently non-taxable.
        // GST continues to apply only on Customer Freight unless the business rule changes later.
        row.gst = Math.round((num(row.customer_freight) * num(row.gst_rate) / 100) * 100) / 100;
        row.customer_amount = num(row.customer_freight) + num(row.hamali_loading) + num(row.hamali_unloading) + num(row.other_charges);
        row.invoice_total = num(row.customer_amount) + num(row.gst);
        row.customer_balance = Math.max(0, num(row.invoice_total) - num(row.customer_paid));
        row.profit = num(row.customer_freight) - num(row.supplier_freight);
    }

    function setStatus(message, type = '') {
        saveState.textContent = message;
        saveState.className = 'status-msg' + (type ? ' ' + type : '');
    }

    function markDirty() {
        dirty = true;
        setStatus('Unsaved changes', 'error');
    }

    function markSaved(message = 'No unsaved changes') {
        dirty = false;
        setStatus(message, 'ok');
    }

    function setError(message) {
        setStatus(message, 'error');
    }

    function ensureTrailingBlank() {
        if (!rows.length || !rows.some(row => !activeRow(row))) {
            rows.push(blankRow());
        }
    }

    function inputCell(rowIndex, field, type = 'text', extra = '') {
        const value = rows[rowIndex][field] ?? '';
        return `<input data-row="${rowIndex}" data-field="${field}" data-grid-cell type="${type}" value="${esc(value)}" ${extra}>`;
    }

    function selectorCell(rowIndex, field) {
        const def = selectorDefs[field];
        const row = rows[rowIndex];
        const label = row[def.name] || 'Select';
        return `<button type="button" data-row="${rowIndex}" data-field="${field}" data-grid-cell data-selector class="cell-button ${row[field] ? '' : 'empty'}"><span>${esc(label)}</span></button>`;
    }

    function paymentCell(rowIndex, type) {
        const field = type === 'supplier' ? 'supplier_payment' : 'customer_paid';
        return `<button type="button" data-row="${rowIndex}" data-field="${field}" data-grid-cell data-payment="${type}" class="payment-button">${money(rows[rowIndex][field])}</button>`;
    }

    function computedCell(rowIndex, field, classes = '') {
        return `<div data-computed="${field}" class="computed ${classes}">${money(rows[rowIndex][field])}</div>`;
    }

    function render() {
        if (!rows.length) rows = [blankRow()];
        ensureTrailingBlank();
        rows.forEach(recompute);

        body.innerHTML = rows.map((row, index) => `
            <tr data-row-index="${index}" class="${index === selectedIndex ? 'is-selected' : ''}">
                <td class="sticky-sr"><div class="computed" style="justify-content:center">${row.sr_no ?? (index + 1)}</div></td>
                <td class="sticky-company">${selectorCell(index, 'transport_company_id')}</td>
                <td>${inputCell(index, 'lr_date', 'date')}</td>
                <td>${inputCell(index, 'lr_no')}</td>
                <td>${selectorCell(index, 'vehicle_type_id')}</td>
                <td>${inputCell(index, 'lorry_no')}</td>
                <td>${inputCell(index, 'so_ref_no')}</td>
                <td>${inputCell(index, 'from_place')}</td>
                <td>${inputCell(index, 'to_place')}</td>
                <td>${selectorCell(index, 'supplier_id')}</td>
                <td>${inputCell(index, 'supplier_freight', 'number', 'min="0" step="0.01" inputmode="decimal"')}</td>
                <td>${inputCell(index, 'supplier_advance', 'number', 'min="0" step="0.01" inputmode="decimal"')}</td>
                <td>${computedCell(index, 'supplier_balance')}</td>
                <td>${paymentCell(index, 'supplier')}</td>
                <td>${selectorCell(index, 'customer_id')}</td>
                <td>${inputCell(index, 'customer_freight', 'number', 'min="0" step="0.01" inputmode="decimal"')}</td>
                <td>${paymentCell(index, 'customer')}</td>
                <td>${computedCell(index, 'customer_balance')}</td>
                <td>${inputCell(index, 'hamali_loading', 'number', 'min="0" step="0.01" inputmode="decimal"')}</td>
                <td>${inputCell(index, 'hamali_unloading', 'number', 'min="0" step="0.01" inputmode="decimal"')}</td>
                <td>${inputCell(index, 'other_charges', 'number', 'min="0" step="0.01" inputmode="decimal"')}</td>
                <td>${computedCell(index, 'profit', 'profit-cell ' + (row.profit < 0 ? 'negative' : ''))}</td>
                <td><div class="computed" style="justify-content:flex-start">${esc(row.bill_no || 'Auto')}</div></td>
                <td>${selectorCell(index, 'gst_rate_id')}</td>
                <td>${inputCell(index, 'remarks')}</td>
                <td class="text-center"><button type="button" class="row-action delete" data-row-delete="${index}">Delete</button></td>
                <td class="text-center"><button type="button" class="row-action" data-row-print="${index}" ${row.id ? '' : 'disabled'}>Print</button></td>
            </tr>
        `).join('');

        updateTotals();
    }

    function updateTotals() {
        const data = rows.filter(activeRow);
        document.getElementById('rowCount').textContent = data.length;
        document.getElementById('supplierTotal').textContent = money(data.reduce((sum, row) => sum + num(row.supplier_freight), 0));
        document.getElementById('customerTotal').textContent = money(data.reduce((sum, row) => sum + num(row.customer_freight), 0));
        document.getElementById('profitTotal').textContent = money(data.reduce((sum, row) => sum + num(row.profit), 0));
    }

    function rowElement(rowIndex) {
        return body.querySelector(`tr[data-row-index="${rowIndex}"]`);
    }

    function cellElement(rowIndex, field) {
        return rowElement(rowIndex)?.querySelector(`[data-field="${field}"]`) || null;
    }

    function ensureCellFullyVisible(target) {
        if (!target || !gridScroll) return;

        const containerRect = gridScroll.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();
        const header = gridScroll.querySelector('thead');
        const headerHeight = header ? header.getBoundingClientRect().height : 0;
        const stickyCompany = body.querySelector('tr .sticky-company');
        const stickyRight = stickyCompany
            ? Math.max(containerRect.left, stickyCompany.getBoundingClientRect().right)
            : containerRect.left;
        const padding = 8;
        const visibleLeft = target.closest('.sticky-sr, .sticky-company')
            ? containerRect.left + padding
            : stickyRight + padding;
        const visibleRight = containerRect.right - padding;
        const visibleTop = containerRect.top + headerHeight + 2;
        const visibleBottom = containerRect.bottom - 2;

        if (!target.closest('.sticky-sr, .sticky-company')) {
            if (targetRect.left < visibleLeft) {
                gridScroll.scrollLeft -= (visibleLeft - targetRect.left);
            } else if (targetRect.right > visibleRight) {
                gridScroll.scrollLeft += (targetRect.right - visibleRight);
            }
        }

        if (targetRect.top < visibleTop) {
            gridScroll.scrollTop -= (visibleTop - targetRect.top);
        } else if (targetRect.bottom > visibleBottom) {
            gridScroll.scrollTop += (targetRect.bottom - visibleBottom);
        }
    }

    function focusCell(rowIndex, field, select = true) {
        const target = cellElement(rowIndex, field);
        if (!target) return;

        selectedIndex = rowIndex;
        target.focus({preventScroll: true});

        if (select && target instanceof HTMLInputElement && ['text', 'number'].includes(target.type)) {
            target.select();
        }

        ensureCellFullyVisible(target);
        requestAnimationFrame(() => ensureCellFullyVisible(target));
    }

    function focusFirstGridCell() {
        if (rows[0]) focusCell(0, fieldOrder[0]);
    }

    function focusNextField(rowIndex, field) {
        const fieldIndex = fieldOrder.indexOf(field);
        if (fieldIndex < 0) return;

        if (fieldIndex < fieldOrder.length - 1) {
            focusCell(rowIndex, fieldOrder[fieldIndex + 1]);
            return;
        }

        if (rowIndex === rows.length - 1) {
            rows.push(blankRow());
            render();
        }

        const nextRowIndex = Math.min(rowIndex + 1, rows.length - 1);
        if (gridScroll) gridScroll.scrollLeft = 0;
        focusCell(nextRowIndex, fieldOrder[0]);
    }

    function focusPreviousField(rowIndex, field) {
        const fieldIndex = fieldOrder.indexOf(field);
        if (fieldIndex < 0) return;

        if (fieldIndex > 0) {
            focusCell(rowIndex, fieldOrder[fieldIndex - 1]);
            return;
        }

        if (rowIndex > 0) {
            focusCell(rowIndex - 1, fieldOrder[fieldOrder.length - 1]);
        }
    }

    function focusVertical(rowIndex, field, direction) {
        let targetIndex = rowIndex + direction;

        if (direction > 0 && targetIndex >= rows.length) {
            rows.push(blankRow());
            render();
            targetIndex = rows.length - 1;
        }

        if (targetIndex < 0 || targetIndex >= rows.length) return;
        focusCell(targetIndex, field);
    }

    function refreshComputedRow(rowIndex) {
        const tr = rowElement(rowIndex);
        if (!tr) return;
        const row = rows[rowIndex];

        ['supplier_balance', 'customer_balance', 'profit'].forEach(field => {
            const element = tr.querySelector(`[data-computed="${field}"]`);
            if (element) element.textContent = money(row[field]);
        });

        const profit = tr.querySelector('[data-computed="profit"]');
        if (profit) profit.classList.toggle('negative', row.profit < 0);
    }

    function applyRange(range, message = 'Loaded') {
        currentRange = normalizeRange(range);
        fromDateInput.value = currentRange.from_date;
        toDateInput.value = currentRange.to_date;
        rows = currentRange.rows.map(normalizeRow);
        deletedIds = [];
        selectedIndex = 0;
        ensureTrailingBlank();
        render();
        dirty = false;
        setStatus(message, 'ok');
    }

    function validFilterDates() {
        return !!fromDateInput.value && !!toDateInput.value && fromDateInput.value <= toDateInput.value;
    }

    async function loadRange(focusGrid = false) {
        if (!validFilterDates()) {
            setError('Select a valid From Date and To Date.');
            return;
        }

        try {
            const url = new URL(routes.range, window.location.origin);
            url.searchParams.set('from_date', fromDateInput.value);
            url.searchParams.set('to_date', toDateInput.value);
            const response = await fetch(url, {headers:{Accept:'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || firstValidation(data.errors) || 'Unable to load voucher entries.');
            applyRange(data.range, 'Entries loaded');
            if (focusGrid) requestAnimationFrame(() => focusFirstGridCell());
        } catch (error) {
            setError(error.message || 'Unable to load voucher entries.');
        }
    }

    function requestLoadRange(focusGrid = false) {
        if (dirty && !window.confirm('Unsaved voucher changes will be lost. Load another date range?')) {
            fromDateInput.value = currentRange?.from_date || fromDateInput.value;
            toDateInput.value = currentRange?.to_date || toDateInput.value;
            return;
        }
        loadRange(focusGrid);
    }

    function clearWholeDate(input) {
        input.value = '';
        input.dispatchEvent(new Event('input', {bubbles:true}));
    }

    fromDateInput.addEventListener('keydown', event => {
        if (event.key === 'Backspace') {
            event.preventDefault();
            clearWholeDate(fromDateInput);
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            if (!fromDateInput.value) {
                if (typeof fromDateInput.showPicker === 'function') fromDateInput.showPicker();
                return;
            }
            toDateInput.focus();
            return;
        }
        if (event.key === 'ArrowRight' && fromDateInput.value) {
            return;
        }
    });

    toDateInput.addEventListener('keydown', event => {
        if (event.key === 'Backspace') {
            event.preventDefault();
            clearWholeDate(toDateInput);
            return;
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            if (!toDateInput.value) {
                if (typeof toDateInput.showPicker === 'function') toDateInput.showPicker();
                return;
            }
            requestLoadRange(true);
            return;
        }
    });

    let dateReloadTimer = null;
    [fromDateInput, toDateInput].forEach(input => {
        input.addEventListener('change', () => {
            window.clearTimeout(dateReloadTimer);
            dateReloadTimer = window.setTimeout(() => {
                if (validFilterDates()) requestLoadRange(false);
            }, 120);
        });
    });

    body.addEventListener('focusin', event => {
        const element = event.target.closest('[data-grid-cell]');
        if (!element) return;
        selectedIndex = Number(element.dataset.row);
        body.querySelectorAll('tr').forEach(tr => tr.classList.toggle('is-selected', Number(tr.dataset.rowIndex) === selectedIndex));

        if (element instanceof HTMLInputElement && ['text', 'number'].includes(element.type)) {
            element.select();
        }
    });

    body.addEventListener('input', event => {
        const element = event.target.closest('input[data-grid-cell]');
        if (!element) return;

        const rowIndex = Number(element.dataset.row);
        const row = rows[rowIndex];
        const field = element.dataset.field;
        if (!row || !field) return;

        row[field] = element.value;
        recompute(row);
        markDirty();
        updateTotals();
        refreshComputedRow(rowIndex);
    });

    body.addEventListener('click', event => {
        const deleteButton = event.target.closest('[data-row-delete]');
        if (deleteButton) {
            selectedIndex = Number(deleteButton.dataset.rowDelete);
            deleteSelected();
            return;
        }

        const printButton = event.target.closest('[data-row-print]');
        if (printButton) {
            const row = rows[Number(printButton.dataset.rowPrint)];
            if (!row?.id) {
                setError('Save this row first, then Print.');
                return;
            }
            window.open(`${routes.printBase}/${row.id}`, '_blank', 'noopener');
            return;
        }

        const selector = event.target.closest('[data-selector]');
        if (selector) {
            openSelector(Number(selector.dataset.row), selector.dataset.field);
            return;
        }

        const payment = event.target.closest('[data-payment]');
        if (payment) {
            openPayment(Number(payment.dataset.row), payment.dataset.payment);
        }
    });

    body.addEventListener('keydown', event => {
        const element = event.target.closest('[data-grid-cell]');
        if (!element) return;

        const rowIndex = Number(element.dataset.row);
        const field = element.dataset.field;
        const row = rows[rowIndex];
        if (!row || !field) return;

        if (event.key === 'ArrowLeft') {
            if (element instanceof HTMLInputElement && element.type === 'date') return;
            event.preventDefault();
            focusPreviousField(rowIndex, field);
            return;
        }

        if (event.key === 'ArrowRight') {
            if (element instanceof HTMLInputElement && element.type === 'date') return;
            event.preventDefault();
            focusNextField(rowIndex, field);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (rowIndex === 0) {
                fromDateInput.focus({preventScroll:true});
            } else {
                focusVertical(rowIndex, field, -1);
            }
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            focusVertical(rowIndex, field, 1);
            return;
        }

        if (event.key === 'Backspace' && element instanceof HTMLInputElement && element.type === 'date') {
            event.preventDefault();
            element.value = '';
            row[field] = '';
            markDirty();
            return;
        }

        if (event.key === 'Backspace' && element.matches('[data-selector]')) {
            event.preventDefault();
            clearSelector(rowIndex, field);
            return;
        }

        if (
            element.matches('[data-selector]') &&
            event.key.length === 1 &&
            !event.ctrlKey && !event.altKey && !event.metaKey
        ) {
            event.preventDefault();
            openSelector(rowIndex, field, event.key);
            return;
        }

        if (event.key !== 'Enter') return;
        event.preventDefault();

        if (element instanceof HTMLInputElement && element.type === 'date') {
            if (!element.value) {
                if (typeof element.showPicker === 'function') element.showPicker();
                return;
            }
            focusNextField(rowIndex, field);
            return;
        }

        if (element.matches('[data-selector]')) {
            if (row[field]) {
                focusNextField(rowIndex, field);
            } else {
                openSelector(rowIndex, field);
            }
            return;
        }

        // Payment amounts are click-to-open actions. Enter remains pure Excel-style navigation.
        if (element.matches('[data-payment]')) {
            focusNextField(rowIndex, field);
            return;
        }

        focusNextField(rowIndex, field);
    });

    async function openSelector(rowIndex, field, initialText = '') {
        const def = selectorDefs[field];
        if (!def) return;

        selectorContext = {row: rowIndex, field};
        selectorTitle.textContent = def.title;
        selectorSearch.value = initialText;
        selectorItems = [];
        selectorActive = 0;
        selectorList.scrollTop = 0;
        selectorModal.classList.remove('hidden');

        await loadOptions(initialText);

        if (!selectorContext || selectorContext.row !== rowIndex || selectorContext.field !== field) return;
        selectorSearch.focus();
        selectorSearch.setSelectionRange(selectorSearch.value.length, selectorSearch.value.length);
    }

    async function loadOptions(search) {
        if (!selectorContext) return;
        const requestNumber = ++selectorRequest;
        const context = {...selectorContext};
        selectorList.innerHTML = '<div class="p-4 text-sm text-slate-500">Loading...</div>';

        const query = new URLSearchParams({
            type: selectorDefs[context.field].type,
            search: search || '',
        });

        try {
            const response = await fetch(routes.options + '?' + query.toString(), {headers:{Accept:'application/json'}});
            const data = await response.json();
            if (requestNumber !== selectorRequest || !selectorContext) return;
            if (!response.ok) throw new Error(data.message || 'Unable to load list.');
            selectorItems = data.items || [];
            selectorActive = 0;
            renderOptions();
        } catch (error) {
            if (requestNumber !== selectorRequest) return;
            selectorList.innerHTML = `<div class="p-4 text-sm text-red-700">${esc(error.message || 'Unable to load list.')}</div>`;
        }
    }

    function renderOptions() {
        if (!selectorItems.length) {
            selectorList.innerHTML = '<div class="p-4 text-sm text-slate-500">No matching records.</div>';
            return;
        }

        selectorList.innerHTML = selectorItems.map((item, index) => `
            <button type="button" class="selector-item ${index === selectorActive ? 'active' : ''}" data-option-index="${index}">
                <strong>${esc(item.name)}</strong>
                ${item.subtitle ? `<small>${esc(item.subtitle)}</small>` : ''}
            </button>
        `).join('');

        selectorList.querySelector('.active')?.scrollIntoView({block:'nearest'});
    }

    function chooseSelector(index) {
        if (!selectorContext || !selectorItems[index]) return;

        const context = {...selectorContext};
        const item = selectorItems[index];
        const def = selectorDefs[context.field];
        const row = rows[context.row];
        if (!row) return;

        row[context.field] = item.id;
        row[def.name] = item.name;
        if (context.field === 'gst_rate_id') {
            row.gst_rate = num(item.rate);
        }
        recompute(row);
        markDirty();
        closeSelector(false);
        render();
        requestAnimationFrame(() => focusNextField(context.row, context.field));
    }

    function clearSelector(rowIndex, field) {
        const def = selectorDefs[field];
        const row = rows[rowIndex];
        if (!def || !row) return;

        row[field] = null;
        row[def.name] = '';
        if (field === 'gst_rate_id') row.gst_rate = 0;
        recompute(row);
        markDirty();
        render();
        requestAnimationFrame(() => focusCell(rowIndex, field, false));
    }

    function closeSelector(restoreFocus = true) {
        const context = selectorContext ? {...selectorContext} : null;
        selectorModal.classList.add('hidden');
        selectorContext = null;
        selectorItems = [];
        selectorRequest++;

        if (restoreFocus && context) {
            requestAnimationFrame(() => focusCell(context.row, context.field, false));
        }
    }

    selectorSearch.addEventListener('input', () => {
        window.clearTimeout(selectorTimer);
        selectorTimer = window.setTimeout(() => loadOptions(selectorSearch.value.trim()), 160);
    });

    selectorSearch.addEventListener('keydown', event => {
        event.stopPropagation();

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            selectorActive = Math.min(selectorActive + 1, Math.max(0, selectorItems.length - 1));
            renderOptions();
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            selectorActive = Math.max(0, selectorActive - 1);
            renderOptions();
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            chooseSelector(selectorActive);
        }
    });

    selectorList.addEventListener('click', event => {
        const button = event.target.closest('[data-option-index]');
        if (button) chooseSelector(Number(button.dataset.optionIndex));
    });

    document.getElementById('selectorClose').addEventListener('click', () => closeSelector(true));

    async function openPayment(rowIndex, type) {
        const row = rows[rowIndex];
        if (!row?.id) {
            setError('Save this voucher first, then add installments.');
            return;
        }

        paymentContext = {
            row: rowIndex,
            type,
            voucherId: row.id,
            field: type === 'supplier' ? 'supplier_payment' : 'customer_paid',
        };

        document.getElementById('paymentTitle').textContent = type === 'supplier'
            ? 'Supplier Payment Installments'
            : 'Customer Paid Amount Installments';

        paymentModal.classList.remove('hidden');
        payDate.value = row.lr_date || currentRange?.from_date || '';
        payAmount.value = '';
        payMode.value = 'Cash';
        payReference.value = '';
        payRemarks.value = '';

        await refreshPayments();
        payDate.focus();
    }

    async function refreshPayments() {
        if (!paymentContext) return;
        const context = {...paymentContext};
        const url = `${routes.base}/${context.voucherId}/payments/${context.type}`;

        try {
            const response = await fetch(url, {headers:{Accept:'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Unable to load payments.');
            if (!paymentContext || paymentContext.voucherId !== context.voucherId) return;

            const voucher = data.voucher || {};
            const row = rows[context.row];
            if (row) Object.assign(row, normalizeRow({...row, ...voucher}));

            document.getElementById('payVoucher').textContent = '#' + (voucher.sr_no || row?.sr_no || '-') + (voucher.lr_no ? ' · LR ' + voucher.lr_no : '');
            const total = context.type === 'supplier'
                ? num(voucher.supplier_freight)
                : num(voucher.invoice_total || (num(voucher.customer_freight) + num(voucher.hamali_loading) + num(voucher.hamali_unloading) + num(voucher.other_charges) + num(voucher.gst)));
            const paid = context.type === 'supplier'
                ? num(voucher.supplier_advance) + num(voucher.supplier_payment)
                : num(voucher.customer_paid);
            const balance = context.type === 'supplier' ? num(voucher.supplier_balance) : num(voucher.customer_balance);

            document.getElementById('payTotal').textContent = money(total);
            document.getElementById('payPaid').textContent = money(paid);
            document.getElementById('payBalance').textContent = money(balance);

            paymentRows.innerHTML = (data.payments || []).map(payment => `
                <tr>
                    <td>${esc(formatDate(payment.payment_date))}</td>
                    <td class="text-right font-bold">${money(payment.amount)}</td>
                    <td>${esc(payment.payment_mode || '-')}</td>
                    <td>${esc(payment.reference || '-')}</td>
                    <td>${esc(payment.remarks || '-')}</td>
                    <td class="text-center"><button type="button" data-delete-payment="${payment.id}" class="font-bold text-red-700">Delete</button></td>
                </tr>
            `).join('') || '<tr><td colspan="6" class="py-5 text-center text-slate-400">No installments yet.</td></tr>';

            render();
        } catch (error) {
            setError(error.message || 'Unable to load payments.');
        }
    }

    function focusPaymentField(element, select = false) {
        element?.focus({preventScroll:true});
        if (select && element instanceof HTMLInputElement && ['text', 'number'].includes(element.type)) element.select();
    }

    paymentForm.addEventListener('keydown', event => {
        const target = event.target;

        if (target === payDate && event.key === 'Backspace') {
            event.preventDefault();
            payDate.value = '';
            return;
        }

        if (event.key !== 'Enter') return;
        event.preventDefault();

        if (target === payDate) {
            if (payDate.value === '') {
                if (typeof payDate.showPicker === 'function') payDate.showPicker();
                else payDate.click();
                return;
            }
            focusPaymentField(payAmount, true);
            return;
        }

        if (target === payAmount) {
            if (num(payAmount.value) <= 0) {
                setError('Enter an amount greater than zero.');
                focusPaymentField(payAmount, true);
                return;
            }
            focusPaymentField(payMode);
            return;
        }

        if (target === payMode) {
            focusPaymentField(payReference, true);
            return;
        }

        if (target === payReference) {
            focusPaymentField(payRemarks, true);
            return;
        }

        if (target === payRemarks) {
            focusPaymentField(payAdd);
            return;
        }

        if (target === payAdd) {
            paymentForm.requestSubmit();
        }
    });

    paymentForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!paymentContext) return;

        if (!payDate.value) {
            setError('Select payment date.');
            payDate.focus();
            return;
        }

        if (num(payAmount.value) <= 0) {
            setError('Enter an amount greater than zero.');
            focusPaymentField(payAmount, true);
            return;
        }

        const context = {...paymentContext};
        const payload = {
            payment_date: payDate.value,
            amount: num(payAmount.value),
            payment_mode: payMode.value,
            reference: payReference.value,
            remarks: payRemarks.value,
        };

        try {
            payAdd.disabled = true;
            const response = await fetch(`${routes.base}/${context.voucherId}/payments/${context.type}`, {
                method:'POST',
                headers:{'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':csrf},
                body:JSON.stringify(payload),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || firstValidation(data.errors) || 'Unable to save payment.');

            payAmount.value = '';
            payReference.value = '';
            payRemarks.value = '';
            await refreshPayments();
            setStatus(dirty ? 'Payment saved · voucher has unsaved changes' : 'Payment saved', dirty ? 'error' : 'ok');
            focusPaymentField(payAmount, true);
        } catch (error) {
            setError(error.message || 'Unable to save payment.');
            focusPaymentField(payAmount, true);
        } finally {
            payAdd.disabled = false;
        }
    });

    paymentRows.addEventListener('click', async event => {
        const button = event.target.closest('[data-delete-payment]');
        if (!button || !paymentContext) return;
        if (!window.confirm('Delete this installment?')) return;

        const context = {...paymentContext};
        try {
            const response = await fetch(`${routes.base}/${context.voucherId}/payments/${context.type}/${button.dataset.deletePayment}`, {
                method:'DELETE',
                headers:{Accept:'application/json', 'X-CSRF-TOKEN':csrf},
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Unable to delete payment.');
            await refreshPayments();
            setStatus(dirty ? 'Payment deleted · voucher has unsaved changes' : 'Payment deleted', dirty ? 'error' : 'ok');
        } catch (error) {
            setError(error.message || 'Unable to delete payment.');
        }
    });

    function closePayment(restoreFocus = true) {
        const context = paymentContext ? {...paymentContext} : null;
        paymentModal.classList.add('hidden');
        paymentContext = null;

        if (restoreFocus && context) {
            requestAnimationFrame(() => focusCell(context.row, context.field, false));
        }
    }

    document.getElementById('paymentClose').addEventListener('click', () => closePayment(true));

    function addRow() {
        rows.push(blankRow());
        selectedIndex = rows.length - 1;
        render();
        requestAnimationFrame(() => focusCell(selectedIndex, fieldOrder[0], false));
    }

    function deleteSelected() {
        const row = rows[selectedIndex];
        if (!row) return;

        if (row.id && !window.confirm('Delete selected voucher and its payment history?')) return;
        if (row.id) deletedIds.push(row.id);

        rows.splice(selectedIndex, 1);
        if (!rows.length) rows.push(blankRow());
        selectedIndex = Math.max(0, Math.min(selectedIndex, rows.length - 1));
        markDirty();
        render();
        requestAnimationFrame(() => focusCell(selectedIndex, fieldOrder[0], false));
    }

    async function saveAll() {
        if (!currentRange || !validFilterDates()) {
            setError('Select a valid From Date and To Date.');
            fromDateInput.focus();
            return;
        }

        const payloadRows = rows.filter(activeRow).map(row => ({
            id: row.id,
            transport_company_id: row.transport_company_id,
            lr_date: row.lr_date || currentRange.from_date,
            lr_no: row.lr_no || null,
            vehicle_type_id: row.vehicle_type_id,
            lorry_no: row.lorry_no || null,
            so_ref_no: row.so_ref_no || null,
            from_place: row.from_place || null,
            to_place: row.to_place || null,
            supplier_id: row.supplier_id,
            supplier_freight: num(row.supplier_freight),
            supplier_advance: num(row.supplier_advance),
            customer_id: row.customer_id,
            customer_freight: num(row.customer_freight),
            hamali_loading: num(row.hamali_loading),
            hamali_unloading: num(row.hamali_unloading),
            other_charges: num(row.other_charges),
            gst_rate_id: row.gst_rate_id,
            remarks: row.remarks || null,
        }));

        setStatus('Saving...');

        try {
            const response = await fetch(routes.save, {
                method:'POST',
                headers:{'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN':csrf},
                body:JSON.stringify({
                    from_date: currentRange.from_date,
                    to_date: currentRange.to_date,
                    rows: payloadRows,
                    deleted_ids: deletedIds,
                }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || firstValidation(data.errors) || 'Unable to save voucher entries.');

            applyRange(data.range, data.message || 'Saved');
        } catch (error) {
            setError(error.message || 'Save failed.');
        }
    }

    function requestCloseWorkspace() {
        if (dirty && !window.confirm('Your unsaved voucher entries will be lost. Do you want to continue?')) return;

        if (window.AppPageExit) {
            window.AppPageExit('#nav-voucher');
            return;
        }

        workspace.classList.add('hidden');
        window.setTimeout(() => document.getElementById('nav-voucher')?.focus(), 0);
    }

    document.getElementById('addRowBtn').addEventListener('click', addRow);
    document.getElementById('deleteRowBtn').addEventListener('click', deleteSelected);
    saveButton.addEventListener('click', saveAll);
    document.getElementById('closeVoucherWorkspace').addEventListener('click', requestCloseWorkspace);

    window.addEventListener('keydown', event => {
        if (!paymentModal.classList.contains('hidden')) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                closePayment(true);
            }
            return;
        }

        if (!selectorModal.classList.contains('hidden')) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                closeSelector(true);
            }
            return;
        }

        if (
            event.key === 'Tab' && !event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey &&
            !workspace.classList.contains('hidden')
        ) {
            event.preventDefault();
            event.stopImmediatePropagation();
            saveButton.focus({preventScroll:true});
            saveButton.scrollIntoView({block:'nearest', inline:'nearest'});
            return;
        }

        if ((event.ctrlKey || event.metaKey) && !event.altKey && event.key.toLowerCase() === 's') {
            event.preventDefault();
            event.stopImmediatePropagation();
            saveAll();
            return;
        }

        if ((event.ctrlKey || event.metaKey) && event.key === 'Delete') {
            event.preventDefault();
            event.stopImmediatePropagation();
            deleteSelected();
            return;
        }

        if (event.key === 'Insert' && !event.ctrlKey && !event.altKey && !event.metaKey) {
            event.preventDefault();
            event.stopImmediatePropagation();
            addRow();
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopImmediatePropagation();
            requestCloseWorkspace();
        }
    }, true);

    window.addEventListener('beforeunload', event => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    function firstValidation(errors) {
        if (!errors) return '';
        const first = Object.values(errors)[0];
        return Array.isArray(first) ? first[0] : String(first || '');
    }

    function formatDate(value) {
        if (!value) return '-';
        const [year, month, day] = String(value).split('-');
        return day && month && year ? `${day}-${month}-${year}` : value;
    }

    applyRange(initialRange, 'No unsaved changes');
    window.setTimeout(() => {
        fromDateInput.focus();
    }, 0);
});
</script>
@endpush
