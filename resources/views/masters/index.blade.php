@extends('layouts.app')
@section('title', $config['title'].' | XYZ Transport')

@push('styles')
<style>
.master-card{border:1px solid #cbd5e1;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.master-table{width:100%;border-collapse:collapse;font-size:.86rem}.master-table th{background:#f1f5f9;border-bottom:1px solid #94a3b8;padding:.65rem .75rem;text-align:left;font-size:.72rem;text-transform:uppercase;color:#475569}.master-table td{border-bottom:1px solid #e2e8f0;padding:.62rem .75rem}.master-table tr:hover td{background:#ecfdf5}
.master-input{height:2.5rem;width:100%;border:1px solid #94a3b8;padding:0 .65rem;outline:none;background:white}.master-input:focus,.master-textarea:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.master-textarea{width:100%;border:1px solid #94a3b8;padding:.6rem;outline:none;min-height:78px}
.master-modal{position:fixed;inset:0;z-index:200;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem}.master-modal.hidden{display:none!important}.master-modal-card{width:min(760px,96vw);max-height:90vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}
</style>
@endpush

@section('content')
<div class="app-workspace">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="mr-auto"><h1 class="text-xl font-black text-slate-950">{{ $config['title'] }}</h1><p class="mt-1 text-sm font-medium text-slate-500">Master records used directly in Voucher Entry.</p></div>
        <form method="GET" class="flex items-center gap-2">
            <input name="search" value="{{ $search }}" autofocus class="master-input w-72" placeholder="Search name / code / phone / GST...">
            <button class="h-10 border border-slate-400 bg-white px-4 text-sm font-bold hover:bg-slate-50">Search</button>
            @if($search)<a href="{{ route('masters.index',['type'=>$type]) }}" class="h-10 border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600">Clear</a>@endif
        </form>
        <button type="button" id="addMasterBtn" class="h-10 bg-emerald-800 px-5 text-sm font-bold text-white hover:bg-emerald-900">+ Add {{ $config['singular'] }}</button>
    </div>

    <div class="master-card overflow-x-auto">
        <table class="master-table">
            <thead><tr>
                @foreach($config['columns'] as $column)<th>{{ $column }}</th>@endforeach
                <th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr tabindex="0" data-master-row>
                    @if(in_array($type,['customers','suppliers'],true))
                        <td>{{ $item->code ?: '-' }}</td><td class="font-bold text-slate-900">{{ $item->name }}</td><td>{{ $item->phone ?: '-' }}</td><td>{{ $item->gst_no ?: '-' }}</td><td>₹{{ number_format((float)$item->opening_balance,2) }}</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @elseif($type==='vehicle-types')
                        <td class="font-bold">{{ $item->name }}</td><td>{{ $item->description ?: '-' }}</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @elseif($type==='gst-rates')
                        <td class="font-bold">{{ $item->name }}</td><td>{{ rtrim(rtrim(number_format((float)$item->rate,2,'.',''),'0'),'.') }}%</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @elseif($type==='transport-names')
                        <td class="font-bold">{{ $item->name }}</td><td>{{ $item->gst_no ?: '-' }}</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @elseif($type==='banks')
                        <td class="font-bold">{{ $item->company?->name ?: '-' }}</td>
                        <td class="font-bold">{{ $item->name }}</td>
                        <td>{{ $item->account_number ?: '-' }}</td>
                        <td>{{ $item->ifsc_code ?: '-' }}</td>
                        <td>₹{{ number_format((float)$item->opening_balance,2) }}</td>
                        <td><span class="font-bold {{ $item->is_default?'text-emerald-700':'text-slate-400' }}">{{ $item->is_default?'Default':'-' }}</span></td>
                        <td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @else
                        <td class="font-bold">{{ $item->name }}</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @endif
                    <td class="whitespace-nowrap text-right">
                        <button type="button" class="edit-master font-bold text-blue-700 hover:underline" data-id="{{ $item->id }}" data-record="{{ json_encode($item->toArray()) }}">Edit</button>
                        <form method="POST" action="{{ route('masters.destroy',['type'=>$type,'id'=>$item->id]) }}" class="ml-3 inline" onsubmit="return confirm('Delete this record?')">@csrf @method('DELETE')<button class="font-bold text-red-700 hover:underline">Delete</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($config['columns'])+1 }}" class="py-10 text-center text-slate-400">No records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</div>

