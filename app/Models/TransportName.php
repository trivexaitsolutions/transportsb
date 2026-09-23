<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportName extends Model
{
    protected $fillable = ['name', 'gst_no', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class);
    }
}
