<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model
{
    protected $fillable = ['code','name','contact_person','phone','email','gst_no','address','bank_name','bank_account','ifsc','opening_balance','is_active'];
    protected function casts(): array { return ['opening_balance'=>'decimal:2','is_active'=>'boolean']; }
}
