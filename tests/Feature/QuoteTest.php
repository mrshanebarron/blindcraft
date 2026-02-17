<?php

namespace Tests\Feature;

use App\Models\{Quote, QuoteLineItem};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    // ── Quote List ─────────────────────────────────────────────────

    public function test_quotes_index_loads(): void
    {
        $this->get('/quotes')->assertStatus(200);
    }

    public function test_quotes_index_shows_existing_quotes(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote(['customer_name' => 'Jane Smith']);

        $this->get('/quotes')
            ->assertStatus(200)
            ->assertSee('Jane Smith');
    }

    // ── Create Quote ───────────────────────────────────────────────

    public function test_create_quote(): void
    {
        $this->createFullSupplierSetup();

        $response = $this->post('/quotes', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '555-1234',
            'project_name' => 'Kitchen Blinds',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quotes', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'project_name' => 'Kitchen Blinds',
            'status' => 'draft',
        ]);
    }

    public function test_quote_number_auto_generated(): void
    {
        $this->createFullSupplierSetup();

        $this->post('/quotes', ['customer_name' => 'Test']);

        $quote = Quote::first();
        $this->assertNotNull($quote->quote_number);
        $this->assertStringStartsWith('Q-', $quote->quote_number);
    }

    public function test_quote_numbers_increment(): void
    {
        $this->createFullSupplierSetup();

        $this->post('/quotes', ['customer_name' => 'First']);
        $this->post('/quotes', ['customer_name' => 'Second']);

        $quotes = Quote::orderBy('id')->get();
        $this->assertNotEquals($quotes[0]->quote_number, $quotes[1]->quote_number);
    }

    public function test_quote_valid_until_set(): void
    {
        $this->createFullSupplierSetup();

        $this->post('/quotes', ['customer_name' => 'Test']);

        $quote = Quote::first();
        $this->assertNotNull($quote->valid_until);
        $this->assertTrue($quote->valid_until->isFuture());
    }

    // ── Show Quote ─────────────────────────────────────────────────

    public function test_quote_show_page_loads(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        $this->get("/quotes/{$quote->id}")
            ->assertStatus(200)
            ->assertSee($quote->quote_number);
    }

    public function test_quote_show_404_for_missing(): void
    {
        $this->get('/quotes/999')->assertStatus(404);
    }

    // ── Add Line Item ──────────────────────────────────────────────

    public function test_add_line_item_to_quote(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $quote = $this->createQuote();

        $response = $this->post("/quotes/{$quote->id}/add-line", [
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'quantity' => 1,
            'markup_pct' => 50,
            'room_name' => 'Living Room',
            'window_name' => 'North Window',
            'mount_type' => 'inside',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('quote_line_items', 1);

        $line = QuoteLineItem::first();
        $this->assertEquals($quote->id, $line->quote_id);
        $this->assertEquals('Living Room', $line->room_name);
        $this->assertEquals('inside', $line->mount_type);
        $this->assertGreaterThan(0, $line->unit_cost);
    }

    public function test_add_line_recalculates_quote_totals(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $quote = $this->createQuote();

        $this->post("/quotes/{$quote->id}/add-line", [
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'quantity' => 1,
            'markup_pct' => 50,
        ]);

        $quote->refresh();
        $this->assertGreaterThan(0, $quote->total_cost);
        $this->assertGreaterThan(0, $quote->total_sell);
        $this->assertGreaterThan(0, $quote->total_margin);
    }

    public function test_add_line_validates_required_fields(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        $this->post("/quotes/{$quote->id}/add-line", [])
            ->assertSessionHasErrors(['product_id', 'fabric_id', 'control_type_id', 'width', 'height', 'quantity']);
    }

    public function test_add_line_fails_with_incompatible_config(): void
    {
        $this->createFullSupplierSetup();
        // No pricing grid → engine returns failure
        $quote = $this->createQuote();

        $this->post("/quotes/{$quote->id}/add-line", [
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('config');

        $this->assertDatabaseCount('quote_line_items', 0);
    }

    public function test_add_multiple_lines_to_same_quote(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $quote = $this->createQuote();

        $lineData = [
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'quantity' => 1,
            'markup_pct' => 50,
        ];

        $this->post("/quotes/{$quote->id}/add-line", $lineData);
        $this->post("/quotes/{$quote->id}/add-line", $lineData);

        $this->assertDatabaseCount('quote_line_items', 2);

        $quote->refresh();
        $singleLineCost = QuoteLineItem::first()->line_cost;
        $this->assertEquals($singleLineCost * 2, $quote->total_cost);
    }

    // ── Remove Line Item ───────────────────────────────────────────

    public function test_remove_line_item(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $quote = $this->createQuote();

        $this->post("/quotes/{$quote->id}/add-line", [
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'quantity' => 1,
            'markup_pct' => 50,
        ]);

        $line = QuoteLineItem::first();

        $this->delete("/quotes/{$quote->id}/lines/{$line->id}")
            ->assertRedirect();

        $this->assertDatabaseCount('quote_line_items', 0);
    }

    public function test_remove_line_recalculates_totals(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $quote = $this->createQuote();

        $lineData = [
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'quantity' => 1,
            'markup_pct' => 50,
        ];

        $this->post("/quotes/{$quote->id}/add-line", $lineData);
        $this->post("/quotes/{$quote->id}/add-line", $lineData);

        $quote->refresh();
        $totalBefore = $quote->total_cost;

        $line = QuoteLineItem::first();
        $this->delete("/quotes/{$quote->id}/lines/{$line->id}");

        $quote->refresh();
        $this->assertLessThan($totalBefore, $quote->total_cost);
    }
}
