<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SoNumberSeries;
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
            ->with(['customer:id,name,code,gst_no,business_type'])
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
        $selectedCustomer = $customerId ? Customer::query()->find($customerId) : null;

        return view('sale.orders.index', compact('items', 'search', 'customerId', 'selectedCustomer', 'status'));
    }

    /**
     * Preview only. This does not reserve or increment a number.
     * The final number is assigned transactionally when Store succeeds.
     */
    public function numberPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $customer = Customer::query()->findOrFail((int) $validated['customer_id']);

        if ($this->isGovernmentCustomer($customer)) {
            return response()->json([
                'mode' => 'manual',
                'series_type' => null,
                'number' => null,
                'message' => 'Government customer: enter the customer-provided SO number manually.',
            ]);
        }

        $seriesType = $this->seriesTypeForCustomer($customer);
        $series = SoNumberSeries::query()
            ->where('series_type', $seriesType)
            ->where('is_active', true)
            ->first();

        if (!$series) {
            return response()->json([
                'mode' => 'unavailable',
                'series_type' => $seriesType,
                'number' => null,
                'message' => ($seriesType === 'gst' ? 'GST Registered' : 'Non-GST').' SO Number Series is not configured or is inactive.',
            ], 422);
        }

        [$candidate] = $this->nextAvailableSeriesNumber($series);

        return response()->json([
            'mode' => 'auto',
            'series_type' => $seriesType,
            'series_label' => $seriesType === 'gst' ? 'GST Registered' : 'Non-GST',
            'number' => $candidate,
            'message' => 'Preview only. The final number is reserved only when the SO is saved successfully.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $customer = Customer::query()->findOrFail($request->integer('customer_id'));
        $manualNumber = $this->isGovernmentCustomer($customer);
        $data = $this->validated($request, null, $manualNumber);
        $data['created_by'] = $request->user()?->id;

        try {
            DB::transaction(function () use ($customer, $manualNumber, &$data) {
                if ($manualNumber) {
                    $data['so_number'] = $this->normaliseSoNumber($data['so_number'] ?? '');
                    SalesOrder::query()->create($this->calculatedPayload($data));
                    return;
                }

                $seriesType = $this->seriesTypeForCustomer($customer);
                $series = SoNumberSeries::query()
                    ->where('series_type', $seriesType)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$series) {
                    throw ValidationException::withMessages([
                        'so_number' => ($seriesType === 'gst' ? 'GST Registered' : 'Non-GST').' SO Number Series is not configured or is inactive.',
                    ]);
                }

                [$candidate, $usedNumber] = $this->nextAvailableSeriesNumber($series);
                $data['so_number'] = $candidate;

                SalesOrder::query()->create($this->calculatedPayload($data));

                // Number is consumed only after the SO insert succeeds. If anything fails,
                // the DB transaction rolls back both the SO and this increment.
                $series->next_number = $usedNumber + 1;
                $series->save();
            });
        } catch (QueryException $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'duplicate') || str_contains($message, 'unique')) {
                throw ValidationException::withMessages([
                    'so_number' => 'This SO Number already exists. Please try again.',
                ]);
            }
            throw $e;
        }

        return back()->with('success', 'Sales Order created successfully.');
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        // Existing SO number is intentionally immutable on edit.
        $data = $this->validated($request, $salesOrder->id, false);
        $data['so_number'] = $salesOrder->so_number;
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

    private function validated(Request $request, ?int $id = null, bool $manualSoNumber = true): array
    {
        $requestedMode = strtolower(trim($request->string('tax_mode')->toString()));
        if (! in_array($requestedMode, ['rcm', 'hiring', 'gst', 'na'], true)) {
            $customer = $request->integer('customer_id') ? Customer::query()->find($request->integer('customer_id')) : null;
            $requestedMode = blank(trim((string) ($customer?->gst_no ?? ''))) ? 'rcm' : 'hiring';
        }

        $request->merge([
            'so_number' => $this->normaliseSoNumber($request->string('so_number')->toString()),
            'tax_mode' => $requestedMode,
        ]);

        $soNumberRules = $manualSoNumber
            ? ['required', 'string', 'max:150', Rule::unique('sales_orders', 'so_number')->ignore($id)]
            : ['nullable', 'string', 'max:150'];

        $data = $request->validate([
            'so_number' => $soNumberRules,
            'so_date' => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'from_location' => ['required', 'string', 'max:180'],
            'to_location' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:4000'],
            'trips_quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'per_trip_cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'tax_mode' => ['required', Rule::in(['rcm', 'hiring', 'gst', 'na'])],
            'gst_rate' => ['required', 'numeric', Rule::in([0, 5, 12, 18])],
            'other_charges' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function calculatedPayload(array $data): array
    {
        $taxMode = in_array($data['tax_mode'], ['rcm', 'hiring', 'gst', 'na'], true) ? $data['tax_mode'] : 'rcm';
        if ($taxMode === 'hiring') {
            $rate = 18.0;
        } elseif ($taxMode === 'gst') {
            $requestedRate = (float) ($data['gst_rate'] ?? 0);
            $rate = in_array((int) round($requestedRate), [5, 12, 18], true) ? (float) ((int) round($requestedRate)) : 5.0;
        } else {
            $rate = 0.0;
        }

        $trips = (int) $data['trips_quantity'];
        $perTrip = (float) $data['per_trip_cost'];
        $value = round($trips * $perTrip, 2);
        $gstAmount = round($value * $rate / 100, 2);
        $other = round((float) ($data['other_charges'] ?? 0), 2);

        return [
            'so_number' => $this->normaliseSoNumber($data['so_number']),
            'so_date' => $data['so_date'],
            'customer_id' => $data['customer_id'],
            'from_location' => trim($data['from_location']),
            'to_location' => trim($data['to_location']),
            'description' => trim($data['description']),
            'trips_quantity' => $trips,
            'per_trip_cost' => $perTrip,
            'value' => $value,
            'gst_rate_id' => null,
            'tax_mode' => $taxMode,
            'gst_rate' => $rate,
            'gst_amount' => $gstAmount,
            'other_charges' => $other,
            'total_amount' => round($value + $gstAmount + $other, 2),
            'is_active' => (bool) ($data['is_active'] ?? false),
            ...array_filter(['created_by' => $data['created_by'] ?? null], fn ($v) => $v !== null),
        ];
    }

    private function isGovernmentCustomer(Customer $customer): bool
    {
        return strtolower(trim((string) $customer->business_type)) === 'government';
    }

    private function seriesTypeForCustomer(Customer $customer): string
    {
        return filled(trim((string) $customer->gst_no)) ? 'gst' : 'non_gst';
    }

    /** @return array{0:string,1:int} */
    private function nextAvailableSeriesNumber(SoNumberSeries $series): array
    {
        $number = max(1, (int) $series->next_number);

        // If Next Number was manually moved backwards in the master, safely skip
        // already-used SO numbers instead of producing a duplicate.
        for ($attempt = 0; $attempt < 100000; $attempt++, $number++) {
            $candidate = $this->normaliseSoNumber($series->formatNumber($number));
            if (!SalesOrder::query()->where('so_number', $candidate)->exists()) {
                return [$candidate, $number];
            }
        }

        throw ValidationException::withMessages([
            'so_number' => 'Unable to find an unused SO Number in this series. Please review the Series Master.',
        ]);
    }

    private function normaliseSoNumber(string $value): string
    {
        return strtoupper(trim($value));
    }
}
