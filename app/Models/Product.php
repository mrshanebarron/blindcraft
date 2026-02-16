<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function pricingGrids(): HasMany { return $this->hasMany(PricingGrid::class); }
    public function roundingRules(): HasMany { return $this->hasMany(RoundingRule::class); }
    public function compatibilityRules(): HasMany { return $this->hasMany(CompatibilityRule::class); }
    public function surcharges(): HasMany { return $this->hasMany(Surcharge::class); }

    public function scopeActive($q) { return $q->where('active', true); }

    public function validateDimensions(float $width, float $height): array
    {
        $errors = [];
        if ($width < $this->min_width) $errors[] = "Width must be at least {$this->min_width}\"";
        if ($width > $this->max_width) $errors[] = "Width cannot exceed {$this->max_width}\"";
        if ($height < $this->min_height) $errors[] = "Height must be at least {$this->min_height}\"";
        if ($height > $this->max_height) $errors[] = "Height cannot exceed {$this->max_height}\"";
        return $errors;
    }
}
