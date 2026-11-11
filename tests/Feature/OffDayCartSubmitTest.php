<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers Portal\CartController::submit() — the customer-portal checkout that
 * turns a cart into a SalesOrder. The behaviour under test is the new "off day"
 * flag: the created order's off_day must reflect $request->boolean('off_day').
 *
 * Authenticates on the dedicated 'customer' session guard (auth:customer).
 * Uses DatabaseTransactions so the configured dev DB (`snoodle`) is rolled back
 * after each test, never rebuilt.
 */
class OffDayCartSubmitTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'code'        => 'OFFDAY-CART-' . uniqid(),
            'company'     => 'Off Day Cart Customer',
            'paymentterm' => 0,
            'status'      => 1,
        ]);
    }

    private function makeActiveProduct(): Product
    {
        return Product::create([
            'code'   => 'OFFDAY-PROD-' . uniqid(),
            'name'   => 'Off Day Test Product',
            'price'  => 10.00,
            'status' => 1, // active — submit() skips inactive products
        ]);
    }

    private function addToCart(Customer $customer, Product $product, int $qty = 2): void
    {
        CartItem::create([
            'customer_id' => $customer->id,
            'product_id'  => $product->id,
            'quantity'    => $qty,
        ]);
    }

    /** @test */
    public function submitting_with_off_day_true_creates_an_off_day_order(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeActiveProduct();
        $this->addToCart($customer, $product);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('portal.cart.submit'), ['off_day' => true]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $order = SalesOrder::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order, 'a sales order should have been created');
        $this->assertTrue($order->off_day, 'off_day=true in request must set off_day on the order');
    }

    /** @test */
    public function submitting_with_off_day_false_creates_a_normal_order(): void
    {
        $customer = $this->makeCustomer();
        $product  = $this->makeActiveProduct();
        $this->addToCart($customer, $product);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('portal.cart.submit'), ['off_day' => false]);

        $response->assertOk();

        $order = SalesOrder::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertFalse($order->off_day);
    }

    /** @test */
    public function submitting_without_off_day_defaults_to_a_normal_order(): void
    {
        // Boundary: an omitted off_day key must coerce to false (request->boolean).
        $customer = $this->makeCustomer();
        $product  = $this->makeActiveProduct();
        $this->addToCart($customer, $product);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('portal.cart.submit'), []);

        $response->assertOk();

        $order = SalesOrder::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertFalse($order->off_day, 'omitted off_day must default to false');
    }
}
