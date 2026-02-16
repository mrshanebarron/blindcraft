<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    protected $guarded = [];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }

    public function scopeActive($q) { return $q->where('active', true); }
}
