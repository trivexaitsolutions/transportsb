<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\GstRate;
use App\Models\PrintSetting;
use App\Models\Supplier;
use App\Models\TransportCompany;
use App\Models\VehicleType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterController extends Controller
{
    public function settings(): View
    {
        $settings = PrintSetting::current();

        return view('masters.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'letterhead_top_margin_mm' => ['required', 'numeric', 'min:0', 'max:120'],
        ]);

        $settings = PrintSetting::current();
        $settings->update([
            'letterhead_top_margin_mm' => (float) $data['letterhead_top_margin_mm'],
        ]);

        return back()->with('success', 'Print settings saved successfully.');
    }

    public function index(Request $request, string $type): View
    {
        [$model, $config] = $this->definition($type);
        $search = trim($request->string('search')->toString());

        $query = $model::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search, $type) {
                $q->where('name', 'like', '%'.$search.'%');

                if (in_array($type, ['customers', 'suppliers'], true)) {
                    $q->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('gst_no', 'like', '%'.$search.'%');
                }
            });
        }

        $items = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('masters.index', compact('type', 'config', 'items', 'search'));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        [$model, $config] = $this->definition($type);
        $data = $this->validateMaster($request, $type);
        $model::query()->create($data);

        return back()->with('success', $config['singular'].' added successfully.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        [$model, $config] = $this->definition($type);
        $item = $model::query()->findOrFail($id);
        $data = $this->validateMaster($request, $type, $id);
        $item->update($data);

        return back()->with('success', $config['singular'].' updated successfully.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        [$model, $config] = $this->definition($type);
        $item = $model::query()->findOrFail($id);

        try {
            $item->delete();
        } catch (QueryException) {
            return back()->with('error', $config['singular'].' is already used in vouchers. Make it inactive instead of deleting it.');
        }

        return back()->with('success', $config['singular'].' deleted successfully.');
    }

    private function definition(string $type): array
    {
        return match ($type) {
            'customers' => [Customer::class, [
                'title' => 'Customers',
                'singular' => 'Customer',
                'columns' => ['Code', 'Name', 'Phone', 'GST No.', 'Opening Balance', 'Status'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Customer Code', 'type' => 'text'],
                    ['name' => 'name', 'label' => 'Customer Name', 'type' => 'text', 'required' => true],
                    ['name' => 'contact_person', 'label' => 'Contact Person', 'type' => 'text'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'gst_no', 'label' => 'GST No.', 'type' => 'text'],
                    ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'wide' => true],
                    ['name' => 'bill_note', 'label' => 'Bill Note', 'type' => 'textarea', 'wide' => true, 'default' => 'GST @5% WILL BE PAID BY SERVICE USER UNDER RCM'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ]],
            'suppliers' => [Supplier::class, [
                'title' => 'Suppliers / Transporters',
                'singular' => 'Supplier / Transporter',
                'columns' => ['Code', 'Name', 'Phone', 'GST No.', 'Opening Balance', 'Status'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Supplier Code', 'type' => 'text'],
                    ['name' => 'name', 'label' => 'Supplier / Transporter Name', 'type' => 'text', 'required' => true],
                    ['name' => 'contact_person', 'label' => 'Contact Person', 'type' => 'text'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'gst_no', 'label' => 'GST No.', 'type' => 'text'],
                    ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text'],
                    ['name' => 'bank_account', 'label' => 'Bank Account', 'type' => 'text'],
                    ['name' => 'ifsc', 'label' => 'IFSC', 'type' => 'text'],
                    ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'wide' => true],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ]],
            'vehicle-types' => [VehicleType::class, [
                'title' => 'Vehicle Types',
                'singular' => 'Vehicle Type',
                'columns' => ['Name', 'Description', 'Status'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Vehicle Type', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ]],
            'gst-rates' => [GstRate::class, [
                'title' => 'GST Master',
                'singular' => 'GST Rate',
                'columns' => ['Name', 'Rate', 'Status'],
                'fields' => [
                    ['name' => 'name', 'label' => 'GST Name', 'type' => 'text', 'required' => true],
                    ['name' => 'rate', 'label' => 'GST %', 'type' => 'number', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ]],
            'transport-names' => [TransportCompany::class, [
                'title' => 'Transport Names',
                'singular' => 'Transport Name',
                'columns' => ['Name', 'Status'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Transport Name', 'type' => 'text', 'required' => true],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ]],
            default => abort(404),
        };
    }

    private function validateMaster(Request $request, string $type, ?int $id = null): array
    {
        $base = [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($type === 'customers') {
            $base += [
                'code' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($id)],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'gst_no' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:2000'],
                'bill_note' => ['nullable', 'string', 'max:2000'],
                'opening_balance' => ['nullable', 'numeric'],
            ];
        } elseif ($type === 'suppliers') {
            $base += [
                'code' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($id)],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'gst_no' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:2000'],
                'bank_name' => ['nullable', 'string', 'max:255'],
                'bank_account' => ['nullable', 'string', 'max:255'],
                'ifsc' => ['nullable', 'string', 'max:30'],
                'opening_balance' => ['nullable', 'numeric'],
            ];
        } elseif ($type === 'vehicle-types') {
            $base['name'][] = Rule::unique('vehicle_types', 'name')->ignore($id);
            $base['description'] = ['nullable', 'string', 'max:255'];
        } elseif ($type === 'transport-names') {
            $base['name'][] = Rule::unique('transport_companies', 'name')->ignore($id);
        } elseif ($type === 'gst-rates') {
            $base['name'][] = Rule::unique('gst_rates', 'name')->ignore($id);
            $base['rate'] = ['required', 'numeric', 'min:0', 'max:100', Rule::unique('gst_rates', 'rate')->ignore($id)];
        }

        $data = $request->validate($base);
        $data['is_active'] = $request->boolean('is_active');

        if (array_key_exists('opening_balance', $data)) {
            $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        }

        return $data;
    }
}
