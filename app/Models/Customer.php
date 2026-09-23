<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model
{
    protected $fillable = ['code','name','contact_person','phone','email','gst_no','pan_no','address','opening_balance','business_type','is_government_employee','bill_note','is_active'];
    protected function casts(): array { return ['opening_balance'=>'decimal:2','is_government_employee'=>'boolean','is_active'=>'boolean']; }
    public function invoiceBatches(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(InvoiceBatch::class); }
}
