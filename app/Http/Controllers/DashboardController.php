<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Voucher;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        $todayQuery = Voucher::query()->whereDate('lr_date', $today);

        $todayCount = (clone $todayQuery)->count();
        $todayCustomerFreight = (float) (clone $todayQuery)->sum('customer_freight');
        $todaySupplierFreight = (float) (clone $todayQuery)->sum('supplier_freight');
        $todayProfit = $todayCustomerFreight - $todaySupplierFreight;

        $customerOutstanding = (float) Customer::sum('opening_balance')
            + (float) Voucher::sum('customer_freight')
            - (float) CustomerPayment::sum('amount');

        $supplierOutstanding = (float) Supplier::sum('opening_balance')
            + (float) Voucher::sum('supplier_freight')
            - (float) Voucher::sum('supplier_advance')
            - (float) SupplierPayment::sum('amount');

        $recent = Voucher::query()
            ->with(['transportCompany', 'customer', 'supplier'])
            ->withSum('customerPayments as customer_paid_total', 'amount')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'today',
            'todayCount',
            'todayCustomerFreight',
            'todaySupplierFreight',
            'todayProfit',
            'customerOutstanding',
            'supplierOutstanding',
            'recent',
        ));
    }
}
