@extends('layouts.app')
@section('title', 'SO Number Series | XYZ Transport')
@section('page-nav-target', '#nav-masters')

@push('styles')
<style>
.series-card{border:1px solid #cbd5e1;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.series-table{width:100%;border-collapse:collapse;font-size:.86rem}.series-table th{background:#f1f5f9;border-bottom:1px solid #94a3b8;padding:.65rem .75rem;text-align:left;font-size:.72rem;text-transform:uppercase;color:#475569;white-space:nowrap}.series-table td{border-bottom:1px solid #e2e8f0;padding:.62rem .75rem}.series-table tr:hover td{background:#ecfdf5}
.series-input{height:2.5rem;width:100%;border:1px solid #94a3b8;padding:0 .65rem;outline:none;background:#fff}.series-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}
.series-modal{position:fixed;inset:0;z-index:220;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem}.series-modal.hidden{display:none!important}.series-modal-card{width:min(760px,96vw);max-height:92vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}
.series-preview{border:1px solid #a7f3d0;background:#ecfdf5;padding:.8rem 1rem;font-size:1rem;font-weight:900;color:#065f46;letter-spacing:.04em}
</style>
@endpush

@section('content')
<div class="app-workspace">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="mr-auto">
            <h1 class="text-xl font-black text-slate-950">SO Number Series Master</h1>
            <p class="mt-1 text-sm font-medium text-slate-500">Configure separate automatic SO number formats for GST Registered and Non-GST customers.</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <input name="search" value="{{ $search }}" class="series-input w-72" placeholder="Search prefix / suffix / type...">
            <button class="h-10 border border-slate-400 bg-white px-4 text-sm font-bold hover:bg-slate-50">Search</button>
            @if($search)
                <a href="{{ route('masters.so-series.index') }}" class="h-10 border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600">Clear</a>
            @endif
        </form>

        <button type="button" id="addSeriesBtn" class="h-10 bg-emerald-800 px-5 text-sm font-bold text-white hover:bg-emerald-900">+ Add Series</button>
    </div>

    <div class="mb-3 border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
        Government customer SO numbers will remain manual in the next phase. This master is only for GST Registered and Non-GST auto series.
    </div>

    <div class="series-card overflow-x-auto">
        <table class="series-table">
            <thead>
                <tr>
                    <th>Series Type</th>
                    <th>Prefix</th>
                    <th>Start No.</th>
                    <th>Next No.</th>
                    <th>Digits</th>
                    <th>Suffix</th>
                    <th>Next SO Preview</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr tabindex="0" data-series-row>
                    <td class="font-black">{{ $item->type_label }}</td>
                    <td>{{ $item->prefix !== '' ? $item->prefix : '-' }}</td>
                    <td>{{ number_format($item->start_number) }}</td>
                    <td class="font-black">{{ number_format($item->next_number) }}</td>
                    <td>{{ $item->number_digits }}</td>
                    <td>{{ $item->suffix ?: '-' }}</td>
                    <td class="font-black text-emerald-800">{{ $item->formatNumber() }}</td>
                    <td><span class="font-bold {{ $item->is_active ? 'text-emerald-700' : 'text-slate-400' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="whitespace-nowrap text-right">
                        <button
                            type="button"
                            class="edit-series font-bold text-blue-700 hover:underline"
                            data-id="{{ $item->id }}"
                            data-record="{{ json_encode([
                                'series_type' => $item->series_type,
                                'prefix' => $item->prefix,
                                'start_number' => (int) $item->start_number,
                                'next_number' => (int) $item->next_number,
                                'number_digits' => (int) $item->number_digits,
                                'suffix' => $item->suffix,
                                'is_active' => (bool) $item->is_active,
                            ]) }}"
                        >Edit</button>
                        <form method="POST" action="{{ route('masters.so-series.destroy', $item) }}" class="ml-3 inline" onsubmit="return confirm('Delete this SO Number Series?')">
                            @csrf
                            @method('DELETE')
                            <button class="font-bold text-red-700 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-10 text-center text-slate-400">No SO number series configured yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>

