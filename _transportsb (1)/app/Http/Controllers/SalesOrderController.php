<?php

namespace App\Http\Controllers;

use App\Models\GstRate;
use App\Models\SalesOrder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $customerId = $request->integer('customer_id') ?: null;
        $status = $request->string('status')->toString();
        if (!in_array($status, ['', 'open', 'completed', 'inactive'], true)) {
            $status = '';
        }

        $query = SalesOrder::query()
            ->with(['customer:id,name,code', 'gstRate:id,name,rate'])
            ->withCount('vouchers');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('from_location', 'like', '%'.$search.'%')
                    ->orWhere('to_location', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
            });
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($status === 'open') {
            $query->where('is_active', true)->whereRaw('(select count(*) from vouchers where vouchers.sales_order_id = sales_orders.id) < trips_quantity');
        } elseif ($status === 'completed') {
            $query->whereRaw('(select count(*) from vouchers where vouchers.sales_order_id = sales_orders.id) >= trips_quantity');
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $items = $query->orderByDesc('so_date')->orderByDesc('id')->paginate(50)->withQueryString();
        $selectedCustomer = $customerId ? \App\Models\Customer::query()->find($customerId) : null;
        $defaultGst = GstRate::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('rate')->first();
        $defaultGstPayload = $defaultGst ? [
            'id' => $defaultGst->id,
            'rate' => (float) $defaultGst->rate,
            'label' => rtrim(rtrim(number_format((float) $defaultGst->rate, 2, '.', ''), '0'), '.').'%',
        ] : ['id' => null, 'rate' => 0, 'label' => '0%'];

        return view('sale.orders.index', compact('items', 'search', 'customerId', 'selectedCustomer', 'status', 'defaultGst', 'defaultGstPayload'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;
        SalesOrder::query()->create($this->calculatedPayload($data));

        return back()->with('success', 'Sales Order created successfully.');
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $data = $this->validated($request, $salesOrder->id);
        $usedTrips = $salesOrder->vouchers()->count();

        if ((int) $data['trips_quantity'] < $usedTrips) {
            throw ValidationException::withMessages([
                'trips_quantity' => "Trips Quantity cannot be less than {$usedTrips}; those voucher trips already exist.",
            ]);
        }

        $salesOrder->update($this->calculatedPayload($data));

        return back()->with('success', 'Sales Order updated successfully.');
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->vouchers()->exists()) {
            return back()->with('error', 'This SO already has voucher trips. Make it inactive instead of deleting it.');
        }

        try {
            $salesOrder->delete();
        } catch (QueryException) {
            return back()->with('error', 'This SO is already in use and cannot be deleted.');
        }

        return back()->with('success', 'Sales Order deleted successfully.');
    }

    public function options(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->toString());
        $includeId = $request->integer('include_id') ?: null;

        $query = SalesOrder::query()
            ->with('customer:id,name,code')
            ->withCount('vouchers')
            ->where(function ($q) use ($includeId) {
                $q->where(function ($inner) {
                    $inner->where('is_active', true)
                        ->whereRaw('(select count(*) from vouchers where vouchers.sales_order_id = sales_orders.id) < trips_quantity');
                });
                if ($includeId) {
                    $q->orWhere('id', $includeId);
                }
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', '%'.$search.'%')
                    ->orWhere('from_location', 'like', '%'.$search.'%')
                    ->orWhere('to_location', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
            });
        }

        $items = $query->orderByDesc('so_date')->orderByDesc('id')->limit(100)->get()->map(function (SalesOrder $order) {
            $used = (int) $order->vouchers_count;
            $remaining = max(0, (int) $order->trips_quantity - $used);
            $customer = $order->customer?->name ?: '-';

            return [
                'id' => $order->id,
                'name' => $order->so_number,
                'label' => $order->so_number.' · '.$customer.' · '.$order->from_location.' → '.$order->to_location.' · Remaining '.$remaining.'/'.$order->trips_quantity,
                'so_number' => $order->so_number,
                'customer_id' => $order->customer_id,
                'customer_name' => $customer,
                'from_location' => $order->from_location,
                'to_location' => $order->to_location,
                'trips_quantity' => (int) $order->trips_quantity,
                'used_trips' => $used,
                'remaining_trips' => $remaining,
                'per_trip_cost' => (float) $order->per_trip_cost,
            ];
        });

        return response()->json(['items' => $items]);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'so_number' => ['required', 'string', 'max:150', Rule::unique('sales_orders', 'so_number')->ignore($id)],
            'so_date' => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'from_location' => ['required', 'string', 'max:180'],
            'to_location' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:4000'],
            'trips_quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'per_trip_cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'gst_rate_id' => ['nullable', 'integer', 'exists:gst_rates,id'],
            'other_charges' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    private function calculatedPayload(array $data): array
    {
        $gst = !empty($data['gst_rate_id']) ? GstRate::query()->find($data['gst_rate_id']) : null;
        $rate = (float) ($gst?->rate ?? 0);
        $trips = (int) $data['trips_quantity'];
        $perTrip = (float) $data['per_trip_cost'];
        $value = round($trips * $perTrip, 2);
        $gstAmount = round($value * $rate / 100, 2);
        $other = round((float) ($data['other_charges'] ?? 0), 2);

        return [
            'so_number' => strtoupper(trim($data['so_number'])),
            'so_date' => $data['so_date'],
            'customer_id' => $data['customer_id'],
            'from_location' => trim($data['from_location']),
            'to_location' => trim($data['to_location']),
            'description' => trim($data['description']),
            'trips_quantity' => $trips,
            'per_trip_cost' => $perTrip,
            'value' => $value,
            'gst_rate_id' => $gst?->id,
            'gst_rate' => $rate,
            'gst_amount' => $gstAmount,
            'other_charges' => $other,
            'total_amount' => round($value + $gstAmount + $other, 2),
            'is_active' => (bool) ($data['is_active'] ?? false),
            ...array_filter(['created_by' => $data['created_by'] ?? null], fn ($v) => $v !== null),
        ];
    }
}
