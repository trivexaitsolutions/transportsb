<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_batch_id', 'voucher_id', 'rate', 'taxable_amount', 'gst_rate', 'gst_amount', 'line_total'];

    protected function casts(): array
    {
        return ['rate'=>'decimal:2','taxable_amount'=>'decimal:2','gst_rate'=>'decimal:2','gst_amount'=>'decimal:2','line_total'=>'decimal:2'];
    }

    public function invoiceBatch(): BelongsTo { return $this->belongsTo(InvoiceBatch::class); }
    public function voucher(): BelongsTo { return $this->belongsTo(Voucher::class); }
}
