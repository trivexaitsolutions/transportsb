@extends('layouts.app')
@section('title','Settings | XYZ Transport')

@push('styles')
<style>
.settings-card{max-width:860px;border:1px solid #cbd5e1;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.settings-input{height:2.6rem;width:100%;border:1px solid #94a3b8;padding:0 .7rem;outline:none;background:#fff}.settings-input:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}
.preview{max-width:100%;max-height:180px;border:1px solid #cbd5e1;background:#f8fafc;object-fit:contain}
</style>
@endpush

@section('content')
<div class="app-workspace">
    <div class="mb-4">
        <h1 class="text-xl font-black text-slate-950">Print Settings</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Letterhead and top blank space used by Voucher Bill and Customer Ledger bill print.</p>
    </div>

    <form method="POST" action="{{ route('masters.settings.update') }}" enctype="multipart/form-data" class="settings-card p-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <label class="md:col-span-2">
                <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600">Letterhead Image</span>
                <input type="file" name="letterhead_image" accept="image/png,image/jpeg,image/webp" class="block w-full border border-slate-300 bg-white p-2 text-sm">
                <span class="mt-1 block text-xs font-semibold text-slate-500">Optional. If uploaded, the image is shown at the top of both customer bill print formats.</span>
            </label>

            @if($settings->letterhead_image)
                <div class="md:col-span-2">
                    <div class="mb-2 text-xs font-black uppercase tracking-wide text-slate-600">Current Letterhead</div>
                    <img src="{{ asset($settings->letterhead_image) }}" alt="Current Letterhead" class="preview">
                    <label class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-red-700">
                        <input type="checkbox" name="remove_letterhead" value="1" class="h-4 w-4"> Remove current letterhead
                    </label>
                </div>
            @endif

            <label>
                <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600">Letterhead Top Blank Space (mm)</span>
                <input type="number" name="letterhead_top_margin_mm" value="{{ old('letterhead_top_margin_mm', (float)$settings->letterhead_top_margin_mm) }}" min="0" max="200" step="0.5" class="settings-input" required>
                <span class="mt-1 block text-xs font-semibold text-slate-500">Use this when printing below pre-printed physical letterhead. Set 0 for no extra blank space.</span>
            </label>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-emerald-800 px-6 py-2.5 text-sm font-black text-white hover:bg-emerald-900">Save Settings</button>
        </div>
    </form>
</div>
@endsection
