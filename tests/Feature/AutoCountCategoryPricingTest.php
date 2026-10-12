<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceDetailController;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the AutoCount product-type pricing model that replaced special_prices:
 * product types double as the pricing category. Products push a price per type,
 * customers push their type, and a line price resolves customer's type ->
 * product's price for that type -> base price. Types are created by name on the
 * fly during sync.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) so the configured dev DB is
 * rolled back, never rebuilt.
 */
class AutoCountCategoryPricingTest extends TestCase
{
    use DatabaseTransactions;

    private function headers(): array
    {
        return ['X-AutoCount-Token' => (string) config('services.autocount.token')];
    }

    /** @test */
    public function endpoints_reject_a_bad_token()
    {
        $this->postJson('/api/v1/autocount/products', ['products' => []], [
            'X-AutoCount-Token' => 'wrong-token',
        ])->assertStatus(403);
    }

    /** @test */
    public function product_sync_creates_types_writes_prices_and_prunes()
    {
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'ITM-1', 'name' => 'Item 1', 'price' => 10,
                'prices' => [
                    ['product_type' => 'Price 1', 'price' => 8],
                    ['product_type' => 'Price 2', 'price' => 9],
                ],
            ]],
        ], $this->headers())->assertOk()->assertJson(['result' => true]);

        // Types are auto-created by name.
        $this->assertDatabaseHas('product_types', ['name' => 'Price 1']);
        $this->assertDatabaseHas('product_types', ['name' => 'Price 2']);

        $product = Product::where('code', 'ITM-1')->firstOrFail();
        $t1 = ProductType::where('name', 'Price 1')->value('id');
        $t2 = ProductType::where('name', 'Price 2')->value('id');

        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'product_type_id' => $t1, 'price' => 8]);
        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'product_type_id' => $t2, 'price' => 9]);

        // Re-sync with only "Price 1" (new amount) -> "Price 2" pruned, "Price 1" updated.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'ITM-1', 'name' => 'Item 1', 'price' => 10,
                'prices' => [['product_type' => 'Price 1', 'price' => 7]],
            ]],
        ], $this->headers())->assertOk();

        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'product_type_id' => $t1, 'price' => 7]);
        $this->assertDatabaseMissing('product_prices', ['product_id' => $product->id, 'product_type_id' => $t2]);
        $this->assertSame(1, ProductPrice::where('product_id', $product->id)->count());
    }

    /** @test */
    public function product_sync_without_prices_key_leaves_type_prices_untouched()
    {
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'ITM-2', 'name' => 'Item 2', 'price' => 10,
                'prices' => [['product_type' => 'Price 1', 'price' => 8]],
            ]],
        ], $this->headers())->assertOk();
        $product = Product::where('code', 'ITM-2')->firstOrFail();

        // A plain product re-sync (no 'prices' key) must not wipe type prices.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [['code' => 'ITM-2', 'name' => 'Item 2 renamed', 'price' => 12]],
        ], $this->headers())->assertOk();

        $this->assertSame(1, ProductPrice::where('product_id', $product->id)->count());
    }

    /** @test */
    public function customer_sync_sets_creates_type_and_clears()
    {
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'CUS-1', 'company' => 'Acme', 'product_type' => 'Dealer']],
        ], $this->headers())->assertOk();

        $this->assertDatabaseHas('product_types', ['name' => 'Dealer']);
        $dealer = ProductType::where('name', 'Dealer')->value('id');
        $this->assertSame($dealer, Customer::where('code', 'CUS-1')->value('product_type_id'));

        // Blank clears the assignment (back to base-price fallback).
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'CUS-1', 'company' => 'Acme', 'product_type' => '']],
        ], $this->headers())->assertOk();
        $this->assertNull(Customer::where('code', 'CUS-1')->value('product_type_id'));
    }

    /** @test */
    public function getprice_uses_type_price_then_falls_back_to_base()
    {
        $dealer = ProductType::create(['name' => 'Dealer', 'status' => 1]);
        $retail = ProductType::create(['name' => 'Retail', 'status' => 1]);
        $product = Product::create(['code' => 'ITM-9', 'name' => 'Item 9', 'price' => 10, 'status' => 1]);
        ProductPrice::create(['product_id' => $product->id, 'product_type_id' => $dealer->id, 'price' => 8]);

        $controller = app(InvoiceDetailController::class);

        // Customer tied to Dealer -> dealer price (8).
        $onDealer = $this->makeInvoice($this->makeCustomer('C-DEALER', $dealer->id)->id);
        $this->assertEquals(8, $this->price($controller->getprice($onDealer, $product->id)));

        // Customer with no type -> base price (10).
        $noType = $this->makeInvoice($this->makeCustomer('C-NONE', null)->id);
        $this->assertEquals(10, $this->price($controller->getprice($noType, $product->id)));

        // Customer on Retail, which has no price for this product -> base (10).
        $onRetail = $this->makeInvoice($this->makeCustomer('C-RETAIL', $retail->id)->id);
        $this->assertEquals(10, $this->price($controller->getprice($onRetail, $product->id)));
    }

    private function price($response)
    {
        return json_decode($response->getContent(), true)['data'];
    }

    private function makeCustomer(string $code, $productTypeId): Customer
    {
        $customer = new Customer();
        $customer->code = $code;
        $customer->company = $code;
        $customer->name = $code;            // legacy NOT NULL, not fillable
        $customer->paymentterm = 2;
        $customer->agent_id = 0;
        $customer->supervisor_id = 0;
        $customer->status = 1;
        $customer->product_type_id = $productTypeId;
        $customer->save();

        return $customer;
    }

    private function makeInvoice(int $customerId): int
    {
        return DB::table('invoices')->insertGetId([
            'agent_id' => 0,
            'customer_id' => $customerId,
            'date' => now(),
            'driver_id' => 0,
            'invoiceno' => 'INV-' . $customerId . '-' . uniqid(),
            'kelindan_id' => 0,
            'remark' => '',
            'status' => 1,
            'supervisor_id' => 0,
        ]);
    }
}
