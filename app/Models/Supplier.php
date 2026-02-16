<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $guarded = [];

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function fabrics(): HasMany { return $this->hasMany(Fabric::class); }
    public function controlTypes(): HasMany { return $this->hasMany(ControlType::class); }
    public function productOptions(): HasMany { return $this->hasMany(ProductOption::class); }
    public function roundingRules(): HasMany { return $this->hasMany(RoundingRule::class); }
    public function compatibilityRules(): HasMany { return $this->hasMany(CompatibilityRule::class); }
    public function surcharges(): HasMany { return $this->hasMany(Surcharge::class); }

    public function scopeActive($q) { return $q->where('active', true); }
}
