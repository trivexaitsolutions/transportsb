@extends('layouts.app')
@section('title', 'Voucher Entry | XYZ Transport')
@section('page-nav-target', '#nav-sale')

@push('styles')
<style>
.app-main{padding:0!important}.voucher-workspace{height:calc(100vh - 56px);display:flex;flex-direction:column;background:#fff}.voucher-head{padding:10px 16px 8px;border-bottom:1px solid #cbd5e1;background:#f8fafc}.voucher-grid-wrap{flex:1;overflow:auto;border-bottom:1px solid #cbd5e1;position:relative}.voucher-grid{border-collapse:collapse;min-width:2510px;width:100%;font-size:12px;table-layout:fixed}.voucher-grid th{position:sticky;top:0;z-index:4;background:#dfe7f1;border-right:1px solid #aebccd;border-bottom:1px solid #94a3b8;height:34px;padding:4px 6px;text-align:center;font-size:10px;font-weight:900;text-transform:uppercase}.voucher-grid td{height:34px;border-right:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;padding:0;background:#fff}.voucher-grid tr.is-selected td{background:#fff9c4}.voucher-grid tr.is-selected .computed{background:#f8fafc}.grid-input,.grid-button{width:100%;height:33px;border:0;background:transparent;padding:0 7px;outline:none;font-weight:700;text-align:left}.grid-input:focus,.grid-button:focus{box-shadow:inset 0 0 0 2px #059669;background:#ecfdf5}.grid-button.empty{color:#64748b;font-weight:600}.numeric{text-align:right}.computed{height:33px;display:flex;align-items:center;justify-content:flex-end;padding:0 7px;font-weight:800;background:#f8fafc;white-space:nowrap}.customer-cell{height:33px;display:flex;align-items:center;padding:0 7px;font-weight:800;background:#f8fafc;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.payment-button{width:100%;height:33px;border:0;background:#eff6ff;color:#1d4ed8;text-decoration:underline;font-weight:900;text-align:right;padding:0 7px;outline:none}.payment-button:focus{box-shadow:inset 0 0 0 2px #2563eb}.voucher-foot{height:52px;display:flex;align-items:center;padding:7px 16px;background:#f8fafc;gap:14px}.status-msg{font-size:11px;font-weight:800;color:#64748b}.status-msg.ok{color:#047857}.status-msg.error{color:#b91c1c}.totals{display:flex;gap:18px;font-size:11px;font-weight:800;color:#475569}.totals b{color:#0f172a}.voucher-actions{margin-left:auto;display:flex;gap:8px}.vbtn{height:36px;border:1px solid #94a3b8;background:#fff;padding:0 14px;font-weight:900}.vbtn-primary{background:#08765b;color:#fff;border-color:#08765b}.date-input{height:36px;border:1px solid #94a3b8;background:#fff;padding:0 9px;font-weight:800;outline:none}.date-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.modal-backdrop{position:fixed;inset:0;z-index:240;background:rgba(15,23,42,.58);display:flex;align-items:center;justify-content:center;padding:16px}.modal-backdrop.hidden{display:none!important}.payment-card{width:min(860px,97vw);max-height:94vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}.payment-head{display:flex;align-items:center;justify-content:space-between;background:#055b46;color:#fff;padding:11px 16px}.payment-input{height:38px;border:1px solid #94a3b8;padding:0 8px;outline:none;width:100%}.payment-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.pay-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}.pay-summary>div{border:1px solid #cbd5e1;background:#f8fafc;padding:9px}.pay-summary small{display:block;font-size:10px;font-weight:900;color:#64748b;text-transform:uppercase}.pay-summary b{font-size:15px}.payment-table{width:100%;border-collapse:collapse;font-size:12px}.payment-table th,.payment-table td{border:1px solid #cbd5e1;padding:7px;text-align:left}.payment-table th{background:#e9eef5;font-size:10px;text-transform:uppercase}.row-delete{border:0;background:transparent;color:#b91c1c;font-weight:900}.sr-cell{text-align:center;font-weight:800;background:#f8fafc!important}
</style>
@endpush

@section('content')
<div class="app-workspace voucher-workspace" id="voucherWorkspace">
    <div class="voucher-head">
        <div class="flex flex-wrap items-end gap-4">
            <div class="mr-2"><h1 class="text-lg font-black">Voucher Entry</h1><p class="text-xs font-semibold text-slate-500">Trip-wise Excel entry against Sales Orders. Customer billing/payment is not tracked per truck.</p></div>
            <form id="rangeForm" method="GET" class="flex items-end gap-3">
                <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">From Date</span><input id="fromDate" name="from_date" type="date" value="{{ $fromDate }}" class="date-input" autofocus></label>
                <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">To Date</span><input id="toDate" name="to_date" type="date" value="{{ $toDate }}" class="date-input"></label>
            </form>
            <div class="ml-auto text-right"><div id="saveState" class="status-msg ok">No unsaved changes</div><button type="button" id="closeVoucherWorkspace" class="mt-1 h-8 w-8 border border-slate-300 bg-white font-black">×</button></div>
        </div>
        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-[10px] font-bold text-slate-600">
            <span><b>From / To Date</b> Change dates to load trips</span><span><b>Enter on empty selector</b> Open selector</span><span><b>Enter on selected selector</b> Next cell</span><span><b>Backspace</b> Clear complete selection/date</span><span><b>← →</b> Previous / next cell</span><span><b>↑ ↓</b> Same column</span><span><b>Supplier Payment</b> Click to open installments · Enter skips it</span><span><b>Tab</b> Focus Save All</span><span><b>Ctrl+S</b> Save all rows</span><span><b>Esc</b> Close modal / page</span>
        </div>
    </div>

    <div class="voucher-grid-wrap" id="gridWrap">
        <table class="voucher-grid">
            <colgroup>
                <col style="width:50px"><col style="width:125px"><col style="width:120px"><col style="width:115px"><col style="width:145px"><col style="width:135px"><col style="width:170px"><col style="width:190px"><col style="width:180px"><col style="width:125px"><col style="width:140px"><col style="width:125px"><col style="width:130px"><col style="width:125px"><col style="width:125px"><col style="width:125px"><col style="width:180px"><col style="width:220px"><col style="width:70px">
            </colgroup>
            <thead><tr>
                <th>Sr No</th><th>Transport Name</th><th>LR Date</th><th>LR No</th><th>Vehicle Type</th><th>Lorry Number</th><th>SO No</th><th>Customer</th><th>Supplier / Transporter</th><th>Supplier Freight</th><th>Advance Paid To Supplier</th><th>Balance</th><th>Supplier Payment</th><th>Hamali Loading</th><th>Hamali Unloading</th><th>Other Charges</th><th>Remarks</th><th>Delete</th>
            </tr></thead>
            <tbody id="voucherBody"></tbody>
        </table>
    </div>

    <div class="voucher-foot">
        <div class="totals"><span>Rows: <b id="totalRows">0</b></span><span>Supplier Freight: <b id="totalFreight">₹0.00</b></span><span>Advance: <b id="totalAdvance">₹0.00</b></span><span>Supplier Paid: <b id="totalPaid">₹0.00</b></span><span>Hamali: <b id="totalHamali">₹0.00</b></span><span>Other Charges: <b id="totalOtherCharges">₹0.00</b></span></div>
        <div class="voucher-actions"><button type="button" id="addRowBtn" class="vbtn">+ New Row <span class="ml-1 text-[10px] border border-slate-300 px-1">Insert</span></button><button type="button" id="deleteRowBtn" class="vbtn">Delete Row</button><button type="button" id="saveAllBtn" class="vbtn vbtn-primary">Save All <span class="ml-1 text-[10px] border border-white/40 px-1">Ctrl+S</span></button></div>
    </div>
</div>

<div class="modal-backdrop hidden" id="paymentModal" role="dialog" aria-modal="true">
    <div class="payment-card">
        <div class="payment-head"><h2 class="font-black">Supplier Payment Installments</h2><button type="button" id="paymentClose" class="text-2xl">×</button></div>
        <div class="p-4">
            <div class="pay-summary"><div><small>Supplier Freight</small><b id="payFreight">₹0.00</b></div><div><small>Advance</small><b id="payAdvance">₹0.00</b></div><div><small>Installments Paid</small><b id="payPaid">₹0.00</b></div><div><small>Balance</small><b id="payBalance">₹0.00</b></div></div>
            <div class="mt-3 max-h-52 overflow-auto"><table class="payment-table"><thead><tr><th>Date</th><th>Amount</th><th>Mode</th><th>Reference</th><th>Remarks</th><th></th></tr></thead><tbody id="paymentRows"></tbody></table></div>
            <form id="paymentForm" class="mt-4 grid grid-cols-1 gap-2 md:grid-cols-[145px_150px_130px_1fr_1fr_80px]">
                <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Date</span><input id="payDate" type="date" class="payment-input"></label>
                <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Amount</span><input id="payAmount" type="number" min="0" step="0.01" class="payment-input"></label>
                <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Mode</span><select id="payMode" class="payment-input"><option>Cash</option><option>NEFT</option><option>RTGS</option><option>UPI</option><option>Cheque</option><option>Other</option></select></label>
                <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Reference</span><input id="payReference" class="payment-input"></label>
                <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Remarks</span><input id="payRemarks" class="payment-input"></label>
                <button id="payAdd" class="mt-[18px] h-[38px] bg-emerald-800 px-3 font-black text-white">Add</button>
            </form>
            <div class="mt-2 text-xs font-semibold text-slate-500">Enter: Date → Amount → Mode → Reference → Remarks → Add · Esc closes and returns focus to Supplier Payment cell.</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const csrf=document.querySelector('meta[name=csrf-token]').content;
    const initialRows=@json($rows);
    const routes={range:@json(route('sale.vouchers.index')),save:@json(route('sale.vouchers.save-all')),base:@json(url('/sale/vouchers')),soOptions:@json(route('sale.orders.options'))};
    const body=document.getElementById('voucherBody'),wrap=document.getElementById('gridWrap'),fromDate=document.getElementById('fromDate'),toDate=document.getElementById('toDate'),saveState=document.getElementById('saveState'),saveBtn=document.getElementById('saveAllBtn');
    const fieldOrder=['transport_name_id','lr_date','lr_no','vehicle_type_id','lorry_number','sales_order_id','supplier_id','supplier_freight','advance_paid','supplier_payment','hamali_loading','hamali_unloading','other_charges','remarks'];
    const selectorDefs={transport_name_id:{type:'transport-names',title:'Select Transport Name',label:'transport_name'},vehicle_type_id:{type:'vehicle-types',title:'Select Vehicle Type',label:'vehicle_type_name'},sales_order_id:{title:'Select Sales Order',label:'so_number',so:true},supplier_id:{type:'suppliers',title:'Select Supplier / Transporter',label:'supplier_name'}};
    // Helpers must exist before normalizeRow() is used to build the initial grid.
    // The previous order threw a TDZ ReferenceError here, so none of the keyboard
    // listeners below (including From Date -> To Date on Enter) were attached.
    const num=v=>Number.isFinite(parseFloat(v))?parseFloat(v):0;
    const localToday=()=>{const d=new Date();d.setMinutes(d.getMinutes()-d.getTimezoneOffset());return d.toISOString().slice(0,10);};
    const blankZero=v=>num(v)===0?'':String(v??'');
    let rows=(initialRows||[]).map(normalizeRow),selectedIndex=0,dirty=false,paymentContext=null;
    const money=v=>'₹'+num(v).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});
    const esc=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
    function normalizeRow(r={}){return{id:r.id??null,sr_no:r.sr_no??null,transport_name_id:r.transport_name_id??null,transport_name:r.transport_name??'',lr_date:r.lr_date||fromDate.value||localToday(),lr_no:r.lr_no??'',vehicle_type_id:r.vehicle_type_id??null,vehicle_type_name:r.vehicle_type_name??'',lorry_number:r.lorry_number??'',sales_order_id:r.sales_order_id??null,so_number:r.so_number??'',customer_name:r.customer_name??'',from_location:r.from_location??'',to_location:r.to_location??'',per_trip_cost:num(r.per_trip_cost),supplier_id:r.supplier_id??null,supplier_name:r.supplier_name??'',supplier_freight:blankZero(r.supplier_freight),advance_paid:blankZero(r.advance_paid),supplier_paid:num(r.supplier_paid),supplier_balance:num(r.supplier_balance),hamali_loading:blankZero(r.hamali_loading),hamali_unloading:blankZero(r.hamali_unloading),other_charges:blankZero(r.other_charges),remarks:r.remarks??'',description:r.description??''};}
    function blankRow(){return normalizeRow({lr_date:fromDate.value||localToday()});}
    function activeRow(r){return !!(r.id||r.transport_name_id||r.lr_no||r.vehicle_type_id||r.lorry_number||r.sales_order_id||r.supplier_id||num(r.supplier_freight)||num(r.advance_paid)||num(r.hamali_loading)||num(r.hamali_unloading)||num(r.other_charges)||r.remarks||r.description);}
    function recompute(r){r.supplier_balance=Math.max(0,num(r.supplier_freight)-num(r.advance_paid)-num(r.supplier_paid));}
    function ensureBlank(){if(!rows.length||rows.every(activeRow))rows.push(blankRow());}
    function setStatus(msg,type=''){saveState.textContent=msg;saveState.className='status-msg'+(type?' '+type:'');}
    function markDirty(){dirty=true;setStatus('Unsaved changes','error');}
    function markSaved(msg='No unsaved changes'){dirty=false;setStatus(msg,'ok');}
    function selectorCell(i,field){const r=rows[i],d=selectorDefs[field],label=r[d.label]||'Select';return `<button type="button" data-row="${i}" data-field="${field}" data-grid-cell data-selector class="grid-button ${r[field]?'':'empty'}">${esc(label)}</button>`;}
    function inputCell(i,field,type='text',extra=''){const v=rows[i][field]??'';return `<input data-row="${i}" data-field="${field}" data-grid-cell type="${type}" value="${esc(v)}" class="grid-input ${type==='number'?'numeric':''}" ${extra}>`;}
    function paymentCell(i){return `<button type="button" data-row="${i}" data-field="supplier_payment" data-grid-cell data-payment class="payment-button">${money(rows[i].supplier_paid)}</button>`;}
    function render(){ensureBlank();rows.forEach(recompute);body.innerHTML=rows.map((r,i)=>`<tr data-row-index="${i}" class="${i===selectedIndex?'is-selected':''}"><td class="sr-cell">${r.sr_no??(i+1)}</td><td>${selectorCell(i,'transport_name_id')}</td><td>${inputCell(i,'lr_date','date')}</td><td>${inputCell(i,'lr_no')}</td><td>${selectorCell(i,'vehicle_type_id')}</td><td>${inputCell(i,'lorry_number')}</td><td>${selectorCell(i,'sales_order_id')}</td><td><div class="customer-cell" title="${esc((r.from_location||'')+' → '+(r.to_location||''))}">${esc(r.customer_name||'-')}</div></td><td>${selectorCell(i,'supplier_id')}</td><td>${inputCell(i,'supplier_freight','number','min="0" step="0.01"')}</td><td>${inputCell(i,'advance_paid','number','min="0" step="0.01"')}</td><td><div class="computed">${money(r.supplier_balance)}</div></td><td>${paymentCell(i)}</td><td>${inputCell(i,'hamali_loading','number','min="0" step="0.01"')}</td><td>${inputCell(i,'hamali_unloading','number','min="0" step="0.01"')}</td><td>${inputCell(i,'other_charges','number','min="0" step="0.01"')}</td><td>${inputCell(i,'remarks')}</td><td class="text-center"><button type="button" class="row-delete" data-row-delete="${i}">Delete</button></td></tr>`).join('');updateTotals();}
    function updateTotals(){const active=rows.filter(r=>r.id||activeRow(r));document.getElementById('totalRows').textContent=active.filter(r=>r.id||r.sales_order_id).length;document.getElementById('totalFreight').textContent=money(active.reduce((s,r)=>s+num(r.supplier_freight),0));document.getElementById('totalAdvance').textContent=money(active.reduce((s,r)=>s+num(r.advance_paid),0));document.getElementById('totalPaid').textContent=money(active.reduce((s,r)=>s+num(r.supplier_paid),0));document.getElementById('totalHamali').textContent=money(active.reduce((s,r)=>s+num(r.hamali_loading)+num(r.hamali_unloading),0));document.getElementById('totalOtherCharges').textContent=money(active.reduce((s,r)=>s+num(r.other_charges),0));}
    function cell(i,field){return body.querySelector(`[data-row="${i}"][data-field="${field}"]`);}
    function ensureVisible(el){if(!el)return;const wr=wrap.getBoundingClientRect(),er=el.getBoundingClientRect();if(er.left<wr.left+8)wrap.scrollLeft-=wr.left+8-er.left;else if(er.right>wr.right-8)wrap.scrollLeft+=er.right-(wr.right-8);if(er.top<wr.top+34)wrap.scrollTop-=wr.top+34-er.top;else if(er.bottom>wr.bottom-8)wrap.scrollTop+=er.bottom-(wr.bottom-8);}
    function focusCell(i,field,select=true){const el=cell(i,field);if(!el)return;selectedIndex=i;body.querySelectorAll('tr').forEach(tr=>tr.classList.toggle('is-selected',Number(tr.dataset.rowIndex)===i));el.focus({preventScroll:true});ensureVisible(el);if(select&&el instanceof HTMLInputElement&&['text','number'].includes(el.type))el.select();}
    function nextCell(i,field){const idx=fieldOrder.indexOf(field);if(idx<fieldOrder.length-1){focusCell(i,fieldOrder[idx+1]);return;}if(i===rows.length-1){rows.push(blankRow());render();}focusCell(Math.min(i+1,rows.length-1),fieldOrder[0],false);if(i+1<rows.length){wrap.scrollLeft=0;setTimeout(()=>ensureVisible(cell(i+1,fieldOrder[0])),0);}}
    function prevCell(i,field){const idx=fieldOrder.indexOf(field);if(idx>0)focusCell(i,fieldOrder[idx-1]);else if(i>0)focusCell(i-1,fieldOrder[fieldOrder.length-1]);}
    function vertical(i,field,dir){const target=i+dir;if(target>=0&&target<rows.length)focusCell(target,field);}
    function openSelector(i,field,search=''){const r=rows[i],d=selectorDefs[field],opener=cell(i,field);const opts={title:d.title,opener,search,onSelect:(item,meta)=>{r[field]=item.id;r[d.label]=item.label||item.name;if(field==='sales_order_id'){r.so_number=item.so_number||item.name;r.customer_name=item.customer_name||'';r.from_location=item.from_location||'';r.to_location=item.to_location||'';r.per_trip_cost=num(item.per_trip_cost);}markDirty();render();setTimeout(()=>meta?.source==='quick-add'?focusCell(i,field,false):nextCell(i,field),25);}};if(d.so){opts.url=routes.soOptions+(r.id?('?include_id='+encodeURIComponent(r.sales_order_id||'')):'');opts.allowAdd=false;}else opts.type=d.type;MasterSelector.open(opts);}
    function clearSelector(i,field){const r=rows[i],d=selectorDefs[field];r[field]=null;r[d.label]='';if(field==='sales_order_id'){r.so_number='';r.customer_name='';r.from_location='';r.to_location='';r.per_trip_cost=0;}markDirty();render();setTimeout(()=>focusCell(i,field,false),0);}
    body.addEventListener('focusin',e=>{const el=e.target.closest('[data-grid-cell]');if(!el)return;selectedIndex=Number(el.dataset.row);body.querySelectorAll('tr').forEach(tr=>tr.classList.toggle('is-selected',Number(tr.dataset.rowIndex)===selectedIndex));ensureVisible(el);if(el instanceof HTMLInputElement&&['text','number'].includes(el.type))el.select();});
    body.addEventListener('input',e=>{const el=e.target.closest('input[data-grid-cell]');if(!el)return;const i=Number(el.dataset.row),field=el.dataset.field;if(field==='lorry_number')el.value=el.value.toUpperCase();rows[i][field]=el.value;recompute(rows[i]);markDirty();const bal=body.querySelector(`tr[data-row-index="${i}"] .computed`);if(bal)bal.textContent=money(rows[i].supplier_balance);updateTotals();});
    body.addEventListener('click',e=>{const del=e.target.closest('[data-row-delete]');if(del){selectedIndex=Number(del.dataset.rowDelete);deleteSelected();return;}const sel=e.target.closest('[data-selector]');if(sel){openSelector(Number(sel.dataset.row),sel.dataset.field);return;}const pay=e.target.closest('[data-payment]');if(pay){openPayment(Number(pay.dataset.row));}});
    body.addEventListener('keydown',e=>{const el=e.target.closest('[data-grid-cell]');if(!el)return;const i=Number(el.dataset.row),field=el.dataset.field,r=rows[i];if(e.key==='ArrowUp'){e.preventDefault();if(i===0)fromDate.focus();else vertical(i,field,-1);return;}if(e.key==='ArrowDown'){e.preventDefault();vertical(i,field,1);return;}if(e.key==='ArrowLeft'&&!(el instanceof HTMLInputElement&&el.type==='date')){if(el instanceof HTMLInputElement&&el.type==='text'&&el.selectionStart>0)return;e.preventDefault();prevCell(i,field);return;}if(e.key==='ArrowRight'&&!(el instanceof HTMLInputElement&&el.type==='date')){if(el instanceof HTMLInputElement&&el.type==='text'&&el.selectionStart<(el.value||'').length)return;e.preventDefault();nextCell(i,field);return;}if(e.key==='Backspace'&&el.matches('[data-selector]')){e.preventDefault();clearSelector(i,field);return;}if(e.key==='Backspace'&&el instanceof HTMLInputElement&&el.type==='date'){e.preventDefault();el.value='';r[field]='';markDirty();return;}if(el.matches('[data-selector]')&&e.key.length===1&&!e.ctrlKey&&!e.altKey&&!e.metaKey){e.preventDefault();openSelector(i,field,e.key);return;}if(e.key!=='Enter')return;e.preventDefault();if(el.matches('[data-selector]')){if(r[field])nextCell(i,field);else openSelector(i,field);return;}if(el.matches('[data-payment]')){nextCell(i,field);return;}if(el instanceof HTMLInputElement&&el.type==='date'&&!el.value){el.showPicker?.();return;}nextCell(i,field);});
    async function saveAll(){if(!fromDate.value||!toDate.value||toDate.value<fromDate.value){setStatus('Select a valid date range','error');fromDate.focus();return;}const payload=rows.filter(activeRow).map(r=>({id:r.id,lr_date:r.lr_date||fromDate.value,sales_order_id:r.sales_order_id,transport_name_id:r.transport_name_id,lr_no:r.lr_no||null,vehicle_type_id:r.vehicle_type_id,lorry_number:r.lorry_number||null,supplier_id:r.supplier_id,supplier_freight:num(r.supplier_freight),advance_paid:num(r.advance_paid),hamali_loading:num(r.hamali_loading),hamali_unloading:num(r.hamali_unloading),other_charges:num(r.other_charges),remarks:r.remarks||null,description:r.description||null}));setStatus('Saving...');try{const res=await fetch(routes.save,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({from_date:fromDate.value,to_date:toDate.value,rows:payload})});const data=await res.json();if(!res.ok)throw new Error(data.message||Object.values(data.errors||{})[0]?.[0]||'Save failed');dirty=false;window.AppToastNext?.(data.message||'Voucher entries saved successfully.','success');window.location.href=data.redirect;}catch(err){setStatus(err.message||'Save failed','error');window.AppToast?.(err.message||'Save failed','error',5500);}}
    function addRow(){rows.push(blankRow());selectedIndex=rows.length-1;render();setTimeout(()=>focusCell(selectedIndex,fieldOrder[0],false),0);}
    async function deleteSelected(){const r=rows[selectedIndex];if(!r)return;if(r.id){if(!confirm('Delete this voucher trip?'))return;try{const res=await fetch(routes.base+'/'+r.id,{method:'DELETE',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf}});let data={};try{data=await res.json();}catch(_e){}if(!res.ok)throw new Error(data.message||'This voucher trip cannot be deleted.');rows.splice(selectedIndex,1);selectedIndex=Math.max(0,selectedIndex-1);render();markSaved();window.AppToast?.(data.message||'Voucher trip deleted.','success');}catch(err){window.AppToast?.(err.message||'This voucher trip cannot be deleted.','error',5500);}return;}rows.splice(selectedIndex,1);if(!rows.length)rows.push(blankRow());selectedIndex=Math.max(0,selectedIndex-1);render();markDirty();}
    function validRange(){return !!fromDate.value&&!!toDate.value&&fromDate.value<=toDate.value;}
    async function loadRange(focusGrid=false){
        if(!validRange()){setStatus('Select a valid From Date and To Date.','error');return;}
        if(dirty&&!confirm('Unsaved voucher changes will be lost. Load another date range?'))return;
        try{
            setStatus('Loading...');
            const url=new URL(routes.range,window.location.origin);
            url.searchParams.set('from_date',fromDate.value);
            url.searchParams.set('to_date',toDate.value);
            const res=await fetch(url,{headers:{Accept:'application/json'}});
            const data=await res.json();
            if(!res.ok)throw new Error(data.message||'Unable to load voucher entries.');
            rows=(data.rows||[]).map(normalizeRow);
            selectedIndex=0;
            dirty=false;
            render();
            markSaved('Entries loaded');
            window.history.replaceState({},'',url.pathname+url.search);
            if(focusGrid)requestAnimationFrame(()=>focusCell(0,fieldOrder[0],false));
        }catch(err){setStatus(err.message||'Unable to load voucher entries.','error');}
    }
    function requestRange(focusGrid=false){return loadRange(focusGrid);}
    fromDate.addEventListener('keydown',e=>{
        if(e.key==='Backspace'){e.preventDefault();fromDate.value='';return;}
        if(e.key==='Enter'){e.preventDefault();if(!fromDate.value){fromDate.showPicker?.();return;}toDate.focus();return;}
    });
    toDate.addEventListener('keydown',e=>{
        if(e.key==='Backspace'){e.preventDefault();toDate.value='';return;}
        if(e.key==='Enter'){e.preventDefault();if(!toDate.value){toDate.showPicker?.();return;}requestRange(true);return;}
    });
    let reloadTimer=null;[fromDate,toDate].forEach(x=>x.addEventListener('change',()=>{clearTimeout(reloadTimer);reloadTimer=setTimeout(()=>requestRange(false),120);}));
    document.getElementById('addRowBtn').addEventListener('click',addRow);document.getElementById('deleteRowBtn').addEventListener('click',deleteSelected);saveBtn.addEventListener('click',saveAll);document.getElementById('closeVoucherWorkspace').addEventListener('click',()=>{if(dirty&&!confirm('Unsaved voucher changes will be lost. Continue?'))return;window.AppPageExit?.('#nav-sale');});

    const pModal=document.getElementById('paymentModal'),pRows=document.getElementById('paymentRows'),payDate=document.getElementById('payDate'),payAmount=document.getElementById('payAmount'),payMode=document.getElementById('payMode'),payReference=document.getElementById('payReference'),payRemarks=document.getElementById('payRemarks'),payAdd=document.getElementById('payAdd');
    async function openPayment(i){const r=rows[i];if(!r.id){setStatus('Save this voucher row first, then add Supplier Payment.','error');window.AppToast?.('Save this voucher row first, then add Supplier Payment.','error');return;}paymentContext={row:i,voucherId:r.id};pModal.classList.remove('hidden');await refreshPayments();payDate.value=localToday();payAmount.value='';payReference.value='';payRemarks.value='';setTimeout(()=>payDate.focus(),20);}
    function closePayment(){const c=paymentContext;paymentContext=null;pModal.classList.add('hidden');if(c)setTimeout(()=>focusCell(c.row,'hamali_loading',false),0);}
    async function refreshPayments(){if(!paymentContext)return;const res=await fetch(`${routes.base}/${paymentContext.voucherId}/supplier-payments`,{headers:{Accept:'application/json'}});const data=await res.json();if(!res.ok){setStatus(data.message||'Unable to load payments','error');return;}document.getElementById('payFreight').textContent=money(data.supplier_freight);document.getElementById('payAdvance').textContent=money(data.advance_paid);document.getElementById('payPaid').textContent=money(data.paid);document.getElementById('payBalance').textContent=money(data.balance);pRows.innerHTML=(data.items||[]).length?data.items.map(x=>`<tr><td>${esc(x.payment_date)}</td><td>${money(x.amount)}</td><td>${esc(x.payment_mode||'-')}</td><td>${esc(x.reference||'-')}</td><td>${esc(x.remarks||'-')}</td><td><button type="button" class="row-delete" data-delete-payment="${x.id}">Delete</button></td></tr>`).join(''):'<tr><td colspan="6" class="py-5 text-center text-slate-400">No installments yet.</td></tr>';const r=rows[paymentContext.row];r.supplier_paid=num(data.paid);r.supplier_balance=num(data.balance);render();}
    document.getElementById('paymentClose').addEventListener('click',closePayment);pModal.addEventListener('mousedown',e=>{if(e.target===pModal)closePayment();});
    document.getElementById('paymentForm').addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();e.stopPropagation();closePayment();return;}if(e.key==='Backspace'&&e.target===payDate){e.preventDefault();payDate.value='';return;}if(e.key!=='Enter')return;e.preventDefault();const order=[payDate,payAmount,payMode,payReference,payRemarks,payAdd],i=order.indexOf(e.target);if(e.target===payDate&&!payDate.value){payDate.showPicker?.();return;}if(i>=0&&i<order.length-1){order[i+1].focus();if(order[i+1] instanceof HTMLInputElement)order[i+1].select?.();}else document.getElementById('paymentForm').requestSubmit();});
    document.getElementById('paymentForm').addEventListener('submit',async e=>{e.preventDefault();if(!paymentContext)return;try{const res=await fetch(`${routes.base}/${paymentContext.voucherId}/supplier-payments`,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({payment_date:payDate.value,amount:num(payAmount.value),payment_mode:payMode.value,reference:payReference.value,remarks:payRemarks.value})});const data=await res.json();if(!res.ok)throw new Error(data.message||Object.values(data.errors||{})[0]?.[0]||'Payment failed');payAmount.value='';payReference.value='';payRemarks.value='';await refreshPayments();setStatus(dirty?'Payment saved · voucher has unsaved changes':'Payment saved',dirty?'error':'ok');window.AppToast?.('Supplier payment saved.','success');payAmount.focus();}catch(err){setStatus(err.message,'error');window.AppToast?.(err.message||'Payment failed.','error',5500);payAmount.focus();}});
    pRows.addEventListener('click',async e=>{const b=e.target.closest('[data-delete-payment]');if(!b||!paymentContext)return;if(!confirm('Delete this installment?'))return;const res=await fetch(`${routes.base}/${paymentContext.voucherId}/supplier-payments/${b.dataset.deletePayment}`,{method:'DELETE',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf}});const data=await res.json();if(!res.ok){setStatus(data.message||'Delete failed','error');return;}await refreshPayments();});
    window.addEventListener('keydown',e=>{if(!pModal.classList.contains('hidden')){if(e.key==='Escape'){e.preventDefault();e.stopImmediatePropagation();closePayment();}return;}if(e.key==='Tab'&&!e.shiftKey&&!e.ctrlKey&&!e.altKey&&!e.metaKey){e.preventDefault();saveBtn.focus();return;}if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();e.stopImmediatePropagation();saveAll();return;}if(e.key==='Insert'){e.preventDefault();addRow();return;}if((e.ctrlKey||e.metaKey)&&e.key==='Delete'){e.preventDefault();deleteSelected();}});
    window.addEventListener('beforeunload',e=>{if(dirty){e.preventDefault();e.returnValue='';}});
    render();markSaved();
});
</script>
@endpush