<div id="seriesModal" class="series-modal hidden" role="dialog" aria-modal="true">
    <div class="series-modal-card">
        <div class="flex items-center justify-between bg-emerald-950 px-5 py-3 text-white">
            <h2 id="seriesModalTitle" class="font-black">Add SO Number Series</h2>
            <button type="button" id="seriesCloseBtn" class="text-2xl">×</button>
        </div>

        <form id="seriesForm" method="POST" action="{{ route('masters.so-series.store') }}" class="p-5">
            @csrf
            <div id="seriesMethodSlot"></div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label>
                    <span class="mb-1 block text-xs font-black uppercase text-slate-600">Series Type <span class="text-red-600">*</span></span>
                    <select name="series_type" data-series-field="series_type" class="series-input" required>
                        <option value="">Select Series Type</option>
                        <option value="gst">GST Registered</option>
                        <option value="non_gst">Non-GST</option>
                    </select>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-black uppercase text-slate-600">Prefix</span>
                    <input name="prefix" data-series-field="prefix" class="series-input" placeholder="Example: SO/GST/">
                </label>

                <label>
                    <span class="mb-1 block text-xs font-black uppercase text-slate-600">Start Number <span class="text-red-600">*</span></span>
                    <input type="number" min="1" step="1" name="start_number" data-series-field="start_number" class="series-input" value="1" required>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-black uppercase text-slate-600">Next Number <span class="text-red-600">*</span></span>
                    <input type="number" min="1" step="1" name="next_number" data-series-field="next_number" class="series-input" value="1" required>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-black uppercase text-slate-600">Number Digits <span class="text-red-600">*</span></span>
                    <input type="number" min="1" max="12" step="1" name="number_digits" data-series-field="number_digits" class="series-input" value="4" required>
                </label>

                <label>
                    <span class="mb-1 block text-xs font-black uppercase text-slate-600">Suffix</span>
                    <input name="suffix" data-series-field="suffix" class="series-input" placeholder="Example: /26-27">
                </label>

                <div class="md:col-span-2">
                    <span class="mb-1 block text-xs font-black uppercase text-slate-600">Next SO Number Preview</span>
                    <div id="seriesPreview" class="series-preview">0001</div>
                </div>

                <label class="md:col-span-2 flex items-center gap-3 border border-slate-200 bg-slate-50 p-3 font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" data-series-field="is_active" checked class="h-5 w-5">
                    Active Series
                </label>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="seriesCancelBtn" class="border border-slate-400 px-5 py-2.5 text-sm font-bold">Cancel</button>
                <button type="submit" class="bg-emerald-800 px-6 py-2.5 text-sm font-black text-white">Save Series</button>
            </div>
            <div class="mt-2 text-right text-xs font-semibold text-slate-500">Insert = Add · Enter = Next · Ctrl+S = Save · Esc = Close</div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('seriesModal');
    const form = document.getElementById('seriesForm');
    const title = document.getElementById('seriesModalTitle');
    const methodSlot = document.getElementById('seriesMethodSlot');
    const addBtn = document.getElementById('addSeriesBtn');
    const preview = document.getElementById('seriesPreview');
    const storeUrl = @json(route('masters.so-series.store'));
    const baseUrl = @json(url('/masters/so-number-series'));
    let opener = null;

    const field = name => form.querySelector(`[data-series-field="${name}"]`);
    const ordered = () => Array.from(form.querySelectorAll('[data-series-field]')).filter(el => !el.disabled && el.offsetParent !== null);

    function refreshPreview() {
        const prefix = field('prefix').value || '';
        const suffix = field('suffix').value || '';
        const next = Math.max(1, parseInt(field('next_number').value || '1', 10));
        const digits = Math.max(1, Math.min(12, parseInt(field('number_digits').value || '4', 10)));
        preview.textContent = prefix + String(next).padStart(digits, '0') + suffix;
    }

    ['prefix','next_number','number_digits','suffix'].forEach(name => {
        field(name).addEventListener('input', refreshPreview);
        field(name).addEventListener('change', refreshPreview);
    });

    function resetForm() {
        form.reset();
        form.action = storeUrl;
        methodSlot.innerHTML = '';
        field('start_number').value = '1';
        field('next_number').value = '1';
        field('number_digits').value = '4';
        field('is_active').checked = true;
        refreshPreview();
    }

    function openCreate(origin = addBtn) {
        opener = origin || document.activeElement;
        resetForm();
        title.textContent = 'Add SO Number Series';
        modal.classList.remove('hidden');
        setTimeout(() => field('series_type').focus(), 20);
    }

    function openEdit(button) {
        opener = button;
        let record = {};
        try {
            record = JSON.parse(button.dataset.record || '{}');
        } catch (e) {
            window.AppToast?.('Unable to open this series. Please refresh and try again.', 'error');
            return;
        }

        resetForm();
        form.action = baseUrl + '/' + button.dataset.id;
        methodSlot.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        title.textContent = 'Edit SO Number Series';

        ['series_type','prefix','start_number','next_number','number_digits','suffix'].forEach(name => {
            field(name).value = record[name] ?? '';
        });
        field('is_active').checked = !!record.is_active;
        refreshPreview();

        modal.classList.remove('hidden');
        setTimeout(() => field('series_type').focus(), 20);
    }

    function closeModal() {
        modal.classList.add('hidden');
        const back = opener;
        opener = null;
        setTimeout(() => back?.focus?.(), 0);
    }

    addBtn.addEventListener('click', () => openCreate(addBtn));
    document.querySelectorAll('.edit-series').forEach(button => button.addEventListener('click', () => openEdit(button)));
    document.getElementById('seriesCloseBtn').addEventListener('click', closeModal);
    document.getElementById('seriesCancelBtn').addEventListener('click', closeModal);
    modal.addEventListener('mousedown', e => { if (e.target === modal) closeModal(); });

    form.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
            return;
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            form.requestSubmit();
            return;
        }
        if (e.key !== 'Enter' || e.target.type === 'checkbox') return;

        const list = ordered();
        const index = list.indexOf(e.target);
        if (index < 0) return;
        e.preventDefault();
        if (index < list.length - 1) list[index + 1].focus();
        else form.requestSubmit();
    });

    const rows = Array.from(document.querySelectorAll('[data-series-row]'));
    rows.forEach((row, index) => row.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            rows[Math.min(index + 1, rows.length - 1)]?.focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            rows[Math.max(index - 1, 0)]?.focus();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            row.querySelector('.edit-series')?.click();
        }
    }));

    document.addEventListener('keydown', e => {
        if (e.defaultPrevented) return;
        if (e.key === 'Insert' && modal.classList.contains('hidden')) {
            e.preventDefault();
            openCreate(document.activeElement);
        }
    });

    refreshPreview();
});
</script>
@endpush
