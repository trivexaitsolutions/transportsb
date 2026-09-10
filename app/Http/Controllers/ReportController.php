<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\PrintSetting;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TransportCompany;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function voucherRegister(Request $request): View
    {
        $filters = $this->commonFilters($request);
        $query = Voucher::query()
            ->with(['transportCompany', 'customer', 'supplier', 'vehicleType', 'gstRate'])
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
        $showPayments = $request->boolean('show_payments');
        $customers = Customer::orderBy('name')->get();
        $customer = $customerId ? Customer::find($customerId) : null;
        $entries = collect();
        $opening = 0.0;
        $closing = 0.0;

        if ($customer) {
            [$entries, $opening, $closing] = $this->customerLedgerData($customer, $from, $to, $showPayments);
        }

        return view('reports.customer-ledger', compact(
            'customers', 'customer', 'entries', 'from', 'to', 'opening', 'closing', 'showPayments'
        ));
    }

    public function supplierLedger(Request $request): View
    {
        $supplierId = $request->integer('supplier_id') ?: null;
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        $showPayments = $request->boolean('show_payments');
        $suppliers = Supplier::orderBy('name')->get();
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        $entries = collect();
        $opening = 0.0;
        $closing = 0.0;

        if ($supplier) {
            [$entries, $opening, $closing] = $this->supplierLedgerData($supplier, $from, $to, $showPayments);
        }

        return view('reports.supplier-ledger', compact(
            'suppliers', 'supplier', 'entries', 'from', 'to', 'opening', 'closing', 'showPayments'
        ));
    }

    public function customerLedgerPrint(Request $request, Customer $customer): View
    {
        $validated = $request->validate([
            'voucher_ids' => ['required', 'array', 'min:1'],
            'voucher_ids.*' => ['integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $ids = collect($validated['voucher_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 422, 'Please select at least one bill to print.');

        $vouchers = Voucher::query()
            ->where('customer_id', $customer->id)
            ->whereIn('id', $ids)
            ->with(['transportCompany', 'customer', 'vehicleType', 'gstRate'])
            ->orderBy('lr_date')
            ->orderBy('sr_no')
            ->get();

        abort_if($vouchers->isEmpty(), 422, 'No matching customer bills were found.');

        $from = $validated['from'] ?? $vouchers->min(fn (Voucher $voucher) => $voucher->lr_date?->toDateString());
        $to = $validated['to'] ?? $vouchers->max(fn (Voucher $voucher) => $voucher->lr_date?->toDateString());

        /*
         * BGT / LST are operational transport names under one parent company.
         * Selected bills must therefore print together on one parent-company invoice,
         * not as separate pages grouped by BGT/LST.
         * These defaults can later be moved to a Settings/Master screen when the
         * client's final legal/company details are supplied.
         */
        $printProfile = [
            'name' => 'XYZ TRANSPORT',
            'tagline' => 'Fleet Owners & Transport Contractor',
            'address' => 'Nagpur, Maharashtra',
            'phone' => '9876543210',
            'email' => 'accounts@xyztransport.com',
            'pan' => 'ABCDE1234F',
            'gst_no' => '27ABCDE1234F1Z5',
            'place_of_service' => 'Ambernath Thane',
            'bank_holder' => 'XYZ TRANSPORT',
            'bank_name' => 'Bank of Baroda',
            'bank_address' => 'Nagpur Branch, Nagpur',
            'account_no' => '000000000000',
            'ifsc' => 'BARB0000000',
        ];

        $amount = (float) $vouchers->sum(fn (Voucher $voucher) => $this->customerAmount($voucher));
        $taxable = (float) $vouchers->sum(fn (Voucher $voucher) => (float) $voucher->customer_freight);
        $gst = (float) $vouchers->sum(fn (Voucher $voucher) => (float) $voucher->gst);
        $total = $amount + $gst;

        $gstSummary = $vouchers
            ->groupBy(fn (Voucher $voucher) => (string) ((float) ($voucher->gstRate?->rate ?? 0)))
            ->map(function ($rateRows, $rate) {
                $taxableAmount = (float) $rateRows->sum(fn (Voucher $voucher) => (float) $voucher->customer_freight);
                $gstAmount = (float) $rateRows->sum(fn (Voucher $voucher) => (float) $voucher->gst);

                return [
                    'rate' => (float) $rate,
                    'taxable' => $taxableAmount,
                    'gst' => $gstAmount,
                    'total' => $taxableAmount + $gstAmount,
                ];
            })
            ->sortBy('rate')
            ->values();

        $references = $vouchers->pluck('so_ref_no')->filter()->unique()->values();
        $contractReference = $references->count() === 1
            ? $references->first()
            : ($references->count() > 1 ? 'Multiple References' : '-');

        // Keep the existing view structure, but deliberately supply a single group
        // containing every selected row so mixed BGT/LST selections print together.
        $groups = collect([[
            'rows' => $vouchers,
            'amount' => $amount,
            'taxable' => $taxable,
            'gst' => $gst,
            'total' => $total,
            'gstSummary' => $gstSummary,
            'placeOfService' => $printProfile['place_of_service'],
            'contractReference' => $contractReference,
        ]]);

        $gstNo = strtoupper(trim((string) $customer->gst_no));
        $customerPan = strlen($gstNo) >= 12 ? substr($gstNo, 2, 10) : '-';
        $stateCode = strlen($gstNo) >= 2 ? substr($gstNo, 0, 2) : '27';
        if ($stateCode === '27') {
            $stateCode = '27 (Maharashtra)';
        }

        $letterheadTopMarginMm = (float) PrintSetting::current()->letterhead_top_margin_mm;

        return view('reports.customer-ledger-print', compact(
            'customer', 'groups', 'from', 'to', 'customerPan', 'stateCode', 'printProfile', 'letterheadTopMarginMm'
        ));
    }

    public function outstanding(): View
    {
        $customerRows = Customer::query()->orderBy('name')->get()->map(function (Customer $customer) {
            $charges = (float) $customer->vouchers()->sum('customer_freight')
                + (float) $customer->vouchers()->sum('hamali_loading')
                + (float) $customer->vouchers()->sum('hamali_unloading')
                + (float) $customer->vouchers()->sum('other_charges')
                + (float) $customer->vouchers()->sum('gst');
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
        $voucher->load(['transportCompany', 'customer', 'supplier', 'vehicleType', 'gstRate'])
            ->loadSum('customerPayments as customer_paid_total', 'amount');

        $letterheadTopMarginMm = (float) PrintSetting::current()->letterhead_top_margin_mm;

        return view('reports.customer-bill', compact('voucher', 'letterheadTopMarginMm'));
    }

    public function customerStatement(Request $request, Customer $customer): View
    {
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        [$entries, $opening, $closing] = $this->customerLedgerData($customer, $from, $to, true);

        return view('reports.customer-statement', compact('customer', 'entries', 'from', 'to', 'opening', 'closing'));
    }

    public function supplierStatement(Request $request, Supplier $supplier): View
    {
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();
        [$entries, $opening, $closing] = $this->supplierLedgerData($supplier, $from, $to, true);

        return view('reports.supplier-statement', compact('supplier', 'entries', 'from', 'to', 'opening', 'closing'));
    }

    private function customerLedgerData(Customer $customer, string $from, string $to, bool $showPayments = true): array
    {
        $opening = (float) $customer->opening_balance;
        $openingQuery = Voucher::where('customer_id', $customer->id)->whereDate('lr_date', '<', $from);
        $opening += (float) (clone $openingQuery)->sum('customer_freight');
        $opening += (float) (clone $openingQuery)->sum('hamali_loading');
        $opening += (float) (clone $openingQuery)->sum('hamali_unloading');
        $opening += (float) (clone $openingQuery)->sum('other_charges');
        $opening += (float) (clone $openingQuery)->sum('gst');
        $opening -= (float) CustomerPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('customer_id', $customer->id))
            ->whereDate('payment_date', '<', $from)
            ->sum('amount');

        $periodPayments = (float) CustomerPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('customer_id', $customer->id))
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        $vouchers = Voucher::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('lr_date', [$from, $to])
            ->with(['transportCompany', 'gstRate'])
            ->with(['customerPayments' => fn ($query) => $query->whereDate('payment_date', '<=', $to)->orderBy('payment_date')])
            ->orderBy('lr_date')
            ->orderBy('sr_no')
            ->get();

        $periodCharges = (float) $vouchers->sum(fn (Voucher $voucher) => $this->customerAmount($voucher) + (float) $voucher->gst);
        $closing = $opening + $periodCharges - $periodPayments;

        if (!$showPayments) {
            $entries = $vouchers->map(function (Voucher $voucher) {
                $amount = $this->customerAmount($voucher);
                $taxable = (float) $voucher->customer_freight;
                $gst = (float) $voucher->gst;
                $total = $amount + $gst;
                $paid = (float) $voucher->customerPayments->sum('amount');

                return [
                    'type' => 'voucher',
                    'voucher_id' => $voucher->id,
                    'date' => $voucher->lr_date->format('Y-m-d'),
                    'bill_no' => $voucher->bill_no ?: '-',
                    'lr_no' => $voucher->lr_no ?: '-',
                    'lr_date' => $voucher->lr_date->format('Y-m-d'),
                    'lorry_no' => $voucher->lorry_no ?: '-',
                    'from_place' => $voucher->from_place ?: '-',
                    'to_place' => $voucher->to_place ?: '-',
                    'amount' => $amount,
                    'taxable' => $taxable,
                    'gst_rate' => (float) ($voucher->gstRate?->rate ?? 0),
                    'gst' => $gst,
                    'total' => $total,
                    'voucher_paid' => $paid,
                    'voucher_outstanding' => $total - $paid,
                ];
            })->values();

            return [$entries, $opening, $closing];
        }

        $entries = collect();

        $vouchers->each(function (Voucher $voucher) use ($entries) {
            $entries->push([
                'type' => 'voucher',
                'voucher_id' => $voucher->id,
                'date' => $voucher->lr_date->format('Y-m-d'),
                'sort' => '1-'.str_pad((string) $voucher->id, 12, '0', STR_PAD_LEFT),
                'particulars' => 'Voucher #'.$voucher->sr_no.' · LR '.($voucher->lr_no ?: '-').' · '.($voucher->from_place ?: '-').' → '.($voucher->to_place ?: '-'),
                'reference' => $voucher->bill_no ?: ($voucher->transportCompany?->name.'-'.$voucher->sr_no),
                'charge' => $this->customerAmount($voucher) + (float) $voucher->gst,
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
                    'type' => 'payment',
                    'voucher_id' => $payment->voucher_id,
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'sort' => '2-'.str_pad((string) $payment->id, 12, '0', STR_PAD_LEFT),
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

    private function customerAmount(Voucher $voucher): float
    {
        return (float) $voucher->customer_freight
            + (float) $voucher->hamali_loading
            + (float) $voucher->hamali_unloading
            + (float) $voucher->other_charges;
    }

    private function supplierLedgerData(Supplier $supplier, string $from, string $to, bool $showPayments = true): array
    {
        $opening = (float) $supplier->opening_balance;
        $opening += (float) Voucher::where('supplier_id', $supplier->id)->whereDate('lr_date', '<', $from)->sum('supplier_freight');
        $opening -= (float) Voucher::where('supplier_id', $supplier->id)->whereDate('lr_date', '<', $from)->sum('supplier_advance');
        $opening -= (float) SupplierPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->whereDate('payment_date', '<', $from)
            ->sum('amount');

        $periodPayments = (float) SupplierPayment::query()
            ->whereHas('voucher', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        $vouchers = Voucher::query()
            ->where('supplier_id', $supplier->id)
            ->whereBetween('lr_date', [$from, $to])
            ->with('transportCompany')
            ->with(['supplierPayments' => fn ($query) => $query->whereDate('payment_date', '<=', $to)->orderBy('payment_date')])
            ->orderBy('lr_date')
            ->orderBy('sr_no')
            ->get();

        $periodFreight = (float) $vouchers->sum('supplier_freight');
        $periodAdvance = (float) $vouchers->sum('supplier_advance');
        $closing = $opening + $periodFreight - $periodAdvance - $periodPayments;

        if (!$showPayments) {
            $entries = $vouchers->map(function (Voucher $voucher) {
                $freight = (float) $voucher->supplier_freight;
                $advance = (float) $voucher->supplier_advance;
                $paid = (float) $voucher->supplierPayments->sum('amount');

                return [
                    'type' => 'voucher',
                    'voucher_id' => $voucher->id,
                    'date' => $voucher->lr_date->format('Y-m-d'),
                    'lr_no' => $voucher->lr_no ?: '-',
                    'lorry_no' => $voucher->lorry_no ?: '-',
                    'from_place' => $voucher->from_place ?: '-',
                    'to_place' => $voucher->to_place ?: '-',
                    'freight' => $freight,
                    'advance' => $advance,
                    'installment_paid' => $paid,
                    'voucher_outstanding' => $freight - $advance - $paid,
                    'reference' => $voucher->transportCompany?->name ?: '-',
                ];
            })->values();

            return [$entries, $opening, $closing];
        }

        $entries = collect();

        $vouchers->each(function (Voucher $voucher) use ($entries) {
            $entries->push([
                'type' => 'voucher',
                'voucher_id' => $voucher->id,
                'date' => $voucher->lr_date->format('Y-m-d'),
                'sort' => '1-'.str_pad((string) $voucher->id, 12, '0', STR_PAD_LEFT),
                'particulars' => 'Freight Voucher #'.$voucher->sr_no.' · LR '.($voucher->lr_no ?: '-').' · '.($voucher->from_place ?: '-').' → '.($voucher->to_place ?: '-'),
                'reference' => $voucher->transportCompany?->name,
                'charge' => (float) $voucher->supplier_freight,
                'paid' => 0.0,
            ]);

            if ((float) $voucher->supplier_advance > 0) {
                $entries->push([
                    'type' => 'payment',
                    'voucher_id' => $voucher->id,
                    'date' => $voucher->lr_date->format('Y-m-d'),
                    'sort' => '2-'.str_pad((string) $voucher->id, 12, '0', STR_PAD_LEFT),
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
                    'type' => 'payment',
                    'voucher_id' => $payment->voucher_id,
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'sort' => '3-'.str_pad((string) $payment->id, 12, '0', STR_PAD_LEFT),
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
