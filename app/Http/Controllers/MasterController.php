<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Customer;
use App\Models\GstRate;
use App\Models\PrintSetting;
use App\Models\Supplier;
use App\Models\TransportName;
use App\Models\VehicleType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterController extends Controller
{
    public function index(Request $request, string $type): View
    {
        [$model, $config] = $this->definition($type);
        $search = trim($request->string('search')->toString());

        $query = $model::query();
        if ($type === 'banks') {
            $query->with('transportName:id,name,gst_no');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search, $type) {
                $q->where('name', 'like', '%'.$search.'%');

                if (in_array($type, ['customers', 'suppliers'], true)) {
                    $q->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('gst_no', 'like', '%'.$search.'%')
                        ->orWhere('pan_no', 'like', '%'.$search.'%');
                } elseif ($type === 'transport-names') {
                    $q->orWhere('gst_no', 'like', '%'.$search.'%');
                } elseif ($type === 'banks') {
                    $q->orWhere('account_holder_name', 'like', '%'.$search.'%')
                        ->orWhere('account_number', 'like', '%'.$search.'%')
                        ->orWhere('ifsc_code', 'like', '%'.$search.'%')
                        ->orWhere('branch_name', 'like', '%'.$search.'%')
                        ->orWhereHas('transportName', fn ($company) => $company->where('name', 'like', '%'.$search.'%'));
                }
            });
        }

        $items = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('masters.index', compact('type', 'config', 'items', 'search'));
    }

    public function store(Request $request, string $type): RedirectResponse|JsonResponse
    {
        [$model, $config] = $this->definition($type);
        $data = $this->validateMaster($request, $type);

        if ($type === 'customers') {
            // Customer codes are system-generated on create. Ignore any client-supplied code.
            $data['code'] = $this->nextCustomerCode();
        }

        $item = DB::transaction(function () use ($model, $data, $type, $request) {
            $item = $model::query()->create($data);
            if ($type === 'banks') {
                $this->syncBankDefault($item, $request->boolean('is_default'));
            }
            return $item;
        });
        $message = $config['singular'].' added successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'item' => $this->optionPayload($item, $type),
            ]);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        [$model, $config] = $this->definition($type);
        $item = $model::query()->findOrFail($id);
        $oldCompanyId = $type === 'banks' ? (int) ($item->transport_name_id ?? 0) : 0;
        $data = $this->validateMaster($request, $type, $id);

        DB::transaction(function () use ($item, $data, $type, $request, $oldCompanyId) {
            $item->update($data);
            if ($type === 'banks') {
                $this->syncBankDefault($item->fresh(), $request->boolean('is_default'));
                if ($oldCompanyId && $oldCompanyId !== (int) $item->transport_name_id) {
                    $this->ensureCompanyHasDefaultBank($oldCompanyId);
                }
            }
        });

        return back()->with('success', $config['singular'].' updated successfully.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        [$model, $config] = $this->definition($type);
        $item = $model::query()->findOrFail($id);

        $oldCompanyId = $type === 'banks' ? (int) ($item->transport_name_id ?? 0) : 0;
        try {
            DB::transaction(function () use ($item, $type, $oldCompanyId) {
                $item->delete();
                if ($type === 'banks' && $oldCompanyId) {
                    $this->ensureCompanyHasDefaultBank($oldCompanyId);
                }
            });
        } catch (QueryException) {
            return back()->with('error', $config['singular'].' is already in use. Make it inactive instead of deleting it.');
        }

        return back()->with('success', $config['singular'].' deleted successfully.');
    }

    public function createForm(string $type): JsonResponse
    {
        [, $config] = $this->definition($type);

        return response()->json([
            'singular' => $config['singular'],
            'html' => view('masters.partials.form-fields', compact('config'))->render(),
        ]);
    }

    /**
     * Reusable keyboard-friendly master selector API for SO/Trip/Ledger screens.
     */
    public function options(Request $request, string $type): JsonResponse
    {
        [$model] = $this->definition($type);
        $search = trim($request->string('q')->toString());
        $query = $model::query()->where('is_active', true);

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

        $items = $query->orderBy('name')->limit(100)->get()->map(fn ($item) => $this->optionPayload($item, $type));

        return response()->json(['items' => $items]);
    }

    public function settings(): View
    {
        $settings = PrintSetting::current();

        return view('masters.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'letterhead_top_margin_mm' => ['required', 'numeric', 'min:0', 'max:200'],
            'letterhead_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_letterhead' => ['nullable', 'boolean'],
        ]);

        $settings = PrintSetting::current();
        $data = ['letterhead_top_margin_mm' => (float) $validated['letterhead_top_margin_mm']];

        if ($request->boolean('remove_letterhead') && $settings->letterhead_image) {
            $oldPath = public_path($settings->letterhead_image);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
            $data['letterhead_image'] = null;
        }

        if ($request->hasFile('letterhead_image')) {
            if ($settings->letterhead_image) {
                $oldPath = public_path($settings->letterhead_image);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $directory = public_path('uploads/letterheads');
            File::ensureDirectoryExists($directory);
            $file = $request->file('letterhead_image');
            $filename = 'letterhead-'.Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move($directory, $filename);
            $data['letterhead_image'] = 'uploads/letterheads/'.$filename;
        }

        $settings->update($data);

        return back()->with('success', 'Print settings saved successfully.');
    }

    private function definition(string $type): array
    {
        return match ($type) {
            'customers' => [Customer::class, [
                'title' => 'Customers',
                'singular' => 'Customer',
                'columns' => ['Code', 'Name', 'Phone', 'GST No.', 'Opening Balance', 'Status'],
                'fields' => [
                    ['name' => 'code', 'label' => 'Customer Code', 'type' => 'text', 'create_hidden' => true],
                    ['name' => 'name', 'label' => 'Customer Name', 'type' => 'text', 'required' => true],
                    ['name' => 'contact_person', 'label' => 'Contact Person', 'type' => 'text'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'gst_no', 'label' => 'GST No.', 'type' => 'text'],
                    ['name' => 'pan_no', 'label' => 'PAN', 'type' => 'text'],
                    ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'wide' => true],
                    ['name' => 'business_type', 'label' => 'Business Type', 'type' => 'select', 'required' => true, 'options' => ['b2b' => 'B2B', 'b2c' => 'B2C', 'government' => 'Government']],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
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
            'transport-names' => [TransportName::class, [
                'title' => 'Transport Names / Companies',
                'singular' => 'Transport Name / Company',
                'columns' => ['Name', 'GST No.', 'Status'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Transport Name / Company', 'type' => 'text', 'required' => true],
                    ['name' => 'gst_no', 'label' => 'Company GST No.', 'type' => 'text'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ]],
            'banks' => [Bank::class, [
                'title' => 'Bank Master',
                'singular' => 'Bank',
                'columns' => ['Company', 'Bank Name', 'A/C No.', 'IFSC', 'Opening Balance', 'Default', 'Status'],
                'fields' => [
                    ['name' => 'transport_name_id', 'label' => 'Company', 'type' => 'select', 'required' => true, 'options' => TransportName::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()],
                    ['name' => 'name', 'label' => 'Bank Name', 'type' => 'text', 'required' => true],
                    ['name' => 'account_holder_name', 'label' => 'Account Holder Name', 'type' => 'text'],
                    ['name' => 'account_number', 'label' => 'Account Number', 'type' => 'text'],
                    ['name' => 'ifsc_code', 'label' => 'IFSC Code', 'type' => 'text'],
                    ['name' => 'branch_name', 'label' => 'Branch', 'type' => 'text'],
                    ['name' => 'bank_address', 'label' => 'Bank Address', 'type' => 'textarea', 'wide' => true],
                    ['name' => 'opening_balance', 'label' => 'Opening Balance', 'type' => 'number', 'required' => true],
                    ['name' => 'is_default', 'label' => 'Default Bank for this Company', 'type' => 'checkbox', 'wide' => true, 'default' => false],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
                ],
            ]],
            default => abort(404),
        };
    }


    private function nextCustomerCode(): string
    {
        $highest = 0;

        Customer::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->each(function ($code) use (&$highest) {
                if (preg_match('/^CUST(\d+)$/i', trim((string) $code), $matches)) {
                    $highest = max($highest, (int) $matches[1]);
                }
            });

        return 'CUST'.str_pad((string) ($highest + 1), 3, '0', STR_PAD_LEFT);
    }

    private function optionPayload($item, string $type): array
    {
        $label = $item->name;
        if (in_array($type, ['customers', 'suppliers'], true) && filled($item->code ?? null)) {
            $label .= ' ('.$item->code.')';
        }
        if ($type === 'gst-rates') {
            $label = rtrim(rtrim(number_format((float) $item->rate, 2, '.', ''), '0'), '.').'%';
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'code' => $item->code ?? null,
            'gst_no' => $item->gst_no ?? null,
            'rate' => $item->rate ?? null,
            'opening_balance' => $item->opening_balance ?? null,
            'label' => $label,
        ];
    }

    private function validateMaster(Request $request, string $type, ?int $id = null): array
    {
        $base = [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($type === 'customers') {
            $base += [
                'contact_person' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'gst_no' => ['nullable', 'string', 'max:30'],
                'pan_no' => ['nullable', 'string', 'max:20'],
                'address' => ['nullable', 'string', 'max:2000'],
                'business_type' => ['required', Rule::in(['b2b', 'b2c', 'government'])],
                'opening_balance' => ['nullable', 'numeric'],
            ];

            if ($id !== null) {
                $base['code'] = ['nullable', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($id)];
            }
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
            $base['name'][] = Rule::unique('transport_names', 'name')->ignore($id);
            $base['gst_no'] = ['nullable', 'string', 'max:30'];
        } elseif ($type === 'gst-rates') {
            $base['name'][] = Rule::unique('gst_rates', 'name')->ignore($id);
            $base['rate'] = ['required', 'numeric', 'min:0', 'max:100', Rule::unique('gst_rates', 'rate')->ignore($id)];
        } elseif ($type === 'banks') {
            $companyId = (int) $request->input('transport_name_id');
            $base['transport_name_id'] = ['required', 'integer', Rule::exists('transport_names', 'id')->where(fn ($q) => $q->where('is_active', true))];
            $base['name'][] = Rule::unique('banks', 'name')->where(fn ($q) => $q->where('transport_name_id', $companyId))->ignore($id);
            $base['account_holder_name'] = ['nullable', 'string', 'max:255'];
            $base['account_number'] = ['nullable', 'string', 'max:100'];
            $base['ifsc_code'] = ['nullable', 'string', 'max:30'];
            $base['branch_name'] = ['nullable', 'string', 'max:255'];
            $base['bank_address'] = ['nullable', 'string', 'max:1000'];
            $base['opening_balance'] = ['required', 'numeric'];
            $base['is_default'] = ['nullable', 'boolean'];
        }

        $data = $request->validate($base);
        $data['is_active'] = $request->boolean('is_active');
        if ($type === 'banks') {
            $data['is_default'] = $request->boolean('is_default');
            $data['ifsc_code'] = filled($data['ifsc_code'] ?? null) ? strtoupper(trim($data['ifsc_code'])) : null;
            $data['account_number'] = filled($data['account_number'] ?? null) ? trim($data['account_number']) : null;
        }
        if ($type === 'transport-names') {
            $data['gst_no'] = filled($data['gst_no'] ?? null) ? strtoupper(trim($data['gst_no'])) : null;
        }

        if ($type === 'customers') {
            $data['pan_no'] = filled($data['pan_no'] ?? null) ? strtoupper(trim($data['pan_no'])) : null;
            $data['is_government_employee'] = $request->boolean('is_government_employee');
            if (!$data['is_government_employee']) {
                $data['bill_note'] = null;
            } elseif (blank($data['bill_note'] ?? null)) {
                $data['bill_note'] = 'GST @5% WILL BE PAID BY SERVICE USER UNDER RCM (If Applicable)';
            }
        }

        if (array_key_exists('opening_balance', $data)) {
            $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        }

        return $data;
    }
    private function syncBankDefault(Bank $bank, bool $requestedDefault): void
    {
        $companyId = (int) $bank->transport_name_id;
        if (! $companyId) {
            return;
        }

        if (! $bank->is_active) {
            if ($bank->is_default) {
                $bank->forceFill(['is_default' => false])->saveQuietly();
            }
            $this->ensureCompanyHasDefaultBank($companyId);
            return;
        }

        if ($requestedDefault) {
            Bank::query()->where('transport_name_id', $companyId)->whereKeyNot($bank->id)->update(['is_default' => false]);
            if (! $bank->is_default) {
                $bank->forceFill(['is_default' => true])->saveQuietly();
            }
            return;
        }

        $otherDefault = Bank::query()
            ->where('transport_name_id', $companyId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->whereKeyNot($bank->id)
            ->first();

        if ($otherDefault) {
            if ($bank->is_default) {
                $bank->forceFill(['is_default' => false])->saveQuietly();
            }
            return;
        }

        $replacement = Bank::query()
            ->where('transport_name_id', $companyId)
            ->where('is_active', true)
            ->whereKeyNot($bank->id)
            ->orderBy('id')
            ->first();

        if ($replacement) {
            if ($bank->is_default) {
                $bank->forceFill(['is_default' => false])->saveQuietly();
            }
            $replacement->forceFill(['is_default' => true])->saveQuietly();
        } elseif (! $bank->is_default) {
            // A company with only one active bank should always have that bank as default.
            $bank->forceFill(['is_default' => true])->saveQuietly();
        }
    }

    private function ensureCompanyHasDefaultBank(int $companyId): void
    {
        if ($companyId <= 0) {
            return;
        }

        $hasDefault = Bank::query()
            ->where('transport_name_id', $companyId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->exists();

        if ($hasDefault) {
            return;
        }

        $fallback = Bank::query()
            ->where('transport_name_id', $companyId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($fallback) {
            $fallback->forceFill(['is_default' => true])->saveQuietly();
        }
    }

}
