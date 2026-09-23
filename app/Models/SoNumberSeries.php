<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoNumberSeries extends Model
{
    protected $table = 'so_number_series';

    protected $fillable = [
        'series_type',
        'prefix',
        'start_number',
        'next_number',
        'number_digits',
        'suffix',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_number' => 'integer',
            'next_number' => 'integer',
            'number_digits' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->series_type === 'gst' ? 'GST Registered' : 'Non-GST';
    }

    public function formatNumber(?int $number = null): string
    {
        $number ??= (int) $this->next_number;
        $padded = str_pad((string) $number, max(1, (int) $this->number_digits), '0', STR_PAD_LEFT);

        return (string) $this->prefix.$padded.(string) ($this->suffix ?? '');
    }
}
