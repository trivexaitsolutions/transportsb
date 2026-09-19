<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'sr_no', 'lr_date', 'sales_order_id', 'transport_name_id', 'lr_no', 'vehicle_type_id',
        'lorry_number', 'supplier_id', 'supplier_freight', 'advance_paid', 'hamali_loading',
        'hamali_unloading', 'other_charges', 'remarks', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'lr_date' => 'date',
            'supplier_freight' => 'decimal:2',
            'advance_paid' => 'decimal:2',
            'hamali_loading' => 'decimal:2',
            'hamali_unloading' => 'decimal:2',
            'other_charges' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo { return $this->belongsTo(SalesOrder::class); }
    public function transportName(): BelongsTo { return $this->belongsTo(TransportName::class); }
    public function vehicleType(): BelongsTo { return $this->belongsTo(VehicleType::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function supplierPayments(): HasMany { return $this->hasMany(SupplierPayment::class); }
    public function attachments(): HasMany { return $this->hasMany(VoucherAttachment::class); }
    public function invoiceItem(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(InvoiceItem::class); }
}
