@extends('layouts.app')
@section('title', 'Sales Orders | XYZ Transport')
@section('page-nav-target', '#nav-sale')

@push('styles')
<style>
.so-card{border:1px solid #cbd5e1;background:#fff}.so-table{width:100%;border-collapse:collapse;min-width:1700px;font-size:.82rem}.so-table th{position:sticky;top:0;z-index:2;background:#e9eef5;border-right:1px solid #cbd5e1;border-bottom:1px solid #94a3b8;padding:.58rem .65rem;text-align:left;font-size:.69rem;text-transform:uppercase;white-space:nowrap}.so-table td{border-right:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;padding:.55rem .65rem;vertical-align:top}.so-table tbody tr:focus td,.so-table tbody tr.is-selected td{background:#d1fae5}.so-table tbody tr:hover td{background:#ecfdf5}.so-modal{position:fixed;inset:0;z-index:220;background:rgba(15,23,42,.56);display:flex;align-items:center;justify-content:center;padding:1rem}.so-modal.hidden{display:none!important}.so-modal-card{width:min(930px,97vw);max-height:94vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}.so-input{height:2.5rem;width:100%;border:1px solid #94a3b8;background:#fff;padding:0 .65rem;outline:none}.so-input:focus,.so-textarea:focus,.so-selector:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.so-textarea{width:100%;min-height:84px;border:1px solid #94a3b8;padding:.6rem;outline:none}.so-selector{height:2.5rem;width:100%;border:1px solid #94a3b8;background:#fff;padding:0 .65rem;text-align:left;outline:none;font-weight:700}.so-selector.empty{color:#64748b;font-weight:600}.so-readonly{background:#f1f5f9;font-weight:800}.so-complete{color:#166534;font-weight:900}.so-open{color:#047857;font-weight:900}.so-inactive{color:#94a3b8;font-weight:900}.so-progress{white-space:nowrap;font-weight:800}.filter-selector{height:2.5rem;min-width:230px;border:1px solid #94a3b8;background:#fff;padding:0 .65rem;text-align:left;font-weight:700}.filter-selector.empty{color:#64748b;font-weight:600}
.so-inline-add{height:2.5rem;flex:none;border:1px solid #047857;background:#ecfdf5;color:#065f46;padding:0 .8rem;font-weight:900;white-space:nowrap}.so-inline-add:focus{box-shadow:0 0 0 2px #a7f3d0;outline:none}
</style>
@endpush

@section('content')
<div class="app-workspace">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="mr-auto">
            <h1 class="text-xl font-black text-slate-950">SO / Sales Orders</h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Create customer orders first. Voucher trips will consume the SO trip quantity.</p>
        </div>
        <form method="GET" id="soFilterForm" class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="customer_id" id="filterCustomerId" value="{{ $customerId ?: '' }}">
            <label><span class="mb-1 block text-[11px] font-black uppercase text-slate-500">Customer</span><button type="button" id="filterCustomerBtn" class="filter-selector {{ $selectedCustomer ? '' : 'empty' }}">{{ $selectedCustomer?->name ?: 'Select Customer' }}</button></label>
            <label><span class="mb-1 block text-[11px] font-black uppercase text-slate-500">Status</span><select name="status" class="so-input w-36"><option value="">All</option><option value="open" @selected($status==='open')>Open</option><option value="completed" @selected($status==='completed')>Completed</option><option value="inactive" @selected($status==='inactive')>Inactive</option></select></label>
            <label><span class="mb-1 block text-[11px] font-black uppercase text-slate-500">Search</span><input name="search" value="{{ $search }}" class="so-input w-64" placeholder="SO no / customer / route..."></label>
            <button class="h-10 border border-slate-400 bg-white px-4 text-sm font-bold">Show</button>
            @if($search || $customerId || $status)<a href="{{ route('sale.orders.index') }}" class="h-10 border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-600">Reset</a>@endif
        </form>
        <button type="button" id="addSoBtn" class="h-10 bg-emerald-800 px-5 text-sm font-black text-white hover:bg-emerald-900">+ Add SO</button>
    </div>

    <div class="so-card overflow-auto" style="max-height:calc(100vh - 205px)">
        <table class="so-table">
            <thead><tr>
                <th>SR</th><th>SO No.</th><th>SO Date</th><th>Customer</th><th>From</th><th>To</th><th>Description / Service</th><th>Trips Qty</th><th>Used</th><th>Remaining</th><th>Per Trip Cost</th><th>Value</th><th>Tax & Other Charges</th><th>Total Amount</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php($used=(int)$item->vouchers_count)
                @php($remaining=max(0,(int)$item->trips_quantity-$used))
                <tr tabindex="0" data-so-row>
                    <td>{{ ($items->currentPage()-1)*$items->perPage()+$loop->iteration }}</td>
                    <td class="font-black">{{ $item->so_number }}</td>
                    <td>{{ $item->so_date?->format('d-m-Y') }}</td>
                    <td class="font-bold">{{ $item->customer?->name }}</td>
                    <td>{{ $item->from_location }}</td><td>{{ $item->to_location }}</td>
                    <td style="max-width:320px">{{ $item->description }}</td>
                    <td class="text-right font-bold">{{ $item->trips_quantity }}</td><td class="text-right">{{ $used }}</td><td class="text-right font-bold">{{ $remaining }}</td>
                    <td class="text-right">₹{{ number_format((float)$item->per_trip_cost,2) }}</td><td class="text-right">₹{{ number_format((float)$item->value,2) }}</td>
                    <td><b>{{ strtoupper($item->tax_mode ?? 'rcm') }}</b><br>@if(($item->tax_mode ?? 'rcm') === 'na')<span>GST: NA</span>@elseif(($item->tax_mode ?? 'rcm') === 'rcm')<span>RCM @ 5% (Info) = ₹{{ number_format((float)$item->value * .05,2) }}</span>@else<span>GST @ {{ rtrim(rtrim(number_format((float)$item->gst_rate,2,'.',''),'0'),'.') }}% = ₹{{ number_format((float)$item->gst_amount,2) }}</span>@endif<br><span class="text-slate-500">Other: ₹{{ number_format((float)$item->other_charges,2) }}</span></td>
                    <td class="text-right font-black">₹{{ number_format((float)$item->total_amount,2) }}</td>
                    <td>@if(!$item->is_active)<span class="so-inactive">Inactive</span>@elseif($remaining<=0)<span class="so-complete">Completed</span>@else<span class="so-open">Open</span><div class="text-[11px] text-slate-500">{{ $used }}/{{ $item->trips_quantity }} trips</div>@endif</td>
                    <td class="whitespace-nowrap">
                        <button type="button" class="edit-so font-bold text-blue-700 hover:underline" data-id="{{ $item->id }}" data-record="{{ json_encode([
                            'so_number'=>$item->so_number,'so_date'=>$item->so_date?->format('Y-m-d'),'customer_id'=>$item->customer_id,'customer_name'=>$item->customer?->name,'customer_gst_no'=>$item->customer?->gst_no,'from_location'=>$item->from_location,'to_location'=>$item->to_location,'description'=>$item->description,'trips_quantity'=>(int)$item->trips_quantity,'per_trip_cost'=>(float)$item->per_trip_cost,'value'=>(float)$item->value,'tax_mode'=>$item->tax_mode ?? 'rcm','gst_rate'=>(float)$item->gst_rate,'gst_amount'=>(float)$item->gst_amount,'other_charges'=>(float)$item->other_charges,'total_amount'=>(float)$item->total_amount,'is_active'=>(bool)$item->is_active,'used_trips'=>$used
                        ]) }}">Edit</button>
                        <form method="POST" action="{{ route('sale.orders.destroy',$item) }}" class="ml-3 inline" onsubmit="return confirm('Delete this SO?')">@csrf @method('DELETE')<button class="font-bold text-red-700 hover:underline">Delete</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="16" class="py-12 text-center text-slate-400">No Sales Orders found. Press Insert or click + Add SO.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3 flex items-center justify-between gap-3"><div class="text-xs font-bold text-slate-500"><span class="border border-slate-300 bg-white px-1">Insert</span> Add SO · ↑↓ Row · <span class="border border-slate-300 bg-white px-1">Enter</span> Edit · <span class="border border-slate-300 bg-white px-1">Esc</span> Navbar</div><div>{{ $items->links() }}</div></div>
