<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $fillable = [
        'transport_name_id',
        'name',
        'account_holder_name',
        'account_number',
        'account_type',
        'ifsc_code',
        'branch_name',
        'bank_address',
        'opening_balance',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transportName(): BelongsTo
    {
        return $this->belongsTo(TransportName::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}
