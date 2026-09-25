<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Company;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());

        $items = Company::query()
            ->with(['banks' => fn ($query) => $query->orderByDesc('is_default')->orderBy('name')])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('gst_no', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhereHas('banks', function ($bank) use ($search) {
                            $bank->where('name', 'like', '%'.$search.'%')
                                ->orWhere('account_number', 'like', '%'.$search.'%')
                                ->orWhere('ifsc_code', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('masters.companies', compact('items', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyData = $this->validatedCompany($request);
        $bankRows = $this->validatedBanks($request);

        DB::transaction(function () use ($companyData, $bankRows): void {
            $company = Company::query()->create($companyData);
            $this->syncBanks($company, $bankRows);
        });

        return back()->with('success', 'Company and bank details added successfully.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $companyData = $this->validatedCompany($request, $company->id);
        $bankRows = $this->validatedBanks($request, $company);

        try {
            DB::transaction(function () use ($company, $companyData, $bankRows): void {
                $company->update($companyData);
                $this->syncBanks($company, $bankRows);
            });
        } catch (QueryException) {
            return back()->withInput()->with('error', 'One of the banks is already in use and cannot be removed. Make it inactive instead.');
        }

        return back()->with('success', 'Company and bank details updated successfully.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        try {
            $company->delete();
        } catch (QueryException) {
            return back()->with('error', 'This company is already used in Bank/Voucher records and cannot be deleted.');
        }

        return back()->with('success', 'Company deleted successfully.');
    }

    private function validatedCompany(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('companies', 'name')->ignore($ignoreId),
            ],
            'gst_no' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:2000'],
        ]);

        return [
            'name' => trim((string) $validated['name']),
            'gst_no' => strtoupper(trim((string) $validated['gst_no'])),
            'address' => trim((string) $validated['address']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validatedBanks(Request $request, ?Company $company = null): array
    {
        $rawRows = $request->input('banks', []);
        $rawRows = is_array($rawRows) ? $rawRows : [];

        $rows = collect($rawRows)
            ->filter(function ($row): bool {
                if (! is_array($row)) {
                    return false;
                }

                if (! empty($row['id']) || ! empty($row['_delete'])) {
                    return true;
                }

                foreach (['name', 'account_holder_name', 'account_number', 'ifsc_code', 'branch_name', 'bank_address'] as $field) {
                    if (trim((string) ($row[$field] ?? '')) !== '') {
                        return true;
                    }
                }

                return (float) ($row['opening_balance'] ?? 0) !== 0.0;
            })
            ->values()
            ->all();

        $validated = Validator::make(['banks' => $rows], [
            'banks' => ['array'],
            'banks.*.id' => ['nullable', 'integer'],
            'banks.*.name' => ['nullable', 'string', 'max:255'],
            'banks.*.account_holder_name' => ['nullable', 'string', 'max:255'],
            'banks.*.account_number' => ['nullable', 'string', 'max:100'],
            'banks.*.ifsc_code' => ['nullable', 'string', 'max:30'],
            'banks.*.branch_name' => ['nullable', 'string', 'max:255'],
            'banks.*.bank_address' => ['nullable', 'string', 'max:1000'],
            'banks.*.opening_balance' => ['nullable', 'numeric'],
            'banks.*.is_default' => ['nullable', 'boolean'],
            'banks.*.is_active' => ['nullable', 'boolean'],
            'banks.*._delete' => ['nullable', 'boolean'],
        ])->validate();

        $seenNames = [];
        $result = [];

        foreach ($validated['banks'] ?? [] as $index => $row) {
            $id = ! empty($row['id']) ? (int) $row['id'] : null;
            $delete = filter_var($row['_delete'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($id !== null && $company !== null) {
                $belongsToCompany = Bank::query()
                    ->whereKey($id)
                    ->where('company_id', $company->id)
                    ->exists();

                if (! $belongsToCompany) {
                    throw ValidationException::withMessages([
                        'banks.'.$index.'.id' => 'Selected bank does not belong to this company.',
                    ]);
                }
            }

            if ($delete) {
                if ($id !== null) {
                    $result[] = ['id' => $id, '_delete' => true];
                }

                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    'banks.'.$index.'.name' => 'Bank Name is required when bank details are entered.',
                ]);
            }

            $nameKey = mb_strtolower($name);
            if (isset($seenNames[$nameKey])) {
                throw ValidationException::withMessages([
                    'banks.'.$index.'.name' => 'The same bank name cannot be added twice for one company.',
                ]);
            }
            $seenNames[$nameKey] = true;

            if ($company !== null) {
                $duplicate = Bank::query()
                    ->where('company_id', $company->id)
                    ->whereRaw('LOWER(name) = ?', [$nameKey])
                    ->when($id !== null, fn ($query) => $query->whereKeyNot($id))
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'banks.'.$index.'.name' => 'This bank already exists for the selected company.',
                    ]);
                }
            }

            $result[] = [
                'id' => $id,
                'name' => $name,
                'account_holder_name' => $this->nullableText($row['account_holder_name'] ?? null),
                'account_number' => $this->nullableText($row['account_number'] ?? null),
                'ifsc_code' => ($ifsc = $this->nullableText($row['ifsc_code'] ?? null)) !== null ? strtoupper($ifsc) : null,
                'branch_name' => $this->nullableText($row['branch_name'] ?? null),
                'bank_address' => $this->nullableText($row['bank_address'] ?? null),
                'opening_balance' => round((float) ($row['opening_balance'] ?? 0), 2),
                'is_default' => filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                '_delete' => false,
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $bankRows
     */
    private function syncBanks(Company $company, array $bankRows): void
    {
        $requestedDefaultId = null;

        foreach ($bankRows as $row) {
            $bankId = $row['id'] ?? null;

            if (($row['_delete'] ?? false) === true) {
                if ($bankId !== null) {
                    $company->banks()->whereKey($bankId)->firstOrFail()->delete();
                }

                continue;
            }

            $payload = [
                'name' => $row['name'],
                'account_holder_name' => $row['account_holder_name'],
                'account_number' => $row['account_number'],
                'ifsc_code' => $row['ifsc_code'],
                'branch_name' => $row['branch_name'],
                'bank_address' => $row['bank_address'],
                'opening_balance' => $row['opening_balance'],
                'is_default' => false,
                'is_active' => $row['is_active'],
            ];

            if ($bankId !== null) {
                $bank = $company->banks()->whereKey($bankId)->firstOrFail();
                $bank->update($payload);
            } else {
                $bank = $company->banks()->create($payload);
            }

            if ($row['is_default'] && $bank->is_active && $requestedDefaultId === null) {
                $requestedDefaultId = $bank->id;
            }
        }

        $activeBanks = $company->banks()->where('is_active', true)->orderBy('id')->get();

        if ($activeBanks->isEmpty()) {
            $company->banks()->update(['is_default' => false]);
            return;
        }

        if ($requestedDefaultId === null) {
            $requestedDefaultId = $activeBanks->firstWhere('is_default', true)?->id
                ?? $activeBanks->first()->id;
        }

        $company->banks()->update(['is_default' => false]);
        $company->banks()
            ->whereKey($requestedDefaultId)
            ->where('is_active', true)
            ->update(['is_default' => true]);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