</div>

<div id="soModal" class="so-modal hidden" role="dialog" aria-modal="true">
    <div class="so-modal-card">
        <div class="flex items-center justify-between bg-emerald-950 px-5 py-3 text-white"><h2 id="soModalTitle" class="font-black">Add SO</h2><button type="button" id="soClose" class="text-2xl">×</button></div>
        <form id="soForm" method="POST" action="{{ route('sale.orders.store') }}" class="p-5">@csrf
            <div id="soMethodSlot"></div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">SO Number <span class="text-red-600">*</span></span><input class="so-input uppercase" style="text-transform:uppercase" name="so_number" data-so-field="so_number" required></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">SO Date <span class="text-red-600">*</span></span><input type="date" class="so-input" name="so_date" data-so-field="so_date" required></label>
                 <label class="md:col-span-2"><span class="mb-1 block text-xs font-black uppercase text-slate-600">Customer <span class="text-red-600">*</span></span><input type="hidden" name="customer_id" data-so-field="customer_id"><span class="flex"><button type="button" id="soCustomerBtn" class="so-selector empty flex-1" data-selector-field="customer_id">Select Customer</button><button type="button" id="addSoCustomerBtn" class="so-inline-add px-3 text-xs" title="Add Customer" aria-label="Add Customer">+ Add Customer</button></span></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">From <span class="text-red-600">*</span></span><input class="so-input" name="from_location" data-so-field="from_location" required></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">To <span class="text-red-600">*</span></span><input class="so-input" name="to_location" data-so-field="to_location" required></label>
                <label class="md:col-span-2"><span class="mb-1 block text-xs font-black uppercase text-slate-600">Description / Service <span class="text-red-600">*</span></span><textarea class="so-textarea" name="description" data-so-field="description" required></textarea></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">Trips Quantity <span class="text-red-600">*</span></span><input type="number" min="1" step="1" class="so-input" name="trips_quantity" data-so-field="trips_quantity" required></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">Per Trip Cost <span class="text-red-600">*</span></span><input type="number" min="0" step="0.01" class="so-input" name="per_trip_cost" data-so-field="per_trip_cost" required></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">Value</span><input readonly class="so-input so-readonly" data-so-calc="value" value="0.00"></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">Tax Mode <span class="text-red-600">*</span></span><select name="tax_mode" class="so-input" data-so-field="tax_mode" required><option value="rcm">RCM</option><option value="hiring">HIRING</option><option value="gst">GST</option><option value="na">NA</option></select></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">GST Rate</span><select name="gst_rate" class="so-input" data-so-field="gst_rate"><option value="0">0%</option><option value="5">5%</option><option value="12">12%</option><option value="18">18%</option></select></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">GST Amount</span><input readonly class="so-input so-readonly" data-so-calc="gst_amount" value="0.00"></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">RCM @5% Info</span><input readonly class="so-input so-readonly" data-so-calc="rcm_amount" value="0.00"></label>
                <label><span class="mb-1 block text-xs font-black uppercase text-slate-600">Other Charges</span><input type="number" min="0" step="0.01" class="so-input" name="other_charges" data-so-field="other_charges"></label>
                <label class="md:col-span-2"><span class="mb-1 block text-xs font-black uppercase text-slate-600">Total Amount</span><input readonly class="so-input so-readonly text-lg" data-so-calc="total_amount" value="0.00"></label>
                <label class="md:col-span-2 flex items-center gap-3 border border-slate-200 bg-slate-50 p-3 font-bold text-slate-700"><input type="checkbox" name="is_active" value="1" data-so-field="is_active" checked class="h-5 w-5"> Active SO</label>
            </div>
            <div class="mt-5 flex justify-end gap-2"><button type="button" id="soCancel" class="border border-slate-400 px-5 py-2.5 text-sm font-bold">Cancel</button><button type="submit" class="bg-emerald-800 px-6 py-2.5 text-sm font-black text-white">Save SO</button></div>
            <div class="mt-2 text-right text-xs font-semibold text-slate-500">Insert = Add · Enter = Next · Backspace = Clear selector/date · Ctrl+S = Save · Esc = Close</div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const modal=document.getElementById('soModal'), form=document.getElementById('soForm'), addBtn=document.getElementById('addSoBtn'), title=document.getElementById('soModalTitle'), methodSlot=document.getElementById('soMethodSlot');
    const storeUrl=@json(route('sale.orders.store')), baseUrl=@json(url('/sale/orders'));
    let opener=null;
    const field=(name)=>form.querySelector(`[data-so-field="${name}"]`);
    const customerBtn=document.getElementById('soCustomerBtn');
    const ordered=()=>Array.from(form.querySelectorAll('[data-so-field], [data-selector-field]')).filter(el=>!el.disabled && el.type!=='hidden' && el.offsetParent!==null);
    const num=v=>Number.isFinite(parseFloat(v))?parseFloat(v):0;
    const localToday=()=>{const d=new Date();d.setMinutes(d.getMinutes()-d.getTimezoneOffset());return d.toISOString().slice(0,10);};
    const fmt=v=>num(v).toFixed(2);
    function calc(){
        const value=num(field('trips_quantity').value)*num(field('per_trip_cost').value);
        const rawMode=field('tax_mode').value;
        const mode=['rcm','hiring','gst','na'].includes(rawMode)?rawMode:'rcm';
        let gstRate=0;
        if(mode==='hiring'){
            gstRate=18;
            field('gst_rate').value='18';
        }else if(mode==='gst'){
            gstRate=[5,12,18].includes(num(field('gst_rate').value))?num(field('gst_rate').value):5;
            field('gst_rate').value=String(gstRate);
        }else{
            // RCM and NA: no normal GST is added.
            gstRate=0;
            field('gst_rate').value='0';
        }
        const gst=value*gstRate/100,rcm=mode==='rcm'?value*.05:0,total=value+gst+num(field('other_charges').value);
        form.querySelector('[data-so-calc=value]').value=fmt(value);
        form.querySelector('[data-so-calc=gst_amount]').value=fmt(gst);
        form.querySelector('[data-so-calc=rcm_amount]').value=fmt(rcm);
        form.querySelector('[data-so-calc=total_amount]').value=fmt(total);
    }
    ['trips_quantity','per_trip_cost','tax_mode','gst_rate','other_charges'].forEach(n=>{field(n).addEventListener('input',calc);field(n).addEventListener('change',calc);});
    field('so_number').addEventListener('input',()=>{const p=field('so_number').selectionStart;field('so_number').value=field('so_number').value.toUpperCase();try{field('so_number').setSelectionRange(p,p);}catch(e){}});
    function setSelector(button,hidden,id,label){hidden.value=id||'';button.textContent=label||'Select';button.classList.toggle('empty',!id);}
    function applyCustomerTaxDefault(item){field('tax_mode').value=String(item.gst_no||'').trim()?field('tax_mode').value:'rcm';calc();}
    function openCustomer(btn=customerBtn){MasterSelector.open({type:'customers',title:'Select Customer',opener:btn,onSelect:(item,meta)=>{setSelector(customerBtn,field('customer_id'),item.id,item.label||item.name);applyCustomerTaxDefault(item);setTimeout(()=>meta?.source==='quick-add'?customerBtn.focus():field('from_location').focus(),20);}});}
    customerBtn.addEventListener('click',()=>openCustomer(customerBtn)); document.getElementById('addSoCustomerBtn').addEventListener('click',e=>MasterSelector.open({type:'customers',title:'Add Customer',opener:e.currentTarget,allowAdd:true,forceAdd:true,onSelect:item=>{setSelector(customerBtn,field('customer_id'),item.id,item.label||item.name);applyCustomerTaxDefault(item);setTimeout(()=>customerBtn.focus(),20);}}));
    function reset(){form.reset();methodSlot.innerHTML='';form.action=storeUrl;field('so_date').value=localToday();field('tax_mode').value='hiring';field('gst_rate').value='18';field('is_active').checked=true;setSelector(customerBtn,field('customer_id'),null,'Select Customer');field('other_charges').value='';calc();}
    function openCreate(origin=addBtn){opener=origin||document.activeElement;reset();title.textContent='Add SO';modal.classList.remove('hidden');setTimeout(()=>field('so_number').focus(),20);}
    function openEdit(btn){opener=btn;let r={};try{r=JSON.parse(btn.dataset.record||'{}');}catch(error){window.AppToast?.('Unable to open this Sales Order. Please refresh and try again.','error');return;}reset();form.action=baseUrl+'/'+btn.dataset.id;methodSlot.innerHTML='<input type="hidden" name="_method" value="PUT">';title.textContent='Edit SO';['so_number','so_date','from_location','to_location','description','trips_quantity','per_trip_cost','other_charges'].forEach(n=>field(n).value=r[n]??'');field('tax_mode').value=['rcm','hiring','gst','na'].includes(r.tax_mode)?r.tax_mode:'rcm';field('gst_rate').value=String(r.gst_rate??0);field('is_active').checked=!!r.is_active;setSelector(customerBtn,field('customer_id'),r.customer_id,r.customer_name||'Select Customer');calc();modal.classList.remove('hidden');setTimeout(()=>field('so_number').focus(),20);}
    function close(){modal.classList.add('hidden');const back=opener;opener=null;setTimeout(()=>back?.focus?.(),0);}
    addBtn.addEventListener('click',()=>openCreate(addBtn));document.querySelectorAll('.edit-so').forEach(b=>b.addEventListener('click',()=>openEdit(b)));document.getElementById('soClose').addEventListener('click',close);document.getElementById('soCancel').addEventListener('click',close);modal.addEventListener('mousedown',e=>{if(e.target===modal)close();});
    form.addEventListener('keydown',e=>{
        if(e.key==='Escape'){e.preventDefault();e.stopPropagation();close();return;}
        if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();e.stopPropagation();form.requestSubmit();return;}
        const target=e.target;
        if(e.key==='Backspace'&&target===customerBtn){e.preventDefault();setSelector(customerBtn,field('customer_id'),null,'Select Customer');return;}
        if(e.key==='Backspace'&&target instanceof HTMLInputElement&&target.type==='date'){e.preventDefault();target.value='';return;}
        if(e.key!=='Enter'||target.tagName==='TEXTAREA')return;
        const list=ordered(),i=list.indexOf(target);if(i<0)return;e.preventDefault();if(target===customerBtn){if(field('customer_id').value){field('from_location').focus();}else openCustomer(target);return;}if(target instanceof HTMLInputElement&&target.type==='date'&&!target.value){target.showPicker?.();return;}if(i<list.length-1)list[i+1].focus();else form.requestSubmit();
    });
    const rows=Array.from(document.querySelectorAll('[data-so-row]'));rows.forEach((row,i)=>row.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();rows[Math.min(i+1,rows.length-1)]?.focus();}else if(e.key==='ArrowUp'){e.preventDefault();rows[Math.max(0,i-1)]?.focus();}else if(e.key==='Enter'){e.preventDefault();row.querySelector('.edit-so')?.click();}}));
    document.addEventListener('keydown',e=>{if(e.defaultPrevented||e.target.closest?.('[role="dialog"]'))return;if(e.key==='Insert'&&modal.classList.contains('hidden')){e.preventDefault();openCreate(document.activeElement);}});
    const filterBtn=document.getElementById('filterCustomerBtn'),filterId=document.getElementById('filterCustomerId');filterBtn.addEventListener('click',()=>MasterSelector.open({type:'customers',title:'Filter Customer',opener:filterBtn,allowAdd:false,onSelect:item=>{filterId.value=item.id;filterBtn.textContent=item.label||item.name;filterBtn.classList.remove('empty');setTimeout(()=>document.querySelector('#soFilterForm input[name=search]')?.focus(),20);}}));filterBtn.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();filterBtn.click();}else if(e.key==='Backspace'){e.preventDefault();filterId.value='';filterBtn.textContent='Select Customer';filterBtn.classList.add('empty');}});
});
</script>
@endpush
