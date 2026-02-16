<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingGrid extends Model
{
    protected $guarded = [];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }

    public function scopeForDimensions($q, float $width, float $height)
    {
        return $q->where('width_min', '<=', $width)
                 ->where('width_max', '>=', $width)
                 ->where('height_min', '<=', $height)
                 ->where('height_max', '>=', $height);
    }

    public function scopeForPriceGroup($q, string $group)
    {
        return $q->where('price_group', $group);
    }
}
