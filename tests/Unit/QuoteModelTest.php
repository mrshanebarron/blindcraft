<?php

namespace Tests\Unit;

use App\Models\{Quote, QuoteLineItem};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_number_format(): void
    {
        $number = Quote::generateNumber();

        $this->assertStringStartsWith('Q-' . date('Ym') . '-', $number);
        $this->assertMatchesRegularExpression('/Q-\d{6}-\d{4}/', $number);
    }

    public function test_generate_number_increments(): void
    {
        $first = Quote::generateNumber();
        Quote::create([
            'quote_number' => $first,
            'status' => 'draft',
        ]);

        $second = Quote::generateNumber();
        $this->assertNotEquals($first, $second);

        $firstSeq = (int) substr($first, -4);
        $secondSeq = (int) substr($second, -4);
        $this->assertEquals($firstSeq + 1, $secondSeq);
    }

    public function test_recalculate_totals(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'rounded_width' => 36,
            'rounded_height' => 48,
            'quantity' => 1,
            'mount_type' => 'inside',
            'grid_cost' => 100,
            'unit_cost' => 100,
            'markup_pct' => 50,
            'unit_sell' => 150,
            'line_cost' => 100,
            'line_sell' => 150,
            'line_margin' => 50,
        ]);

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 48,
            'height' => 60,
            'rounded_width' => 48,
            'rounded_height' => 60,
            'quantity' => 2,
            'mount_type' => 'outside',
            'grid_cost' => 120,
            'unit_cost' => 120,
            'markup_pct' => 50,
            'unit_sell' => 180,
            'line_cost' => 240,
            'line_sell' => 360,
            'line_margin' => 120,
        ]);

        $quote->recalculateTotals();

        $this->assertEquals(340.00, $quote->total_cost);   // 100 + 240
        $this->assertEquals(510.00, $quote->total_sell);    // 150 + 360
        $this->assertEquals(170.00, $quote->total_margin);  // 510 - 340
    }

    public function test_recalculate_totals_empty_quote(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        $quote->recalculateTotals();

        $this->assertEquals(0, $quote->total_cost);
        $this->assertEquals(0, $quote->total_sell);
        $this->assertEquals(0, $quote->total_margin);
    }

    public function test_valid_until_cast_to_datetime(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $quote->valid_until);
    }

    public function test_line_items_relationship(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        $this->assertCount(0, $quote->lineItems);

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'rounded_width' => 36,
            'rounded_height' => 48,
            'quantity' => 1,
            'mount_type' => 'inside',
            'grid_cost' => 100,
            'unit_cost' => 100,
            'markup_pct' => 50,
            'unit_sell' => 150,
            'line_cost' => 100,
            'line_sell' => 150,
            'line_margin' => 50,
        ]);

        $this->assertCount(1, $quote->fresh()->lineItems);
    }
}
