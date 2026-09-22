<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\InvoiceAttachment;
use App\Models\InvoiceBatch;
use App\Models\InvoiceItem;
use App\Models\PrintSetting;
use App\Models\SalesOrder;
use App\Models\Voucher;
use App\Models\VoucherAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $selectedCustomer = $request->integer('customer_id') ? Customer::find($request->integer('customer_id')) : null;
        $selectedOrder = $request->integer('sales_order_id') ? SalesOrder::with('customer')->find($request->integer('sales_order_id')) : null;

        return view('sale.billing.index', compact('selectedCustomer', 'selectedOrder'));
    }

    public function soOptions(Request $request): JsonResponse
    {
        $customerId = $request->integer('customer_id');
        abort_unless($customerId, 422, 'Please select a customer first.');
        $search = trim($request->string('q')->toString());
        $query = SalesOrder::query()
            ->with('customer:id,name,code')
            ->withCount(['vouchers', 'vouchers as billed_vouchers_count' => fn ($q) => $q->whereHas('invoiceItem')])
            ->where('customer_id', $customerId)
            ->where('is_active', true)
            ->whereHas('vouchers', fn ($v) => $v->whereDoesntHave('invoiceItem'));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('from_location', 'like', '%'.$search.'%')
                    ->orWhere('to_location', 'like', '%'.$search.'%');
            });
        }

        $items = $query->orderByDesc('so_date')->orderByDesc('id')->limit(100)->get()->map(function (SalesOrder $order) {
            $pending = max(0, (int) $order->vouchers_count - (int) $order->billed_vouchers_count);
            return [
                'id' => $order->id,
                'name' => $order->so_number,
                'label' => $order->so_number.' · '.$order->from_location.' → '.$order->to_location.' · Pending '.$pending.' truck'.($pending === 1 ? '' : 's'),
                'so_number' => $order->so_number,
                'pending_trips' => $pending,
                'from_location' => $order->from_location,
                'to_location' => $order->to_location,
                'description' => $order->description,
            ];
        });

        return response()->json(['items' => $items]);
    }

    public function data(Request $request): JsonResponse
    {
        $editInvoice = null;
        $editInvoiceId = $request->integer('edit_invoice_id') ?: null;
        if ($editInvoiceId) {
            $editInvoice = InvoiceBatch::query()->with(['customer:id,name,code', 'salesOrder'])->findOrFail($editInvoiceId);
        }

        $customerId = $editInvoice?->customer_id ?: ($request->integer('customer_id') ?: null);
        $orderId = $editInvoice?->sales_order_id ?: ($request->integer('sales_order_id') ?: null);

        $customer = $customerId ? Customer::find($customerId) : null;
        $order = $orderId ? SalesOrder::with('customer')->find($orderId) : null;
        if ($order && $customer && $order->customer_id !== $customer->id) {
            abort(422, 'Selected SO does not belong to selected customer.');
        }

        $trips = collect();
        $orderPayload = null;
        if ($order) {
            $allVoucherCount = Voucher::query()->where('sales_order_id', $order->id)->count();
            $billedCount = Voucher::query()->where('sales_order_id', $order->id)->whereHas('invoiceItem')->count();
            $pendingCount = max(0, $allVoucherCount - $billedCount);

            $tripQuery = Voucher::query()
                ->with([
                    'transportName:id,name', 'vehicleType:id,name', 'supplier:id,name',
                    'invoiceItem.invoiceBatch:id,bill_no,invoice_date',
                    'attachments:id,voucher_id,original_name,mime_type,file_size',
                ]);

            if ($editInvoice) {
                $tripQuery->whereHas('invoiceItem', fn ($q) => $q->where('invoice_batch_id', $editInvoice->id));
            } else {
                $tripQuery->where('sales_order_id', $order->id);
            }

            $trips = $tripQuery->orderBy('lr_date')->orderBy('sr_no')->get();

            $orderPayload = [
                'id' => $order->id,
                'so_number' => $order->so_number,
                'so_date' => $order->so_date?->format('d-m-Y'),
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer?->name,
                'from_location' => $order->from_location,
                'to_location' => $order->to_location,
                'description' => $order->description,
                'trips_quantity' => (int) $order->trips_quantity,
                'voucher_count' => $allVoucherCount,
                'billed_count' => $billedCount,
                'pending_count' => $pendingCount,
                'per_trip_cost' => (float) $order->per_trip_cost,
                'tax_mode' => $order->tax_mode ?: 'rcm',
                'gst_rate' => (float) $order->gst_rate,
            ];
        }

        $invoiceQuery = InvoiceBatch::query()
            ->withCount('items')
            ->withSum('payments as paid_amount', 'amount')
            ->with([
                'customer:id,name',
                'salesOrder:id,so_number',
                'attachments:id,invoice_batch_id,original_name,mime_type',
                'items.voucher:id,lr_no,sr_no',
                'items.voucher.attachments:id,voucher_id,original_name,mime_type,file_size',
            ])
            ->orderByDesc('invoice_date')->orderByDesc('id');
        if ($customer) $invoiceQuery->where('customer_id', $customer->id);
        else $invoiceQuery->whereRaw('1=0');
        if ($order && !$editInvoice) $invoiceQuery->where('sales_order_id', $order->id);
        $invoices = $invoiceQuery->limit(100)->get();

        if ($editInvoice) {
            $editInvoice->loadCount('items')
                ->loadSum('payments as paid_amount', 'amount')
                ->load([
                    'customer:id,name',
                    'salesOrder:id,so_number',
                    'attachments:id,invoice_batch_id,original_name,mime_type',
                    'items.voucher:id,lr_no,sr_no',
                    'items.voucher.attachments:id,voucher_id,original_name,mime_type,file_size',
                ]);
        }

        return response()->json([
            'customer' => $customer ? ['id'=>$customer->id,'name'=>$customer->name,'code'=>$customer->code] : null,
            'order' => $orderPayload,
            'editing_invoice' => $editInvoice ? $this->invoicePayload($editInvoice) : null,
            'trips' => $trips->map(function (Voucher $v) {
                $invoice = $v->invoiceItem?->invoiceBatch;
                return [
                    'id' => $v->id,
                    'sr_no' => $v->sr_no,
                    'lr_date' => $v->lr_date?->format('d-m-Y'),
                    'lr_date_raw' => $v->lr_date?->toDateString(),
                    'lr_no' => $v->lr_no,
                    'lorry_number' => $v->lorry_number,
                    'vehicle_type' => $v->vehicleType?->name,
                    'transport_name' => $v->transportName?->name,
                    'supplier' => $v->supplier?->name,
                    'description' => $v->description,
                    'other_charges' => (float) $v->other_charges,
                    'billed' => (bool) $invoice,
                    'bill_no' => $invoice?->bill_no,
                    'invoice_id' => $invoice?->id,
                    'attachments' => $this->voucherAttachmentPayload($v),
                ];
            })->values(),
            'invoices' => $invoices->map(fn (InvoiceBatch $i) => $this->invoicePayload($i))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['customer_freight' => $request->input('customer_freight', $request->input('total_amount'))]);

        $validated = $request->validate([
            'customer_id' => ['required','integer','exists:customers,id'],
            'sales_order_id' => ['required','integer','exists:sales_orders,id'],
            'voucher_ids' => ['required','array','min:1'],
            'voucher_ids.*' => ['integer','distinct','exists:vouchers,id'],
            'invoice_date' => ['required','date'],
            'customer_freight' => ['required','numeric','gt:0','max:999999999999.99'],
            'remarks' => ['nullable','string','max:2000'],
            'voucher_attachments' => ['nullable','array'],
            'voucher_attachments.*' => ['nullable','array','max:20'],
            'voucher_attachments.*.*' => ['file','mimes:pdf,jpg,jpeg,png,webp','max:10240'],
        ]);

        $order = SalesOrder::with('customer')->findOrFail($validated['sales_order_id']);
        if ($order->customer_id !== (int) $validated['customer_id']) {
            throw ValidationException::withMessages(['sales_order_id' => 'Selected SO does not belong to selected customer.']);
        }

        $filesWritten = [];
        try {
            $invoice = DB::transaction(function () use ($request, $validated, $order, &$filesWritten) {
                $ids = collect($validated['voucher_ids'])->map(fn ($v)=>(int)$v)->unique()->values();
                $vouchers = Voucher::query()->where('sales_order_id', $order->id)->whereIn('id', $ids)->lockForUpdate()->get();
                if ($vouchers->count() !== $ids->count()) {
                    throw ValidationException::withMessages(['voucher_ids' => 'One or more selected truck rows do not belong to this SO.']);
                }

                if (InvoiceItem::query()->whereIn('voucher_id', $ids)->exists()) {
                    throw ValidationException::withMessages(['voucher_ids' => 'One or more selected trucks are already billed. Reload and select only pending trucks.']);
                }

                $uploadedIds = $this->uploadedVoucherIds($request);
                if ($uploadedIds->diff($ids)->isNotEmpty()) {
                    throw ValidationException::withMessages(['voucher_attachments' => 'An attachment was submitted for a truck that is not selected for this bill.']);
                }

                $vouchers->load('attachments');
                foreach ($vouchers as $voucher) {
                    $newFiles = $request->file('voucher_attachments.'.$voucher->id, []);
                    if ($voucher->attachments->count() + count($newFiles) < 1) {
                        $lr = filled($voucher->lr_no) ? $voucher->lr_no : 'SR '.$voucher->sr_no;
                        throw ValidationException::withMessages(['voucher_attachments' => 'Add at least one attachment for LR '.$lr.'.']);
                    }
                    if ($voucher->attachments->count() + count($newFiles) > 20) {
                        throw ValidationException::withMessages(['voucher_attachments' => 'A maximum of 20 attachments is allowed per LR.']);
                    }
                }

                $count = $vouchers->count();
                $otherCharges = round((float) $vouchers->sum('other_charges'), 2);
                $freight = round((float) $validated['customer_freight'], 2);
                $tax = $this->taxAmounts($order->tax_mode, $freight, $otherCharges, (float) $order->gst_rate);

                $invoice = InvoiceBatch::query()->create([
                    'bill_no' => 'TMP-'.Str::uuid(),
                    'invoice_date' => $validated['invoice_date'],
                    'customer_id' => $order->customer_id,
                    'sales_order_id' => $order->id,
                    'tax_mode' => $tax['mode'],
                    'trip_count' => $count,
                    'customer_freight' => $freight,
                    'gst_rate' => $tax['gst_rate'],
                    'gst_amount' => $tax['gst_amount'],
                    'rcm_rate' => $tax['rcm_rate'],
                    'rcm_amount' => $tax['rcm_amount'],
                    'other_charges' => $otherCharges,
                    'total_amount' => $tax['total_amount'],
                    'remarks' => filled($validated['remarks'] ?? null) ? trim($validated['remarks']) : null,
                    'created_by' => $request->user()?->id,
                ]);
                $invoice->update(['bill_no' => 'BILL-'.str_pad((string)$invoice->id, 6, '0', STR_PAD_LEFT)]);

                foreach ($vouchers as $voucher) {
                    InvoiceItem::query()->create([
                        'invoice_batch_id' => $invoice->id,
                        'voucher_id' => $voucher->id,
                        'rate' => 0,
                        'taxable_amount' => 0,
                        'gst_rate' => 0,
                        'gst_amount' => 0,
                        'line_total' => 0,
                    ]);
                }
                $this->syncInvoiceItemAmounts($invoice, $freight, $tax['gst_rate'], $tax['gst_amount']);
                $this->storeVoucherAttachments($request, $vouchers, $filesWritten);

                return $invoice;
            });
        } catch (\Throwable $e) {
            foreach ($filesWritten as $path) Storage::disk('local')->delete($path);
            throw $e;
        }

        $invoice->loadCount('items')->loadSum('payments as paid_amount', 'amount')->load([
            'customer:id,name',
            'salesOrder:id,so_number',
            'attachments:id,invoice_batch_id,original_name,mime_type',
            'items.voucher:id,lr_no,sr_no',
            'items.voucher.attachments:id,voucher_id,original_name,mime_type,file_size',
        ]);

        return response()->json([
            'message' => $invoice->bill_no.' created successfully.',
            'invoice' => $this->invoicePayload($invoice),
            'print_url' => route('sale.billing.print', $invoice),
        ]);
    }

    public function update(Request $request, InvoiceBatch $invoice): JsonResponse
    {
        $request->merge(['customer_freight' => $request->input('customer_freight', $request->input('total_amount'))]);

        $validated = $request->validate([
            'invoice_date' => ['required','date'],
            'customer_freight' => ['required','numeric','gt:0','max:999999999999.99'],
            'remarks' => ['nullable','string','max:2000'],
            'voucher_attachments' => ['nullable','array'],
            'voucher_attachments.*' => ['nullable','array','max:20'],
            'voucher_attachments.*.*' => ['file','mimes:pdf,jpg,jpeg,png,webp','max:10240'],
            'remove_voucher_attachment_ids' => ['nullable','array','max:200'],
            'remove_voucher_attachment_ids.*' => ['integer','distinct'],
        ]);

        $removeIds = collect($validated['remove_voucher_attachment_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $filesWritten = [];
        $filesToDelete = [];

        try {
            DB::transaction(function () use ($request, $validated, $invoice, $removeIds, &$filesWritten, &$filesToDelete) {
                $locked = InvoiceBatch::query()->lockForUpdate()->findOrFail($invoice->id);
                $vouchers = Voucher::query()
                    ->whereHas('invoiceItem', fn ($query) => $query->where('invoice_batch_id', $locked->id))
                    ->with('attachments')
                    ->lockForUpdate()
                    ->get();
                $voucherIds = $vouchers->pluck('id')->map(fn ($id) => (int) $id)->values();

                $uploadedIds = $this->uploadedVoucherIds($request);
                if ($uploadedIds->diff($voucherIds)->isNotEmpty()) {
                    throw ValidationException::withMessages(['voucher_attachments' => 'An attachment was submitted for a truck outside this bill.']);
                }

                $toRemove = collect();
                if ($removeIds->isNotEmpty()) {
                    $toRemove = VoucherAttachment::query()->whereIn('id', $removeIds)->whereIn('voucher_id', $voucherIds)->get();
                    if ($toRemove->count() !== $removeIds->count()) {
                        throw ValidationException::withMessages(['voucher_attachments' => 'One or more LR attachments could not be removed. Reload and try again.']);
                    }
                }

                // Old invoices can retain bill-level files because they cannot be safely assigned to one LR.
                $hasLegacyAttachments = $locked->attachments()->exists();
                foreach ($vouchers as $voucher) {
                    $removed = $toRemove->where('voucher_id', $voucher->id)->count();
                    $newFiles = $request->file('voucher_attachments.'.$voucher->id, []);
                    $remaining = $voucher->attachments->count() - $removed + count($newFiles);
                    if (! $hasLegacyAttachments && $remaining < 1) {
                        $lr = filled($voucher->lr_no) ? $voucher->lr_no : 'SR '.$voucher->sr_no;
                        throw ValidationException::withMessages(['voucher_attachments' => 'Keep or add at least one attachment for LR '.$lr.'.']);
                    }
                    if ($remaining > 20) {
                        throw ValidationException::withMessages(['voucher_attachments' => 'A maximum of 20 attachments is allowed per LR.']);
                    }
                }

                $otherCharges = round((float) $vouchers->sum('other_charges'), 2);
                $freight = round((float) $validated['customer_freight'], 2);
                $tax = $this->taxAmounts($locked->tax_mode, $freight, $otherCharges, (float) $locked->gst_rate);
                $paid = round((float) $locked->payments()->sum('amount'), 2);
                if ($tax['total_amount'] + 0.004 < $paid) {
                    throw ValidationException::withMessages([
                        'customer_freight' => 'Total Invoice Amount cannot be lower than already paid amount ₹'.number_format($paid, 2).'.',
                    ]);
                }

                $locked->update([
                    'invoice_date' => $validated['invoice_date'],
                    'customer_freight' => $freight,
                    'tax_mode' => $tax['mode'],
                    'gst_rate' => $tax['gst_rate'],
                    'gst_amount' => $tax['gst_amount'],
                    'rcm_rate' => $tax['rcm_rate'],
                    'rcm_amount' => $tax['rcm_amount'],
                    'other_charges' => $otherCharges,
                    'total_amount' => $tax['total_amount'],
                    'remarks' => filled($validated['remarks'] ?? null) ? trim($validated['remarks']) : null,
                ]);

                $this->syncInvoiceItemAmounts($locked, $freight, $tax['gst_rate'], $tax['gst_amount']);

                foreach ($toRemove as $attachment) {
                    $filesToDelete[] = $attachment->stored_path;
                    $attachment->delete();
                }

                $this->storeVoucherAttachments($request, $vouchers, $filesWritten);
            });
        } catch (\Throwable $e) {
            foreach ($filesWritten as $path) Storage::disk('local')->delete($path);
            throw $e;
        }
        foreach ($filesToDelete as $path) Storage::disk('local')->delete($path);

        $invoice = $invoice->fresh();
        $invoice->loadCount('items')->loadSum('payments as paid_amount', 'amount')->load([
            'customer:id,name',
            'salesOrder:id,so_number',
            'attachments:id,invoice_batch_id,original_name,mime_type',
            'items.voucher:id,lr_no,sr_no',
            'items.voucher.attachments:id,voucher_id,original_name,mime_type,file_size',
        ]);

        return response()->json([
            'message' => $invoice->bill_no.' updated successfully.',
            'invoice' => $this->invoicePayload($invoice),
            'print_url' => route('sale.billing.print', $invoice),
        ]);
    }

    public function payments(InvoiceBatch $invoice): JsonResponse
    {
        $invoice->loadSum('payments as paid_amount', 'amount');
        return response()->json([
            'invoice' => $this->invoicePayload($invoice),
            'payments' => $invoice->payments()->orderBy('payment_date')->orderBy('id')->get()->map(fn (CustomerPayment $p) => [
                'id'=>$p->id,'date'=>$p->payment_date?->format('d-m-Y'),'amount'=>(float)$p->amount,
                'mode'=>$p->payment_mode,'reference'=>$p->reference,'remarks'=>$p->remarks,
            ])->values(),
        ]);
    }

    public function storePayment(Request $request, InvoiceBatch $invoice): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => ['nullable','date'],
            'amount' => ['required','numeric','gt:0'],
            'payment_mode' => ['nullable','string','max:50'],
            'reference' => ['nullable','string','max:150'],
            'remarks' => ['nullable','string','max:255'],
        ]);

        return DB::transaction(function () use ($request, $validated, $invoice) {
            $locked = InvoiceBatch::query()->lockForUpdate()->findOrFail($invoice->id);
            $paid = (float) $locked->payments()->sum('amount');
            $balance = max(0, round((float)$locked->total_amount - $paid, 2));
            $amount = round((float)$validated['amount'], 2);
            if ($amount > $balance + 0.004) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed outstanding amount ₹'.number_format($balance,2).'.']);
            }
            $payment = $locked->payments()->create([
                'payment_date'=>$validated['payment_date'] ?? now()->toDateString(),'amount'=>$amount,
                'payment_mode'=>filled($validated['payment_mode']??null)?trim($validated['payment_mode']):null,
                'reference'=>filled($validated['reference']??null)?trim($validated['reference']):null,
                'remarks'=>filled($validated['remarks']??null)?trim($validated['remarks']):null,
                'created_by'=>$request->user()?->id,
            ]);
            return response()->json(['message'=>'Payment saved.','payment_id'=>$payment->id]);
        });
    }

    public function destroyPayment(InvoiceBatch $invoice, CustomerPayment $payment): JsonResponse
    {
        abort_unless($payment->invoice_batch_id === $invoice->id, 404);
        $payment->delete();
        return response()->json(['message'=>'Payment deleted.']);
    }

    public function attachment(InvoiceBatch $invoice, InvoiceAttachment $attachment): BinaryFileResponse
    {
        abort_unless($attachment->invoice_batch_id === $invoice->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->stored_path), 404);
        return response()->file(Storage::disk('local')->path($attachment->stored_path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
        ]);
    }

    public function voucherAttachment(Voucher $voucher, VoucherAttachment $attachment): BinaryFileResponse
    {
        abort_unless($attachment->voucher_id === $voucher->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->stored_path), 404);

        return response()->file(Storage::disk('local')->path($attachment->stored_path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
        ]);
    }

    public function print(InvoiceBatch $invoice): View
    {
        $invoice->load([
            'customer', 'salesOrder',
            'items.voucher.transportName', 'items.voucher.vehicleType', 'items.voucher.supplier',
        ])->loadSum('payments as paid_amount', 'amount');
        $printSettings = PrintSetting::current();
        return view('sale.billing.print', compact('invoice','printSettings'));
    }

    private function taxAmounts(?string $mode, float $freight, float $otherCharges, float $selectedGstRate = 0.0): array
    {
        $mode = in_array($mode, ['rcm', 'hiring', 'gst', 'na'], true) ? $mode : 'rcm';
        if ($mode === 'hiring') {
            $gstRate = 18.0;
        } elseif ($mode === 'gst') {
            $requestedRate = (int) round($selectedGstRate);
            $gstRate = in_array($requestedRate, [5, 12, 18], true) ? (float) $requestedRate : 5.0;
        } else {
            // RCM and NA do not add normal GST to the bill.
            $gstRate = 0.0;
        }
        $gstAmount = round($freight * $gstRate / 100, 2);
        $rcmRate = $mode === 'rcm' ? 5.0 : 0.0;
        $rcmAmount = round($freight * $rcmRate / 100, 2);

        return [
            'mode' => $mode,
            'gst_rate' => $gstRate,
            'gst_amount' => $gstAmount,
            'rcm_rate' => $rcmRate,
            'rcm_amount' => $rcmAmount,
            'total_amount' => round($freight + $gstAmount + $otherCharges, 2),
        ];
    }

    private function syncInvoiceItemAmounts(InvoiceBatch $invoice, float $total, float $gstRate = 0.0, float $gstTotal = 0.0): void
    {
        $items = $invoice->items()->orderBy('id')->lockForUpdate()->get();
        $count = $items->count();
        if ($count === 0) return;

        $baseShare = floor(($total / $count) * 100) / 100;
        $gstShare = floor(($gstTotal / $count) * 100) / 100;
        $allocated = 0.0;
        $allocatedGst = 0.0;
        foreach ($items->values() as $index => $item) {
            $taxable = $index === $count - 1 ? round($total - $allocated, 2) : round($baseShare, 2);
            $gstAmount = $index === $count - 1 ? round($gstTotal - $allocatedGst, 2) : round($gstShare, 2);
            $allocated = round($allocated + $taxable, 2);
            $allocatedGst = round($allocatedGst + $gstAmount, 2);
            $item->update([
                'rate' => $taxable,
                'taxable_amount' => $taxable,
                'gst_rate' => $gstRate,
                'gst_amount' => $gstAmount,
                'line_total' => round($taxable + $gstAmount, 2),
            ]);
        }
    }

    private function uploadedVoucherIds(Request $request)
    {
        return collect(array_keys($request->file('voucher_attachments', [])))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
    }

    private function storeVoucherAttachments(Request $request, $vouchers, array &$filesWritten): void
    {
        foreach ($vouchers as $voucher) {
            foreach ($request->file('voucher_attachments.'.$voucher->id, []) as $file) {
                $extension = strtolower((string) $file->getClientOriginalExtension());
                $name = Str::uuid().($extension !== '' ? '.'.$extension : '');
                $path = $file->storeAs('voucher-attachments/'.$voucher->id, $name, 'local');
                if (! $path) {
                    throw ValidationException::withMessages(['voucher_attachments' => 'An LR attachment could not be stored. Please try again.']);
                }
                $filesWritten[] = $path;
                VoucherAttachment::query()->create([
                    'voucher_id' => $voucher->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize() ?: 0,
                    'created_by' => $request->user()?->id,
                ]);
            }
        }
    }

    private function voucherAttachmentPayload(Voucher $voucher)
    {
        return $voucher->attachments->map(fn (VoucherAttachment $attachment) => [
            'id' => $attachment->id,
            'voucher_id' => $voucher->id,
            'name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'file_size' => (int) $attachment->file_size,
            'url' => route('sale.vouchers.attachments.show', [$voucher, $attachment]),
        ])->values();
    }

    private function invoicePayload(InvoiceBatch $invoice): array
    {
        $invoice->loadMissing([
            'customer:id,name',
            'salesOrder:id,so_number',
            'attachments:id,invoice_batch_id,original_name,mime_type',
            'items.voucher:id,lr_no,sr_no',
            'items.voucher.attachments:id,voucher_id,original_name,mime_type,file_size',
        ]);
        $paid = array_key_exists('paid_amount', $invoice->getAttributes())
            ? (float) ($invoice->paid_amount ?? 0)
            : (float) $invoice->payments()->sum('amount');
        $outstanding = max(0, round((float)$invoice->total_amount - $paid, 2));
        $lrNumbers = $invoice->items
            ->map(function (InvoiceItem $item) {
                if (filled($item->voucher?->lr_no)) return trim((string) $item->voucher->lr_no);
                return $item->voucher ? 'SR '.$item->voucher->sr_no : '';
            })
            ->filter()
            ->unique()
            ->values();

        $lrAttachments = $invoice->items->flatMap(function (InvoiceItem $item) {
            $voucher = $item->voucher;
            if (! $voucher) return collect();
            $lr = filled($voucher->lr_no) ? $voucher->lr_no : 'Voucher '.$voucher->id;

            return $this->voucherAttachmentPayload($voucher)->map(fn ($attachment) => $attachment + [
                'lr_no' => $voucher->lr_no,
                'display_name' => $lr.' · '.$attachment['name'],
                'legacy' => false,
            ]);
        })->values();
        $legacyAttachments = $invoice->attachments->map(fn (InvoiceAttachment $attachment) => [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'display_name' => 'Legacy · '.$attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'url' => route('sale.billing.attachments.show', [$invoice, $attachment]),
            'legacy' => true,
        ])->values();

        return [
            'id'=>$invoice->id,
            'bill_no'=>$invoice->bill_no,
            'invoice_date'=>$invoice->invoice_date?->format('d-m-Y'),
            'invoice_date_raw'=>$invoice->invoice_date?->toDateString(),
            'customer_id'=>$invoice->customer_id,
            'customer_name'=>$invoice->customer?->name,
            'sales_order_id'=>$invoice->sales_order_id,
            'so_number'=>$invoice->salesOrder?->so_number,
            'trip_count'=>(int)($invoice->items_count ?? $invoice->trip_count),
            'voucher_ids'=>$invoice->items->pluck('voucher_id')->map(fn ($id)=>(int)$id)->values(),
            'lr_numbers'=>$lrNumbers,
            'lr_numbers_text'=>$lrNumbers->implode(', '),
            'tax_mode'=>$invoice->tax_mode ?: 'rcm',
            'customer_freight'=>(float)$invoice->customer_freight,
            'gst_rate'=>(float)$invoice->gst_rate,
            'gst_amount'=>(float)$invoice->gst_amount,
            'rcm_rate'=>(float)$invoice->rcm_rate,
            'rcm_amount'=>(float)$invoice->rcm_amount,
            'other_charges'=>(float)$invoice->other_charges,
            'total_amount'=>(float)$invoice->total_amount,
            'paid_amount'=>$paid,
            'outstanding'=>$outstanding,
            'remarks'=>$invoice->remarks,
            'lr_attachments'=>$lrAttachments,
            'legacy_attachments'=>$legacyAttachments,
            'attachments'=>$lrAttachments->map(fn ($attachment) => [
                'id' => $attachment['id'],
                'name' => $attachment['display_name'],
                'mime_type' => $attachment['mime_type'],
                'url' => $attachment['url'],
                'legacy' => false,
            ])->concat($legacyAttachments)->values(),
            'print_url'=>route('sale.billing.print',$invoice),
        ];
    }
}
