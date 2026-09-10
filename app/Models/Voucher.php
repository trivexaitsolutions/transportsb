<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'sr_no', 'voucher_day_id', 'transport_company_id', 'lr_date', 'lr_no', 'vehicle_type_id',
        'lorry_no', 'so_ref_no', 'from_place', 'to_place', 'supplier_id',
        'supplier_freight', 'supplier_advance', 'customer_id', 'customer_freight',
        'hamali_loading', 'hamali_unloading', 'other_charges', 'bill_no', 'gst_rate_id', 'gst', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'lr_date' => 'date',
            'supplier_freight' => 'decimal:2',
            'supplier_advance' => 'decimal:2',
            'customer_freight' => 'decimal:2',
            'hamali_loading' => 'decimal:2',
            'hamali_unloading' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'gst' => 'decimal:2',
        ];
    }


    public function day(): BelongsTo
    {
        return $this->belongsTo(VoucherDay::class, 'voucher_day_id');
    }

    public function transportCompany(): BelongsTo
    {
        return $this->belongsTo(TransportCompany::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function gstRate(): BelongsTo
    {
        return $this->belongsTo(GstRate::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
