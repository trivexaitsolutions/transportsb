<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PrintSetting extends Model
{
    protected $fillable = ['letterhead_image','letterhead_top_margin_mm'];
    protected function casts(): array { return ['letterhead_top_margin_mm'=>'decimal:2']; }
    public static function current(): self { return static::query()->firstOrCreate([], ['letterhead_top_margin_mm'=>0]); }
}
