@extends('layouts.app')
@section('title', 'Print Settings | XYZ Transport')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-4">
        <h1 class="text-xl font-black text-slate-900">Print Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Control the blank space reserved for pre-printed letterhead on customer bill prints.</p>
    </div>

    <div class="border border-slate-300 bg-white shadow-sm">
        <form method="POST" action="{{ route('masters.settings.update') }}" class="p-5">
            @csrf
            @method('PUT')

            <label class="block">
                <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600">Letterhead Top Blank Space (mm)</span>
                <input
                    type="number"
                    name="letterhead_top_margin_mm"
                    value="{{ old('letterhead_top_margin_mm', rtrim(rtrim(number_format((float) $settings->letterhead_top_margin_mm, 2, '.', ''), '0'), '.')) }}"
                    min="0"
                    max="120"
                    step="1"
                    class="h-10 w-48 border border-slate-400 bg-white px-3 font-bold outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100"
                    autofocus
                >
                <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-500">
                    Enter how much blank space should be left at the top before the invoice content starts. Use <b>0</b> for no extra blank space. Example: enter <b>45</b> to leave 45 mm for an already printed physical letterhead.
                </p>
            </label>

            <div class="mt-5 flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <span class="text-xs font-semibold text-slate-500">This setting applies to Voucher Bill Print and Customer Ledger selected-bills print.</span>
                <button type="submit" class="bg-emerald-800 px-6 py-2.5 text-sm font-black text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-amber-300">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        document.querySelector('form')?.requestSubmit();
    }
});
</script>
@endpush
