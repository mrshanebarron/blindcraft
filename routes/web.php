<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConfiguratorController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;

// Public
Route::get('/', [DashboardController::class, 'index'])->name('home');

// Configurator
Route::get('/configure', [ConfiguratorController::class, 'index'])->name('configurator');
Route::post('/api/calculate', [ConfiguratorController::class, 'calculate'])->name('api.calculate');
Route::get('/api/products/{supplier}', [ConfiguratorController::class, 'products'])->name('api.products');
Route::get('/api/fabrics/{supplier}', [ConfiguratorController::class, 'fabrics'])->name('api.fabrics');
Route::get('/api/controls/{supplier}', [ConfiguratorController::class, 'controls'])->name('api.controls');
Route::get('/api/options/{supplier}', [ConfiguratorController::class, 'options'])->name('api.options');

// Quotes
Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
Route::post('/quotes/{quote}/add-line', [QuoteController::class, 'addLine'])->name('quotes.add-line');
Route::delete('/quotes/{quote}/lines/{lineItem}', [QuoteController::class, 'removeLine'])->name('quotes.remove-line');

// Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/suppliers', [AdminController::class, 'suppliers'])->name('suppliers');
    Route::get('/suppliers/{supplier}', [AdminController::class, 'supplierDetail'])->name('suppliers.show');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::get('/pricing-grids/{product}', [AdminController::class, 'pricingGrid'])->name('pricing-grid');
    Route::get('/rules', [AdminController::class, 'rules'])->name('rules');
});
