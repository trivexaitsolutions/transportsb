<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPartyPayment extends Model
{
    protected $fillable = [
        'customer_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
