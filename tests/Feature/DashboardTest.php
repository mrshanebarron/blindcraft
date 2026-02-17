<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_homepage_shows_supplier_count(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->get('/')->assertStatus(200)->assertSee('Test Supplier');
    }
}
