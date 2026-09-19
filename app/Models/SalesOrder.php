<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'so_number', 'so_date', 'customer_id', 'from_location', 'to_location', 'description',
        'trips_quantity', 'per_trip_cost', 'value', 'gst_rate_id', 'tax_mode', 'gst_rate', 'gst_amount',
        'other_charges', 'total_amount', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'so_date' => 'date',
            'trips_quantity' => 'integer',
            'per_trip_cost' => 'decimal:2',
            'value' => 'decimal:2',
            'tax_mode' => 'string',
            'gst_rate' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function gstRate(): BelongsTo { return $this->belongsTo(GstRate::class); }
    public function vouchers(): HasMany { return $this->hasMany(Voucher::class); }
    public function invoiceBatches(): HasMany { return $this->hasMany(InvoiceBatch::class); }
}
