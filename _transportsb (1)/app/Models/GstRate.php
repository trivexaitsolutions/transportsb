<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GstRate extends Model
{
    protected $fillable = ['name','rate','is_active','is_default'];
    protected function casts(): array { return ['rate'=>'decimal:2','is_active'=>'boolean','is_default'=>'boolean']; }
}
