<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPartyPayment extends Model
{
    protected $fillable = [
        'supplier_id',
        'payment_type',
        'voucher_id',
        'payment_date',
        'amount',
        'payment_mode',
        'reference',
        'remarks',
        'attachment_name',
        'attachment_path',
        'attachment_mime',
        'attachment_size',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'attachment_size' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
