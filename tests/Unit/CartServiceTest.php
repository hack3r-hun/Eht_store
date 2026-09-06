<?php

namespace Tests\Unit;

use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtotal_calculates_correctly(): void
    {
        $product = $this->createTestProduct([
            'name' => 'Capacitor',
            'slug' => 'cap',
            'sku' => 'C-001',
            'price' => 100,
            'stock_quantity' => 10,
        ]);
        $variant = $this->defaultVariant($product);

        $cart = app(CartService::class);
        $cart->add($product, $variant, 3);

        $this->assertEquals(300.0, $cart->subtotal());
        $this->assertEquals(3, $cart->count());
    }
}
