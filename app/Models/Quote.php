<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_until' => 'datetime',
    ];

    public function lineItems(): HasMany { return $this->hasMany(QuoteLineItem::class); }

    public function recalculateTotals(): void
    {
        $this->total_cost = $this->lineItems->sum('line_cost');
        $this->total_sell = $this->lineItems->sum('line_sell');
        $this->total_margin = $this->total_sell - $this->total_cost;
        $this->save();
    }

    public static function generateNumber(): string
    {
        $prefix = 'Q-' . date('Ym');
        $last = static::where('quote_number', 'like', $prefix . '%')->max('quote_number');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
