@extends('layouts.app')
@section('title','Voucher Register | XYZ Transport')
@push('styles')@include('reports._styles')@endpush
@section('content')
<div class="app-workspace">
<div class="report-head no-print">
    <div class="title"><h1 class="text-xl font-black">Voucher Register</h1><p class="text-sm text-slate-500">Complete voucher history with customer, supplier and outstanding values.</p></div>
    <form method="GET" class="report-filter">
        <label><span>From</span><input type="date" name="from" value="{{ $filters['from'] }}"></label>
        <label><span>To</span><input type="date" name="to" value="{{ $filters['to'] }}"></label>
        <label><span>Transport</span><select name="transport_company_id" data-slim-select data-placeholder="All"><option value="">All</option>@foreach($companies as $x)<option value="{{ $x->id }}" @selected($filters['transport_company_id']==$x->id)>{{ $x->name }}</option>@endforeach</select></label>
        <label><span>Customer</span><select name="customer_id" data-slim-select data-placeholder="All"><option value="">All</option>@foreach($customers as $x)<option value="{{ $x->id }}" @selected($filters['customer_id']==$x->id)>{{ $x->name }}</option>@endforeach</select></label>
        <label><span>Supplier</span><select name="supplier_id" data-slim-select data-placeholder="All"><option value="">All</option>@foreach($suppliers as $x)<option value="{{ $x->id }}" @selected($filters['supplier_id']==$x->id)>{{ $x->name }}</option>@endforeach</select></label>
        <button class="report-btn primary">Show</button><button type="button" onclick="window.print()" class="report-btn">Print</button>
    </form>
</div>
<div class="report-panel overflow-auto"><table class="report-table"><thead><tr><th>Sr</th><th>Transport</th><th>LR Date</th><th>LR No</th><th>Vehicle Type</th><th>Lorry</th><th>SO / Ref</th><th>From</th><th>To</th><th>Supplier</th><th>Supp. Freight</th><th>Supp. Advance</th><th>Supp. Paid</th><th>Supp. Balance</th><th>Customer</th><th>Cust. Freight</th><th>Paid</th><th>Balance</th><th>Hamali L</th><th>Hamali U</th><th>Profit</th><th>Bill No</th><th>GST</th><th>Remarks</th><th class="no-print">Bill</th></tr></thead><tbody>
@forelse($vouchers as $v)
@php $sp=(float)($v->supplier_payment_total??0);$cp=(float)($v->customer_paid_total??0);$sb=max(0,(float)$v->supplier_freight-(float)$v->supplier_advance-$sp);$cb=max(0,(float)$v->customer_freight-$cp;$profit=(float)$v->customer_freight-(float)$v->supplier_freight; @endphp
<tr><td>{{ $v->sr_no }}</td><td class="font-bold">{{ $v->transportCompany?->name }}</td><td>{{ optional($v->lr_date)->format('d-m-Y') }}</td><td>{{ $v->lr_no?:'-' }}</td><td>{{ $v->vehicleType?->name?:'-' }}</td><td>{{ $v->lorry_no?:'-' }}</td><td>{{ $v->so_ref_no?:'-' }}</td><td>{{ $v->from_place?:'-' }}</td><td>{{ $v->to_place?:'-' }}</td><td>{{ $v->supplier?->name?:'-' }}</td><td class="num">₹{{ number_format((float)$v->supplier_freight,2) }}</td><td class="num">₹{{ number_format((float)$v->supplier_advance,2) }}</td><td class="num">₹{{ number_format($sp,2) }}</td><td class="num font-bold">₹{{ number_format($sb,2) }}</td><td>{{ $v->customer?->name }}</td><td class="num">₹{{ number_format((float)$v->customer_freight,2) }}</td><td class="num">₹{{ number_format($cp,2) }}</td><td class="num font-bold">₹{{ number_format($cb,2) }}</td><td class="num">₹{{ number_format((float)$v->hamali_loading,2) }}</td><td class="num">₹{{ number_format((float)$v->hamali_unloading,2) }}</td><td class="num font-black {{ $profit>=0?'positive':'negative' }}">₹{{ number_format($profit,2) }}</td><td>{{ $v->bill_no?:'-' }}</td><td class="num">₹{{ number_format((float)$v->gst,2) }}</td><td>{{ $v->remarks?:'-' }}</td><td class="no-print"><a target="_blank" href="{{ route('reports.customer-bill',$v) }}" class="font-bold text-blue-700 underline">Print</a></td></tr>
@empty<tr><td colspan="25" class="py-10 text-center text-slate-400">No vouchers found for selected filters.</td></tr>@endforelse
</tbody></table></div><div class="mt-3 no-print">{{ $vouchers->links() }}</div>
</div>
@endsection
