<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceBatch extends Model
{
    protected $fillable = [
        'bill_no', 'invoice_date', 'customer_id', 'sales_order_id', 'trip_count', 'customer_freight',
        'gst_rate', 'gst_amount', 'other_charges', 'total_amount', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'trip_count' => 'integer',
            'customer_freight' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function attachments(): HasMany { return $this->hasMany(InvoiceAttachment::class); }
    public function payments(): HasMany { return $this->hasMany(CustomerPayment::class); }
}
