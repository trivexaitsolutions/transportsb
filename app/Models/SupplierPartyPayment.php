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
        'bank_id',
        'cheque_status',
        'cheque_cleared_date',
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
            'cheque_cleared_date' => 'date',
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

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function scopePosted($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('payment_mode')
                ->orWhereRaw('LOWER(payment_mode) <> ?', ['cheque'])
                ->orWhere('cheque_status', 'cleared');
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
