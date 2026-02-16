<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLineItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'selected_options' => 'array',
        'pricing_breakdown' => 'array',
    ];

    public function quote(): BelongsTo { return $this->belongsTo(Quote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function fabric(): BelongsTo { return $this->belongsTo(Fabric::class); }
    public function controlType(): BelongsTo { return $this->belongsTo(ControlType::class); }
}
