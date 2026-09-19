<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcknowledgementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_code_is_generated_and_sales_order_number_is_uppercased(): void
    {
        $this->actingAs(User::factory()->create());
        Customer::query()->create(['code' => 'CUST010', 'name' => 'Existing', 'is_active' => true]);

        $this->post(route('masters.store', 'customers'), [
            'name' => 'Generated Customer',
            'is_active' => '1',
        ])->assertRedirect();

        $customer = Customer::query()->where('name', 'Generated Customer')->firstOrFail();
        $this->assertSame('CUST011', $customer->code);

        $this->post(route('sale.orders.store'), [
            'so_number' => ' so-25/11 ',
            'so_date' => '2026-09-19',
            'customer_id' => $customer->id,
            'from_location' => 'Mumbai',
            'to_location' => 'Pune',
            'description' => 'Transport',
            'trips_quantity' => 2,
            'per_trip_cost' => 5000,
            'other_charges' => 0,
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('sales_orders', ['so_number' => 'SO-25/11', 'tax_mode' => 'rcm', 'gst_amount' => 0]);
    }

    public function test_voucher_lorry_number_is_uppercased_and_other_charges_are_saved(): void
    {
        $this->actingAs(User::factory()->create());
        [$customer, $order] = $this->customerAndOrder();

        $this->postJson(route('sale.vouchers.save-all'), [
            'from_date' => '2026-09-19',
            'to_date' => '2026-09-19',
            'rows' => [[
                'lr_date' => '2026-09-19',
                'sales_order_id' => $order->id,
                'lorry_number' => 'mh05ab1234',
                'other_charges' => 450.25,
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('vouchers', [
            'sales_order_id' => $order->id,
            'lorry_number' => 'MH05AB1234',
            'other_charges' => 450.25,
        ]);
    }

    public function test_each_selected_lr_requires_and_stores_its_own_attachment(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        [$customer, $order] = $this->customerAndOrder();
        $first = $this->voucher($order, 1, 'LR101', 100);
        $second = $this->voucher($order, 2, 'LR102', 200);

        $payload = [
            'customer_id' => $customer->id,
            'sales_order_id' => $order->id,
            'voucher_ids' => [$first->id, $second->id],
            'invoice_date' => '2026-09-19',
            'customer_freight' => 10000,
            'remarks' => 'Received',
        ];

        $this->postJson(route('sale.billing.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_attachments');

        $payload['voucher_attachments'] = [
            $first->id => [UploadedFile::fake()->create('lr101.pdf', 10, 'application/pdf')],
            $second->id => [UploadedFile::fake()->create('lr102.jpg', 10, 'image/jpeg')],
        ];

        $response = $this->post(route('sale.billing.store'), $payload, ['Accept' => 'application/json'])
            ->assertOk();

        $invoiceId = $response->json('invoice.id');
        $this->assertDatabaseCount('voucher_attachments', 2);
        $this->assertDatabaseHas('voucher_attachments', ['voucher_id' => $first->id, 'original_name' => 'lr101.pdf']);
        $this->assertDatabaseHas('voucher_attachments', ['voucher_id' => $second->id, 'original_name' => 'lr102.jpg']);
        $this->assertDatabaseHas('invoice_batches', ['id' => $invoiceId, 'tax_mode' => 'rcm', 'customer_freight' => 10000, 'gst_amount' => 0, 'rcm_amount' => 500, 'other_charges' => 300, 'total_amount' => 10300]);

        $data = $this->getJson(route('sale.billing.data', [
            'customer_id' => $customer->id,
            'sales_order_id' => $order->id,
        ]))->assertOk();

        $this->assertSame('LR101, LR102', $data->json('invoices.0.lr_numbers_text'));
        $this->assertCount(2, $data->json('invoices.0.attachments'));

        $firstAttachmentId = $first->attachments()->value('id');
        $this->post(route('sale.billing.update', $invoiceId), [
            '_method' => 'PUT',
            'invoice_date' => '2026-09-20',
            'customer_freight' => 9500,
            'remove_voucher_attachment_ids' => [$firstAttachmentId],
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('voucher_attachments');

        $this->post(route('sale.billing.update', $invoiceId), [
            '_method' => 'PUT',
            'invoice_date' => '2026-09-20',
            'customer_freight' => 9500,
            'remove_voucher_attachment_ids' => [$firstAttachmentId],
            'voucher_attachments' => [
                $first->id => [UploadedFile::fake()->create('lr101-replacement.pdf', 10, 'application/pdf')],
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertDatabaseCount('voucher_attachments', 2);
        $this->assertDatabaseHas('invoice_batches', ['id' => $invoiceId, 'customer_freight' => 9500, 'gst_amount' => 0, 'rcm_amount' => 475, 'other_charges' => 300, 'total_amount' => 9800]);

        $this->postJson(route('sale.vouchers.save-all'), [
            'from_date' => '2026-09-19',
            'to_date' => '2026-09-19',
            'rows' => [[
                'id' => $first->id,
                'lr_date' => '2026-09-19',
                'sales_order_id' => $order->id,
                'lr_no' => 'CHANGED-LR',
                'other_charges' => 100,
            ]],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('vouchers', ['id' => $first->id, 'lr_no' => 'LR101']);
    }

    public function test_hiring_mode_charges_18_percent_gst_in_invoice_total(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        [$customer, $order] = $this->customerAndOrder('hiring');
        $voucher = $this->voucher($order, 1, 'LR201', 250);

        $response = $this->post(route('sale.billing.store'), [
            'customer_id' => $customer->id,
            'sales_order_id' => $order->id,
            'voucher_ids' => [$voucher->id],
            'invoice_date' => '2026-09-19',
            'customer_freight' => 10000,
            'voucher_attachments' => [
                $voucher->id => [UploadedFile::fake()->create('lr201.pdf', 10, 'application/pdf')],
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertDatabaseHas('invoice_batches', [
            'id' => $response->json('invoice.id'),
            'tax_mode' => 'hiring',
            'customer_freight' => 10000,
            'gst_rate' => 18,
            'gst_amount' => 1800,
            'rcm_amount' => 0,
            'other_charges' => 250,
            'total_amount' => 12050,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'voucher_id' => $voucher->id,
            'taxable_amount' => 10000,
            'gst_rate' => 18,
            'gst_amount' => 1800,
            'line_total' => 11800,
        ]);
    }

    private function customerAndOrder(string $taxMode = 'rcm'): array
    {
        $customer = Customer::query()->create([
            'code' => 'CUST001',
            'name' => 'Test Customer',
            'gst_no' => $taxMode === 'hiring' ? '27ABCDE1234F1Z5' : null,
            'is_active' => true,
        ]);
        $gstRate = $taxMode === 'hiring' ? 18 : 0;
        $gstAmount = round(25000 * $gstRate / 100, 2);
        $order = SalesOrder::query()->create([
            'so_number' => 'SO-001',
            'so_date' => '2026-09-19',
            'customer_id' => $customer->id,
            'from_location' => 'Mumbai',
            'to_location' => 'Pune',
            'description' => 'Transport',
            'trips_quantity' => 5,
            'per_trip_cost' => 5000,
            'value' => 25000,
            'tax_mode' => $taxMode,
            'gst_rate' => $gstRate,
            'gst_amount' => $gstAmount,
            'other_charges' => 0,
            'total_amount' => 25000 + $gstAmount,
            'is_active' => true,
        ]);

        return [$customer, $order];
    }

    private function voucher(SalesOrder $order, int $serial, string $lrNumber, float $otherCharges): Voucher
    {
        return Voucher::query()->create([
            'sr_no' => $serial,
            'lr_date' => '2026-09-19',
            'sales_order_id' => $order->id,
            'lr_no' => $lrNumber,
            'other_charges' => $otherCharges,
        ]);
    }
}
