<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAttachment extends Model
{
    protected $fillable = ['invoice_batch_id', 'original_name', 'stored_path', 'mime_type', 'file_size', 'created_by'];
    public function invoiceBatch(): BelongsTo { return $this->belongsTo(InvoiceBatch::class); }
}
