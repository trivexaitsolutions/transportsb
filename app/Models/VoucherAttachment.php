<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherAttachment extends Model
{
    protected $fillable = [
        'voucher_id', 'original_name', 'stored_path', 'mime_type', 'file_size', 'created_by',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
