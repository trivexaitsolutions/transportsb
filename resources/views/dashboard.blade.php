@extends('layouts.app')
@section('title','Dashboard | XYZ Transport')
@section('content')
<div class="app-workspace">
    <div class="mb-5 flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-slate-900">Dashboard</h1><p class="mt-1 text-sm text-slate-500">Transport voucher and outstanding summary.</p></div>
        <a href="{{ route('vouchers.index') }}" class="bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-900">Open Voucher Entry</a>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 xl:grid-cols-6">
        @php $cards=[['Today Vouchers',$todayCount,''],['Customer Freight',$todayCustomerFreight,'₹'],['Supplier Freight',$todaySupplierFreight,'₹'],['Base Profit',$todayProfit,'₹'],['Customer Outstanding',$customerOutstanding,'₹'],['Supplier Outstanding',$supplierOutstanding,'₹']]; @endphp
        @foreach($cards as [$label,$value,$prefix])
        <div class="border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</div><div class="mt-2 text-2xl font-black text-slate-950">{{ $prefix }}{{ is_numeric($value) ? number_format($value, $prefix ? 2 : 0) : $value }}</div></div>
        @endforeach
    </div>
    <div class="mt-5 border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3"><div><h2 class="font-bold text-slate-900">Recent Vouchers</h2><div class="text-xs text-slate-500">Latest transport entries</div></div><span class="text-xs font-semibold text-slate-500">Today: {{ \Carbon\Carbon::parse($today)->format('d-m-Y') }}</span></div>
        <div class="overflow-x-auto"><table class="app-responsive-table w-full border-collapse text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="border-b px-4 py-3 text-left">Sr</th><th class="border-b px-4 py-3 text-left">Transport</th><th class="border-b px-4 py-3 text-left">LR Date</th><th class="border-b px-4 py-3 text-left">LR No</th><th class="border-b px-4 py-3 text-left">Customer</th><th class="border-b px-4 py-3 text-left">Supplier</th><th class="border-b px-4 py-3 text-right">Customer Freight</th><th class="border-b px-4 py-3 text-right">Balance</th></tr></thead><tbody>
        @forelse($recent as $row)
            @php $paid=(float)($row->customer_paid_total??0); $bal=(float)$row->customer_freight-$paid; @endphp
            <tr class="hover:bg-emerald-50/40"><td class="border-b px-4 py-3">{{ $row->sr_no }}</td><td class="border-b px-4 py-3 font-bold">{{ $row->transportCompany?->name }}</td><td class="border-b px-4 py-3">{{ optional($row->lr_date)->format('d-m-Y') }}</td><td class="border-b px-4 py-3">{{ $row->lr_no ?: '-' }}</td><td class="border-b px-4 py-3">{{ $row->customer?->name }}</td><td class="border-b px-4 py-3">{{ $row->supplier?->name ?: '-' }}</td><td class="border-b px-4 py-3 text-right">₹{{ number_format((float)$row->customer_freight,2) }}</td><td class="border-b px-4 py-3 text-right font-bold">₹{{ number_format($bal,2) }}</td></tr>
        @empty<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">No vouchers yet.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
