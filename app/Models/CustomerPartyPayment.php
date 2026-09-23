<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPartyPayment extends Model
{
    protected $fillable = [
        'customer_id',
        'payment_type',
        'invoice_batch_id',
        'payment_date',
        'amount',
        'tds_percent',
        'tds_amount',
        'net_amount',
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
            'payment_type' => 'string',
            'cheque_cleared_date' => 'date',
            'amount' => 'decimal:2',
            'tds_percent' => 'decimal:2',
            'tds_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'attachment_size' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoiceBatch(): BelongsTo
    {
        return $this->belongsTo(InvoiceBatch::class);
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
