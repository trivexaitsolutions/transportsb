@extends('layouts.app')
@section('title', $config['title'].' | XYZ Transport')

@push('styles')
<style>
.master-card{border:1px solid #cbd5e1;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.master-table{width:100%;border-collapse:collapse;font-size:.86rem}.master-table th{background:#f1f5f9;border-bottom:1px solid #94a3b8;padding:.65rem .75rem;text-align:left;font-size:.72rem;text-transform:uppercase;color:#475569}.master-table td{border-bottom:1px solid #e2e8f0;padding:.62rem .75rem}.master-table tr:hover td{background:#ecfdf5}
.master-input{height:2.5rem;width:100%;border:1px solid #94a3b8;padding:0 .65rem;outline:none;background:white}.master-input:focus,.master-textarea:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}.master-textarea{width:100%;border:1px solid #94a3b8;padding:.6rem;outline:none;min-height:78px}
.master-modal{position:fixed;inset:0;z-index:200;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem}.master-modal-card{width:min(760px,96vw);max-height:90vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}
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

    @if(session('success'))<div class="mb-3 border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-3 border border-red-300 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-3 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800"><b>Please correct:</b> {{ $errors->first() }}</div>@endif

    <div class="master-card overflow-x-auto">
        <table class="master-table">
            <thead><tr>
                @foreach($config['columns'] as $column)<th>{{ $column }}</th>@endforeach
                <th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    @if(in_array($type,['customers','suppliers'],true))
                        <td>{{ $item->code ?: '-' }}</td><td class="font-bold text-slate-900">{{ $item->name }}</td><td>{{ $item->phone ?: '-' }}</td><td>{{ $item->gst_no ?: '-' }}</td><td>₹{{ number_format((float)$item->opening_balance,2) }}</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @elseif($type==='vehicle-types')
                        <td class="font-bold">{{ $item->name }}</td><td>{{ $item->description ?: '-' }}</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @else
                        <td class="font-bold">{{ $item->name }}</td><td><span class="font-bold {{ $item->is_active?'text-emerald-700':'text-slate-400' }}">{{ $item->is_active?'Active':'Inactive' }}</span></td>
                    @endif
                    <td class="whitespace-nowrap text-right">
                        <button type="button" class="edit-master font-bold text-blue-700 hover:underline" data-id="{{ $item->id }}" data-record="{{ e(json_encode($item->toArray())) }}">Edit</button>
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
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach($config['fields'] as $field)
                    @if($field['type']==='checkbox')
                        <label class="{{ !empty($field['wide'])?'md:col-span-2':'' }} flex items-center gap-3 border border-slate-200 bg-slate-50 p-3 font-bold text-slate-700"><input type="checkbox" name="{{ $field['name'] }}" value="1" data-master-field="{{ $field['name'] }}" checked class="h-5 w-5"> {{ $field['label'] }}</label>
                    @else
                        <label class="{{ !empty($field['wide'])?'md:col-span-2':'' }}"><span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600">{{ $field['label'] }} @if(!empty($field['required']))<span class="text-red-600">*</span>@endif</span>
                            @if($field['type']==='textarea')
                                <textarea name="{{ $field['name'] }}" data-master-field="{{ $field['name'] }}" class="master-textarea" {{ !empty($field['required'])?'required':'' }}></textarea>
                            @else
                                <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" data-master-field="{{ $field['name'] }}" class="master-input" @if($field['type']==='number') step="0.01" @endif {{ !empty($field['required'])?'required':'' }}>
                            @endif
                        </label>
                    @endif
                @endforeach
            </div>
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
    const storeUrl=@json(route('masters.store',['type'=>$type])); const baseUrl=@json(url('/masters/'.$type)); const singular=@json($config['singular']);
    const fields=()=>Array.from(form.querySelectorAll('[data-master-field]'));
    function openCreate(){form.action=storeUrl;methodSlot.innerHTML='';title.textContent='Add '+singular;form.reset();const active=form.querySelector('[name=is_active]');if(active)active.checked=true;modal.classList.remove('hidden');setTimeout(()=>fields().find(x=>x.type!=='checkbox')?.focus(),30);}
    function openEdit(btn){const r=JSON.parse(btn.dataset.record||'{}');form.action=baseUrl+'/'+btn.dataset.id;methodSlot.innerHTML='<input type="hidden" name="_method" value="PUT">';title.textContent='Edit '+singular;fields().forEach(el=>{const v=r[el.dataset.masterField];if(el.type==='checkbox')el.checked=!!v;else el.value=v??'';});modal.classList.remove('hidden');setTimeout(()=>fields().find(x=>x.type!=='checkbox')?.focus(),30);}
    function close(){modal.classList.add('hidden');}
    document.getElementById('addMasterBtn').addEventListener('click',openCreate);document.querySelectorAll('.edit-master').forEach(b=>b.addEventListener('click',()=>openEdit(b)));document.getElementById('closeMasterBtn').addEventListener('click',close);document.getElementById('cancelMasterBtn').addEventListener('click',close);
    form.addEventListener('keydown',e=>{if(e.key==='Enter'&&e.target.tagName!=='TEXTAREA'){e.preventDefault();const list=fields().filter(x=>!x.disabled);const i=list.indexOf(e.target);if(i>=0&&i<list.length-1)list[i+1].focus();else form.requestSubmit();}});
    document.addEventListener('keydown',e=>{if(e.key==='Insert'&&modal.classList.contains('hidden')){e.preventDefault();openCreate();}else if(e.key==='Escape'&&!modal.classList.contains('hidden')){e.preventDefault();e.stopPropagation();close();}else if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'&&!modal.classList.contains('hidden')){e.preventDefault();form.requestSubmit();}});
});
</script>
@endpush
