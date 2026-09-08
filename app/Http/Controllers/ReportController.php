<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TransportCompany;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function voucherRegister(Request $request): View
    {
        $filters = $this->commonFilters($request);
        $query = Voucher::query()
            ->with(['transportCompany', 'customer', 'supplier', 'vehicleType'])
            ->withSum('supplierPayments as supplier_payment_total', 'amount')
            ->withSum('customerPayments as customer_paid_total', 'amount');

        $this->applyVoucherFilters($query, $filters);

        $vouchers = $query->orderBy('lr_date')->orderBy('sr_no')->paginate(100)->withQueryString();
        $companies = TransportCompany::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('reports.voucher-register', compact('vouchers', 'filters', 'companies', 'customers', 'suppliers'));
    }

    public function customerLedger(Request $request): View
    {
        $customerId = $request->integer('customer_id') ?: null;
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        $customers = Customer::orderBy('name')->get();
        $customer = $customerId ? Customer::find($customerId) : null;
        $entries = collect();
        $opening = 0.0;
        $closing = 0.0;

        if ($customer) {
            [$entries, $opening, $closing] = $this->customerLedgerData($customer, $from, $to);
        }

        return view('reports.customer-ledger', compact('customers', 'customer', 'entries', 'from', 'to', 'opening', 'closing'));
    }

    public function supplierLedger(Request $request): View
    {
        $supplierId = $request->integer('supplier_id') ?: null;
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        $suppliers = Supplier::orderBy('name')->get();
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        $entries = collect();
        $opening = 0.0;
        $closing = 0.0;

        if ($supplier) {
            [$entries, $opening, $closing] = $this->supplierLedgerData($supplier, $from, $to);
        }

        return view('reports.supplier-ledger', compact('suppliers', 'supplier', 'entries', 'from', 'to', 'opening', 'closing'));
    }

    public function outstanding(): View
    {
        $customerRows = Customer::query()->orderBy('name')->get()->map(function (Customer $customer) {
            $charges = (float) $customer->vouchers()->sum('customer_freight');
            $paid = (float) CustomerPayment::query()->whereHas('voucher', fn ($q) => $q->where('customer_id', $customer->id))->sum('amount');
            $balance = (float) $customer->opening_balance + $charges - $paid;

            return compact('customer', 'charges', 'paid', 'balance');
        })->filter(fn ($row) => abs($row['balance']) > 0.004)->values();

        $supplierRows = Supplier::query()->orderBy('name')->get()->map(function (Supplier $supplier) {
            $freight = (float) $supplier->vouchers()->sum('supplier_freight');
            $advance = (float) $supplier->vouchers()->sum('supplier_advance');
            $paid = (float) SupplierPayment::query()->whereHas('voucher', fn ($q) => $q->where('supplier_id', $supplier->id))->sum('amount');
            $balance = (float) $supplier->opening_balance + $freight - $advance - $paid;

            return compact('supplier', 'freight', 'advance', 'paid', 'balance');
        })->filter(fn ($row) => abs($row['balance']) > 0.004)->values();

        return view('reports.outstanding', compact('customerRows', 'supplierRows'));
    }

    public function profit(Request $request): View
    {
        $filters = $this->commonFilters($request);
        $query = Voucher::query()->with(['transportCompany', 'customer', 'supplier']);
        $this->applyVoucherFilters($query, $filters);
        $rows = $query->orderBy('lr_date')->orderBy('sr_no')->get();

        $totals = [
            'customer_freight' => (float) $rows->sum('customer_freight'),
            'supplier_freight' => (float) $rows->sum('supplier_freight'),
            'hamali_loading' => (float) $rows->sum('hamali_loading'),
            'hamali_unloading' => (float) $rows->sum('hamali_unloading'),
        ];
        $totals['profit'] = $totals['customer_freight'] - $totals['supplier_freight'];

        $companies = TransportCompany::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('reports.profit', compact('rows', 'totals', 'filters', 'companies', 'customers', 'suppliers'));
    }

    public function customerBill(Voucher $voucher): View
    {
        $voucher->load(['transportCompany', 'customer', 'supplier', 'vehicleType'])
            ->loadSum('customerPayments as customer_paid_total', 'amount');

        return view('reports.customer-bill', compact('voucher'));
    }

    public function customerStatement(Request $request, Customer $customer): View
    {
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        [$entries, $opening, $closing] = $this->customerLedgerData($customer, $from, $to);

        return view('reports.customer-statement', compact('customer', 'entries', 'from', 'to', 'opening', 'closing'));
    }

    public function supplierStatement(Request $request, Supplier $supplier): View
    {
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        [$entries, $opening, $closing] = $this->supplierLedgerData($supplier, $from, $to);

        return view('reports.supplier-statement', compact('supplier', 'entries', 'from', 'to', 'opening', 'closing'));
    }

    private function customerLedgerData(Customer $customer, string $from, string $to): array
    {
        $opening = (float) $customer->opening_balance;
        $opening += (float) Voucher::where('customer_id', $customer->id)->whereDate('lr_date', '<', $from)->sum('customer_freight');
        $opening -= (float) CustomerPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('customer_id', $customer->id))
            ->whereDate('payment_date', '<', $from)
            ->sum('amount');

        $entries = collect();

        Voucher::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('lr_date', [$from, $to])
            ->with('transportCompany')
            ->get()
            ->each(function (Voucher $voucher) use ($entries) {
                $entries->push([
                    'date' => $voucher->lr_date->format('Y-m-d'),
                    'sort' => '1-'.$voucher->id,
                    'particulars' => 'Voucher #'.$voucher->sr_no.' · LR '.($voucher->lr_no ?: '-').' · '.($voucher->from_place ?: '-').' → '.($voucher->to_place ?: '-'),
                    'reference' => $voucher->bill_no ?: ($voucher->transportCompany?->name.'-'.$voucher->sr_no),
                    'charge' => (float) $voucher->customer_freight,
                    'paid' => 0.0,
                ]);
            });

        CustomerPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('customer_id', $customer->id))
            ->whereBetween('payment_date', [$from, $to])
            ->with('voucher')
            ->get()
            ->each(function (CustomerPayment $payment) use ($entries) {
                $entries->push([
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'sort' => '2-'.$payment->id,
                    'particulars' => 'Payment received against Voucher #'.$payment->voucher->sr_no,
                    'reference' => $payment->reference ?: $payment->payment_mode,
                    'charge' => 0.0,
                    'paid' => (float) $payment->amount,
                ]);
            });

        $balance = $opening;
        $entries = $entries->sortBy(fn ($row) => $row['date'].'-'.$row['sort'])->values()->map(function ($row) use (&$balance) {
            $balance += $row['charge'] - $row['paid'];
            $row['balance'] = $balance;
            return $row;
        });

        return [$entries, $opening, $balance];
    }

    private function supplierLedgerData(Supplier $supplier, string $from, string $to): array
    {
        $opening = (float) $supplier->opening_balance;
        $opening += (float) Voucher::where('supplier_id', $supplier->id)->whereDate('lr_date', '<', $from)->sum('supplier_freight');
        $opening -= (float) Voucher::where('supplier_id', $supplier->id)->whereDate('lr_date', '<', $from)->sum('supplier_advance');
        $opening -= (float) SupplierPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->whereDate('payment_date', '<', $from)
            ->sum('amount');

        $entries = collect();

        Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->whereBetween('lr_date', [$from, $to])
            ->with('transportCompany')
            ->get()
            ->each(function (Voucher $voucher) use ($entries) {
                $entries->push([
                    'date' => $voucher->lr_date->format('Y-m-d'),
                    'sort' => '1-'.$voucher->id,
                    'particulars' => 'Freight Voucher #'.$voucher->sr_no.' · LR '.($voucher->lr_no ?: '-').' · '.($voucher->from_place ?: '-').' → '.($voucher->to_place ?: '-'),
                    'reference' => $voucher->transportCompany?->name,
                    'charge' => (float) $voucher->supplier_freight,
                    'paid' => 0.0,
                ]);

                if ((float) $voucher->supplier_advance > 0) {
                    $entries->push([
                        'date' => $voucher->lr_date->format('Y-m-d'),
                        'sort' => '2-'.$voucher->id,
                        'particulars' => 'Advance paid against Voucher #'.$voucher->sr_no,
                        'reference' => 'Advance',
                        'charge' => 0.0,
                        'paid' => (float) $voucher->supplier_advance,
                    ]);
                }
            });

        SupplierPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->whereBetween('payment_date', [$from, $to])
            ->with('voucher')
            ->get()
            ->each(function (SupplierPayment $payment) use ($entries) {
                $entries->push([
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'sort' => '3-'.$payment->id,
                    'particulars' => 'Payment made against Voucher #'.$payment->voucher->sr_no,
                    'reference' => $payment->reference ?: $payment->payment_mode,
                    'charge' => 0.0,
                    'paid' => (float) $payment->amount,
                ]);
            });

        $balance = $opening;
        $entries = $entries->sortBy(fn ($row) => $row['date'].'-'.$row['sort'])->values()->map(function ($row) use (&$balance) {
            $balance += $row['charge'] - $row['paid'];
            $row['balance'] = $balance;
            return $row;
        });

        return [$entries, $opening, $balance];
    }

    private function commonFilters(Request $request): array
    {
        return [
            'from' => $request->string('from')->toString() ?: now()->startOfMonth()->toDateString(),
            'to' => $request->string('to')->toString() ?: now()->toDateString(),
            'transport_company_id' => $request->integer('transport_company_id') ?: null,
            'customer_id' => $request->integer('customer_id') ?: null,
            'supplier_id' => $request->integer('supplier_id') ?: null,
        ];
    }

    private function applyVoucherFilters($query, array $filters): void
    {
        $query->whereBetween('lr_date', [$filters['from'], $filters['to']]);

        if ($filters['transport_company_id']) {
            $query->where('transport_company_id', $filters['transport_company_id']);
        }
        if ($filters['customer_id']) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if ($filters['supplier_id']) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
    }
}
