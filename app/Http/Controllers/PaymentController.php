<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPartyPayment;
use App\Models\Supplier;
use App\Models\SupplierPartyPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    public function supplierIndex(Request $request): View
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        $supplierId = $request->integer('supplier_id') ?: null;

        $query = SupplierPartyPayment::query()
            ->with('supplier:id,code,name')
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));

        $totalAmount = (float) (clone $query)->sum('amount');
        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(50)->withQueryString();
        $parties = Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return view('payments.index', [
            'paymentType' => 'supplier',
            'title' => 'Supplier Payment',
            'partyLabel' => 'Supplier / Transporter',
            'partyField' => 'supplier_id',
            'partyRelation' => 'supplier',
            'parties' => $parties,
            'payments' => $payments,
            'totalAmount' => $totalAmount,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedPartyId' => $supplierId,
            'storeRoute' => route('payments.suppliers.store'),
            'indexRoute' => route('payments.suppliers.index'),
            'attachmentRouteName' => 'payments.suppliers.attachment',
            'destroyRouteName' => 'payments.suppliers.destroy',
        ]);
    }

    public function supplierStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $attachment = $this->storeAttachment($request, 'supplier-payment-receipts');

        try {
            SupplierPartyPayment::query()->create([
                'supplier_id' => $validated['supplier_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => round((float) $validated['amount'], 2),
                'payment_mode' => $this->nullableText($validated['payment_mode'] ?? null),
                'reference' => $this->nullableText($validated['reference'] ?? null),
                'remarks' => $this->nullableText($validated['remarks'] ?? null),
                ...$attachment,
                'created_by' => $request->user()?->id,
            ]);
        } catch (\Throwable $exception) {
            if ($attachment['attachment_path']) {
                Storage::disk('local')->delete($attachment['attachment_path']);
            }

            throw $exception;
        }

        return redirect()->route('payments.suppliers.index')->with('success', 'Supplier payment added successfully.');
    }

    public function supplierAttachment(SupplierPartyPayment $payment): BinaryFileResponse
    {
        return $this->attachmentResponse($payment->attachment_path, $payment->attachment_name, $payment->attachment_mime);
    }

    public function supplierDestroy(SupplierPartyPayment $payment): RedirectResponse
    {
        $path = $payment->attachment_path;
        $payment->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'Supplier payment deleted.');
    }

    public function customerIndex(Request $request): View
    {
        $fromDate = $this->filterDate($request->string('from_date')->toString(), now()->startOfMonth()->toDateString());
        $toDate = $this->filterDate($request->string('to_date')->toString(), now()->toDateString());
        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        $customerId = $request->integer('customer_id') ?: null;

        $query = CustomerPartyPayment::query()
            ->with('customer:id,code,name')
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId));

        $totalAmount = (float) (clone $query)->sum('amount');
        $payments = $query->orderByDesc('payment_date')->orderByDesc('id')->paginate(50)->withQueryString();
        $parties = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return view('payments.index', [
            'paymentType' => 'customer',
            'title' => 'Customer Payment',
            'partyLabel' => 'Customer',
            'partyField' => 'customer_id',
            'partyRelation' => 'customer',
            'parties' => $parties,
            'payments' => $payments,
            'totalAmount' => $totalAmount,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'selectedPartyId' => $customerId,
            'storeRoute' => route('payments.customers.store'),
            'indexRoute' => route('payments.customers.index'),
            'attachmentRouteName' => 'payments.customers.attachment',
            'destroyRouteName' => 'payments.customers.destroy',
        ]);
    }

    public function customerStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $attachment = $this->storeAttachment($request, 'customer-payment-receipts');

        try {
            CustomerPartyPayment::query()->create([
                'customer_id' => $validated['customer_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => round((float) $validated['amount'], 2),
                'payment_mode' => $this->nullableText($validated['payment_mode'] ?? null),
                'reference' => $this->nullableText($validated['reference'] ?? null),
                'remarks' => $this->nullableText($validated['remarks'] ?? null),
                ...$attachment,
                'created_by' => $request->user()?->id,
            ]);
        } catch (\Throwable $exception) {
            if ($attachment['attachment_path']) {
                Storage::disk('local')->delete($attachment['attachment_path']);
            }

            throw $exception;
        }

        return redirect()->route('payments.customers.index')->with('success', 'Customer payment added successfully.');
    }

    public function customerAttachment(CustomerPartyPayment $payment): BinaryFileResponse
    {
        return $this->attachmentResponse($payment->attachment_path, $payment->attachment_name, $payment->attachment_mime);
    }

    public function customerDestroy(CustomerPartyPayment $payment): RedirectResponse
    {
        $path = $payment->attachment_path;
        $payment->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'Customer payment deleted.');
    }

    /**
     * @return array{attachment_name:?string,attachment_path:?string,attachment_mime:?string,attachment_size:int}
     */
    private function storeAttachment(Request $request, string $directory): array
    {
        $file = $request->file('attachment');
        if (! $file) {
            return [
                'attachment_name' => null,
                'attachment_path' => null,
                'attachment_mime' => null,
                'attachment_size' => 0,
            ];
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory, $filename, 'local');

        if (! $path) {
            abort(500, 'Payment attachment could not be stored.');
        }

        return [
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_path' => $path,
            'attachment_mime' => $file->getMimeType(),
            'attachment_size' => (int) ($file->getSize() ?: 0),
        ];
    }

    private function attachmentResponse(?string $path, ?string $name, ?string $mime): BinaryFileResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($name ?: 'receipt').'"',
        ]);
    }

    private function filterDate(string $value, string $fallback): string
    {
        if ($value === '') {
            return $fallback;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
