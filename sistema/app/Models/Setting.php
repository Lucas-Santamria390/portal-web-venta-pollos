<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['price_per_lb', 'auto_price'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_lb' => 'decimal:2',
            'auto_price' => 'boolean',
        ];
    }

    /**
     * Get the single settings row, creating it with defaults when missing.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'price_per_lb' => 0,
            'auto_price' => true,
        ]);
    }
}
