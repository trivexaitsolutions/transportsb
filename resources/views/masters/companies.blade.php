@extends('layouts.app')
@section('title', 'Company Master | XYZ Transport')
@section('page-nav-target', '#nav-masters')

@push('styles')
<style>
.company-card{border:1px solid #cbd5e1;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.company-table{width:100%;border-collapse:collapse;font-size:.86rem}.company-table th{background:#f1f5f9;border-bottom:1px solid #94a3b8;padding:.65rem .75rem;text-align:left;font-size:.72rem;text-transform:uppercase;color:#475569;white-space:nowrap}.company-table td{border-bottom:1px solid #e2e8f0;padding:.62rem .75rem;vertical-align:top}.company-table tr:hover td,.company-table tr:focus td{background:#ecfdf5}
.company-input,.company-textarea{width:100%;border:1px solid #94a3b8;background:#fff;padding:0 .65rem;outline:none}.company-input{height:2.5rem}.company-textarea{min-height:78px;padding:.65rem;resize:vertical}.company-input:focus,.company-textarea:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}
.company-modal{position:fixed;inset:0;z-index:220;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem}.company-modal.hidden{display:none!important}.company-modal-card{width:min(980px,97vw);max-height:94vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}
.bank-section{margin-top:18px;border:1px solid #cbd5e1;background:#f8fafc}.bank-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid #cbd5e1;padding:10px 12px}.bank-card{margin:10px;border:1px solid #cbd5e1;background:#fff}.bank-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;border-bottom:1px solid #e2e8f0;background:#f8fafc;padding:7px 10px}.bank-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:10px}.bank-wide{grid-column:1/-1}.bank-flags{display:flex;align-items:center;gap:16px;flex-wrap:wrap}.bank-flag{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800}.bank-empty{padding:18px;text-align:center;color:#64748b;font-size:12px;font-weight:700}
@media(max-width:760px){.bank-grid{grid-template-columns:1fr}.bank-wide{grid-column:auto}}
</style>
@endpush

@section('content')
<div class="app-workspace">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="mr-auto">
            <h1 class="text-xl font-black text-slate-950">Company Master</h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Maintain Company, GST, Address and its Bank Details from one place.</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <input name="search" value="{{ $search }}" class="company-input w-72" placeholder="Search company / GST / bank...">
            <button class="h-10 border border-slate-400 bg-white px-4 text-sm font-bold hover:bg-slate-50">Search</button>
            @if($search)<a href="{{ route('masters.companies.index') }}" class="h-10 border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600">Clear</a>@endif
        </form>

        <button type="button" id="addCompanyBtn" class="h-10 bg-emerald-800 px-5 text-sm font-bold text-white hover:bg-emerald-900">+ Add Company</button>
    </div>

    <div class="mb-3 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900">
        Bank Details are now maintained inside Company Master. Separate Bank Master is no longer required for normal setup.
    </div>

    <div class="company-card overflow-x-auto">
        <table class="company-table">
            <thead><tr><th style="width:70px">Sr</th><th>Company Name</th><th style="width:220px">GST Number</th><th>Company Address</th><th>Banks</th><th style="width:150px" class="text-right">Actions</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr tabindex="0" data-company-row>
                    <td>{{ ($items->currentPage()-1)*$items->perPage()+$loop->iteration }}</td>
                    <td class="font-black">{{ $item->name }}</td>
                    <td class="font-bold">{{ $item->gst_no }}</td>
                    <td class="whitespace-pre-line">{{ $item->address }}</td>
                    <td>
                        @forelse($item->banks as $bank)
                            <div class="mb-1 last:mb-0">
                                <b>{{ $bank->name }}</b>
                                @if($bank->is_default)<span class="ml-1 text-[10px] font-black text-emerald-700">DEFAULT</span>@endif
                                @if(!$bank->is_active)<span class="ml-1 text-[10px] font-black text-slate-400">INACTIVE</span>@endif
                            </div>
                        @empty
                            <span class="text-slate-400">No bank configured</span>
                        @endforelse
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <button type="button" class="edit-company font-bold text-blue-700 hover:underline" data-id="{{ $item->id }}" data-record="{{ json_encode([
                            'name'=>$item->name,
                            'gst_no'=>$item->gst_no,
                            'address'=>$item->address,
                            'banks'=>$item->banks->map(fn($bank)=>[
                                'id'=>$bank->id,
                                'name'=>$bank->name,
                                'account_holder_name'=>$bank->account_holder_name,
                                'account_number'=>$bank->account_number,
                                'ifsc_code'=>$bank->ifsc_code,
                                'branch_name'=>$bank->branch_name,
                                'bank_address'=>$bank->bank_address,
                                'opening_balance'=>(float)$bank->opening_balance,
                                'is_default'=>(bool)$bank->is_default,
                                'is_active'=>(bool)$bank->is_active,
                            ])->values()->all(),
                        ]) }}">Edit</button>
                        <form method="POST" action="{{ route('masters.companies.destroy',$item) }}" class="ml-3 inline" onsubmit="return confirm('Delete this company?')">@csrf @method('DELETE')<button class="font-bold text-red-700 hover:underline">Delete</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-10 text-center text-slate-400">No companies added yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</div>