<div id="masterModal" class="master-modal hidden" role="dialog" aria-modal="true">
    <div class="master-modal-card">
        <div class="flex items-center justify-between bg-emerald-950 px-5 py-3 text-white"><h2 id="modalTitle" class="font-black">Add {{ $config['singular'] }}</h2><button type="button" id="closeMasterBtn" class="text-2xl">×</button></div>
        <form id="masterForm" method="POST" action="{{ route('masters.store',['type'=>$type]) }}" class="p-5">@csrf
            <div id="methodSlot"></div>
            @include('masters.partials.form-fields', ['config' => $config])
            <div class="mt-5 flex justify-end gap-2"><button type="button" id="cancelMasterBtn" class="border border-slate-400 px-5 py-2.5 text-sm font-bold">Cancel</button><button type="submit" class="bg-emerald-800 px-6 py-2.5 text-sm font-black text-white">Save</button></div>
            <div class="mt-2 text-right text-xs font-semibold text-slate-500">Insert = Add · Enter = next field · Ctrl+S = Save · Esc = Close</div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const modal=document.getElementById('masterModal'), form=document.getElementById('masterForm'), title=document.getElementById('modalTitle'), methodSlot=document.getElementById('methodSlot');
    const addBtn=document.getElementById('addMasterBtn');
    const storeUrl=@json(route('masters.store',['type'=>$type])); const baseUrl=@json(url('/masters/'.$type)); const singular=@json($config['singular']);
    const fields=()=>Array.from(form.querySelectorAll('[data-master-field]'));
    const visibleFields=()=>fields().filter(x=>!x.disabled && x.offsetParent!==null);
    const gov=form.querySelector('[name=is_government_employee]');
    const billNote=form.querySelector('[name=bill_note]');
    const billNoteWrap=form.querySelector('[data-field-wrap=bill_note]');
    let opener=null;

    function syncGovernmentNote(prefill=false){
        if(!gov||!billNote||!billNoteWrap)return;
        const show=gov.checked;
        billNoteWrap.classList.toggle('hidden',!show);
        billNote.disabled=!show;
        if(show&&prefill&&!billNote.value.trim()) billNote.value=billNote.dataset.defaultValue||'GST @5% WILL BE PAID BY SERVICE USER UNDER RCM (If Applicable)';
    }
    function openCreate(origin=addBtn){
        opener=origin||document.activeElement;
        form.action=storeUrl;methodSlot.innerHTML='';title.textContent='Add '+singular;form.reset();
        form.querySelectorAll('[data-create-hidden=\"1\"]').forEach(wrap=>{wrap.classList.add('hidden');const input=wrap.querySelector('[data-master-field]');if(input)input.disabled=true;});
        const active=form.querySelector('[name=is_active]');if(active)active.checked=true;if(gov)gov.checked=false;syncGovernmentNote(false);
        modal.classList.remove('hidden');setTimeout(()=>visibleFields().find(x=>x.type!=='checkbox')?.focus(),30);
    }
    function openEdit(btn){
        opener=btn;
        let r={};
        try{r=JSON.parse(btn.dataset.record||'{}');}
        catch(err){window.AppToast?.('Unable to open this record for editing. Please refresh and try again.','error');return;}
        form.action=baseUrl+'/'+btn.dataset.id;methodSlot.innerHTML='<input type="hidden" name="_method" value="PUT">';title.textContent='Edit '+singular;
        form.querySelectorAll('[data-create-hidden=\"1\"]').forEach(wrap=>{wrap.classList.remove('hidden');const input=wrap.querySelector('[data-master-field]');if(input)input.disabled=false;});
        fields().forEach(el=>{const v=r[el.dataset.masterField];if(el.type==='checkbox')el.checked=!!v;else el.value=v??'';});syncGovernmentNote(true);
        modal.classList.remove('hidden');setTimeout(()=>visibleFields().find(x=>x.type!=='checkbox')?.focus(),30);
    }
    function close(){modal.classList.add('hidden');const back=opener;opener=null;setTimeout(()=>back?.focus?.(),0);}

    gov?.addEventListener('change',()=>syncGovernmentNote(true));
    addBtn.addEventListener('click',()=>openCreate(addBtn));
    document.querySelectorAll('.edit-master').forEach(b=>b.addEventListener('click',()=>openEdit(b)));
    document.getElementById('closeMasterBtn').addEventListener('click',close);
    document.getElementById('cancelMasterBtn').addEventListener('click',close);
    modal.addEventListener('mousedown',e=>{if(e.target===modal)close();});

    form.addEventListener('keydown',e=>{
        if(e.key==='Enter'&&e.target.tagName!=='TEXTAREA'){
            const list=visibleFields();const i=list.indexOf(e.target);if(i<0)return;e.preventDefault();
            if(i<list.length-1)list[i+1].focus();else form.requestSubmit();
        }
    });

    const rows=Array.from(document.querySelectorAll('[data-master-row]'));
    rows.forEach((row,i)=>row.addEventListener('keydown',e=>{
        if(e.key==='ArrowDown'){e.preventDefault();rows[Math.min(i+1,rows.length-1)]?.focus();}
        else if(e.key==='ArrowUp'){e.preventDefault();rows[Math.max(i-1,0)]?.focus();}
        else if(e.key==='Enter'){e.preventDefault();const btn=row.querySelector('.edit-master');if(btn)openEdit(btn);}
    }));

    document.addEventListener('keydown',e=>{
        if(e.key==='Insert'&&modal.classList.contains('hidden')){e.preventDefault();openCreate(document.activeElement);}
        else if(e.key==='Escape'&&!modal.classList.contains('hidden')){e.preventDefault();e.stopPropagation();close();}
        else if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'&&!modal.classList.contains('hidden')){e.preventDefault();form.requestSubmit();}
        else if(e.key==='/'&&modal.classList.contains('hidden')&&!['INPUT','TEXTAREA'].includes(document.activeElement?.tagName)){e.preventDefault();document.querySelector('input[name=search]')?.focus();}
    });
});
</script>
@endpush
