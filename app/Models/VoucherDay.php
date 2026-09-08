<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherDay extends Model
{
    protected $fillable = [
        'day_number',
        'entry_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'entry_date' => 'date',
        ];
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