<div id="companyModal" class="company-modal hidden" role="dialog" aria-modal="true">
    <div class="company-modal-card">
        <div class="flex items-center justify-between bg-emerald-950 px-5 py-3 text-white"><h2 id="companyModalTitle" class="font-black">Add Company</h2><button type="button" id="companyCloseBtn" class="text-2xl">×</button></div>
        <form id="companyForm" method="POST" action="{{ route('masters.companies.store') }}" class="p-5">@csrf
            <div id="companyMethodSlot"></div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="md:col-span-2"><span class="mb-1 block text-xs font-black uppercase text-slate-600">Company Name <span class="text-red-600">*</span></span><input name="name" data-company-field="name" class="company-input" maxlength="150" required></label>
                <label class="md:col-span-2"><span class="mb-1 block text-xs font-black uppercase text-slate-600">GST Number <span class="text-red-600">*</span></span><input name="gst_no" data-company-field="gst_no" class="company-input uppercase" maxlength="30" required></label>
                <label class="md:col-span-2"><span class="mb-1 block text-xs font-black uppercase text-slate-600">Company Address <span class="text-red-600">*</span></span><textarea name="address" data-company-field="address" class="company-textarea" maxlength="2000" required></textarea></label>
            </div>

            <section class="bank-section">
                <div class="bank-section-head">
                    <div><div class="font-black text-slate-900">Bank Details</div><div class="text-xs font-semibold text-slate-500">Add one or more banks for this company. One active bank will always remain Default.</div></div>
                    <button type="button" id="addBankBtn" class="border border-emerald-700 bg-white px-3 py-2 text-xs font-black text-emerald-800 hover:bg-emerald-50">+ Add Bank</button>
                </div>
                <div id="companyBankRows"></div>
            </section>

            <div class="mt-5 flex justify-end gap-2"><button type="button" id="companyCancelBtn" class="border border-slate-400 px-5 py-2.5 text-sm font-bold">Cancel</button><button type="submit" class="bg-emerald-800 px-6 py-2.5 text-sm font-black text-white">Save Company</button></div>
            <div class="mt-2 text-right text-xs font-semibold text-slate-500">Insert = Add Company · Enter = Next · Ctrl+S = Save · Esc = Close</div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const modal=document.getElementById('companyModal'),form=document.getElementById('companyForm'),title=document.getElementById('companyModalTitle'),methodSlot=document.getElementById('companyMethodSlot'),addBtn=document.getElementById('addCompanyBtn'),bankRows=document.getElementById('companyBankRows'),addBankBtn=document.getElementById('addBankBtn');
    const storeUrl=@json(route('masters.companies.store')),baseUrl=@json(url('/masters/companies'));
    let opener=null,banks=[];
    const esc=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
    const field=name=>form.querySelector(`[data-company-field="${name}"]`);
    const blankBank=(makeDefault=false)=>({id:null,name:'',account_holder_name:'',account_number:'',ifsc_code:'',branch_name:'',bank_address:'',opening_balance:0,is_default:makeDefault,is_active:true,_delete:false});

    function captureBanks(){
        bankRows.querySelectorAll('[data-bank-card]').forEach(card=>{
            const i=Number(card.dataset.bankCard),b=banks[i];if(!b)return;
            card.querySelectorAll('[data-bank-field]').forEach(el=>{const key=el.dataset.bankField;b[key]=el.type==='checkbox'?el.checked:el.value;});
        });
    }
    function renderBanks(){
        const visible=banks.filter(b=>!b._delete);
        if(!visible.length){bankRows.innerHTML='<div class="bank-empty">No bank added. Click + Add Bank.</div>'+banks.map((b,i)=>b._delete&&b.id?`<input type="hidden" name="banks[${i}][id]" value="${b.id}"><input type="hidden" name="banks[${i}][_delete]" value="1">`:'').join('');return;}
        bankRows.innerHTML=banks.map((b,i)=>{
            if(b._delete)return b.id?`<input type="hidden" name="banks[${i}][id]" value="${b.id}"><input type="hidden" name="banks[${i}][_delete]" value="1">`:'';
            return `<div class="bank-card" data-bank-card="${i}">
                ${b.id?`<input type="hidden" name="banks[${i}][id]" value="${b.id}">`:''}
                <div class="bank-card-head"><b>Bank ${i+1}${b.is_default?' · Default':''}</b><button type="button" class="text-xs font-black text-red-700 hover:underline" data-remove-bank="${i}">Remove</button></div>
                <div class="bank-grid">
                    <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Bank Name *</span><input class="company-input" data-bank-field="name" name="banks[${i}][name]" value="${esc(b.name)}" maxlength="255"></label>
                    <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Account Holder Name</span><input class="company-input" data-bank-field="account_holder_name" name="banks[${i}][account_holder_name]" value="${esc(b.account_holder_name)}" maxlength="255"></label>
                    <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Account Number</span><input class="company-input" data-bank-field="account_number" name="banks[${i}][account_number]" value="${esc(b.account_number)}" maxlength="100"></label>
                    <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">IFSC Code</span><input class="company-input uppercase" data-bank-field="ifsc_code" name="banks[${i}][ifsc_code]" value="${esc(b.ifsc_code)}" maxlength="30"></label>
                    <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Branch</span><input class="company-input" data-bank-field="branch_name" name="banks[${i}][branch_name]" value="${esc(b.branch_name)}" maxlength="255"></label>
                    <label><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Opening Balance</span><input type="number" step="0.01" class="company-input" data-bank-field="opening_balance" name="banks[${i}][opening_balance]" value="${esc(b.opening_balance??0)}"></label>
                    <label class="bank-wide"><span class="mb-1 block text-[10px] font-black uppercase text-slate-500">Bank Address</span><textarea class="company-textarea" data-bank-field="bank_address" name="banks[${i}][bank_address]" maxlength="1000">${esc(b.bank_address)}</textarea></label>
                    <div class="bank-wide bank-flags">
                        <label class="bank-flag"><input type="hidden" name="banks[${i}][is_default]" value="0"><input type="checkbox" data-bank-field="is_default" data-default-bank="${i}" name="banks[${i}][is_default]" value="1" ${b.is_default?'checked':''}> Default Bank</label>
                        <label class="bank-flag"><input type="hidden" name="banks[${i}][is_active]" value="0"><input type="checkbox" data-bank-field="is_active" name="banks[${i}][is_active]" value="1" ${b.is_active?'checked':''}> Active</label>
                    </div>
                </div>
            </div>`;
        }).join('');
    }
    function normalizeBanks(list){return (Array.isArray(list)?list:[]).map(b=>({...blankBank(false),...b,is_default:!!b.is_default,is_active:b.is_active!==false,_delete:false}));}
    function reset(){form.reset();form.action=storeUrl;methodSlot.innerHTML='';banks=[blankBank(true)];renderBanks();}
    function openCreate(origin=addBtn){opener=origin||document.activeElement;reset();title.textContent='Add Company';modal.classList.remove('hidden');setTimeout(()=>field('name').focus(),20);}
    function openEdit(button){opener=button;let r={};try{r=JSON.parse(button.dataset.record||'{}');}catch(e){window.AppToast?.('Unable to open this company. Please refresh and try again.','error');return;}reset();form.action=baseUrl+'/'+button.dataset.id;methodSlot.innerHTML='<input type="hidden" name="_method" value="PUT">';title.textContent='Edit Company';field('name').value=r.name||'';field('gst_no').value=r.gst_no||'';field('address').value=r.address||'';banks=normalizeBanks(r.banks);if(!banks.length)banks=[blankBank(true)];renderBanks();modal.classList.remove('hidden');setTimeout(()=>field('name').focus(),20);}
    function closeModal(){modal.classList.add('hidden');const previous=opener;opener=null;setTimeout(()=>previous?.focus?.(),0);}

    addBtn.addEventListener('click',()=>openCreate(addBtn));
    document.querySelectorAll('.edit-company').forEach(button=>button.addEventListener('click',()=>openEdit(button)));
    document.getElementById('companyCloseBtn').addEventListener('click',closeModal);document.getElementById('companyCancelBtn').addEventListener('click',closeModal);modal.addEventListener('mousedown',e=>{if(e.target===modal)closeModal();});
    addBankBtn.addEventListener('click',()=>{captureBanks();banks.push(blankBank(!banks.some(b=>!b._delete&&b.is_default&&b.is_active)));renderBanks();setTimeout(()=>bankRows.querySelector('[data-bank-card]:last-of-type input[data-bank-field="name"]')?.focus(),20);});
    bankRows.addEventListener('click',e=>{const remove=e.target.closest('[data-remove-bank]');if(!remove)return;captureBanks();const i=Number(remove.dataset.removeBank);if(banks[i]?.id)banks[i]._delete=true;else banks.splice(i,1);const activeVisible=banks.filter(b=>!b._delete&&b.is_active);if(activeVisible.length&&!activeVisible.some(b=>b.is_default))activeVisible[0].is_default=true;renderBanks();});
    bankRows.addEventListener('change',e=>{const checkbox=e.target.closest('[data-default-bank]');if(!checkbox)return;captureBanks();const i=Number(checkbox.dataset.defaultBank);if(checkbox.checked)banks.forEach((b,idx)=>{if(idx!==i)b.is_default=false;});renderBanks();});
    field('gst_no').addEventListener('input',()=>{field('gst_no').value=field('gst_no').value.toUpperCase();});
    bankRows.addEventListener('input',e=>{if(e.target.matches('[data-bank-field="ifsc_code"]'))e.target.value=e.target.value.toUpperCase();});
    form.addEventListener('submit',()=>captureBanks());
    form.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();e.stopPropagation();closeModal();return;}if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();form.requestSubmit();return;}if(e.key!=='Enter'||e.target.tagName==='TEXTAREA'||e.target.type==='checkbox'||e.target.tagName==='BUTTON')return;const focusables=Array.from(form.querySelectorAll('input:not([type="hidden"]):not([disabled]),textarea:not([disabled]),button:not([disabled])')).filter(el=>el.offsetParent!==null);const i=focusables.indexOf(e.target);if(i<0)return;e.preventDefault();if(i<focusables.length-1)focusables[i+1].focus();else form.requestSubmit();});
    const rows=Array.from(document.querySelectorAll('[data-company-row]'));rows.forEach((row,index)=>row.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();rows[Math.min(index+1,rows.length-1)]?.focus();}else if(e.key==='ArrowUp'){e.preventDefault();rows[Math.max(index-1,0)]?.focus();}else if(e.key==='Enter'){e.preventDefault();row.querySelector('.edit-company')?.click();}}));
    document.addEventListener('keydown',e=>{if(e.defaultPrevented)return;if(e.key==='Insert'&&modal.classList.contains('hidden')){e.preventDefault();openCreate(document.activeElement);}});
});
</script>
@endpush
