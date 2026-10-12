<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceDetailController;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPriceCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the AutoCount product-price-category pricing model. A price category is
 * a customer pricing tier, distinct from a product's type/classification.
 * Products push a price per category, customers push their category, and a line
 * price resolves customer's category -> product's price for that category ->
 * base price. Categories are created by name on the fly during sync.
 *
 * The plugin's wire field was renamed product_type -> product_price_category;
 * the endpoints still accept the legacy name as a fallback (see back-compat
 * tests) so a not-yet-rebuilt plugin keeps syncing.
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
    public function product_sync_creates_categories_writes_prices_and_prunes()
    {
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'ITM-1', 'name' => 'Item 1', 'price' => 10,
                'prices' => [
                    ['product_price_category' => 'Price 1', 'price' => 8],
                    ['product_price_category' => 'Price 2', 'price' => 9],
                ],
            ]],
        ], $this->headers())->assertOk()->assertJson(['result' => true]);

        // Categories are auto-created by name.
        $this->assertDatabaseHas('product_price_categories', ['name' => 'Price 1']);
        $this->assertDatabaseHas('product_price_categories', ['name' => 'Price 2']);

        $product = Product::where('code', 'ITM-1')->firstOrFail();
        $c1 = ProductPriceCategory::where('name', 'Price 1')->value('id');
        $c2 = ProductPriceCategory::where('name', 'Price 2')->value('id');

        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'product_price_category_id' => $c1, 'price' => 8]);
        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'product_price_category_id' => $c2, 'price' => 9]);

        // Re-sync with only "Price 1" (new amount) -> "Price 2" pruned, "Price 1" updated.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'ITM-1', 'name' => 'Item 1', 'price' => 10,
                'prices' => [['product_price_category' => 'Price 1', 'price' => 7]],
            ]],
        ], $this->headers())->assertOk();

        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'product_price_category_id' => $c1, 'price' => 7]);
        $this->assertDatabaseMissing('product_prices', ['product_id' => $product->id, 'product_price_category_id' => $c2]);
        $this->assertSame(1, ProductPrice::where('product_id', $product->id)->count());
    }

    /** @test */
    public function product_sync_without_prices_key_leaves_category_prices_untouched()
    {
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'ITM-2', 'name' => 'Item 2', 'price' => 10,
                'prices' => [['product_price_category' => 'Price 1', 'price' => 8]],
            ]],
        ], $this->headers())->assertOk();
        $product = Product::where('code', 'ITM-2')->firstOrFail();

        // A plain product re-sync (no 'prices' key) must not wipe category prices.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [['code' => 'ITM-2', 'name' => 'Item 2 renamed', 'price' => 12]],
        ], $this->headers())->assertOk();

        $this->assertSame(1, ProductPrice::where('product_id', $product->id)->count());
    }

    /** @test */
    public function product_sync_accepts_legacy_product_type_wire_field()
    {
        // A not-yet-rebuilt plugin still sends the old key; it must still price.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'ITM-3', 'name' => 'Item 3', 'price' => 10,
                'prices' => [['product_type' => 'Price 1', 'price' => 6]],
            ]],
        ], $this->headers())->assertOk()->assertJson(['result' => true]);

        $product = Product::where('code', 'ITM-3')->firstOrFail();
        $c1 = ProductPriceCategory::where('name', 'Price 1')->value('id');
        $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'product_price_category_id' => $c1, 'price' => 6]);
    }

    /** @test */
    public function customer_sync_sets_creates_category_and_clears()
    {
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'CUS-1', 'company' => 'Acme', 'product_price_category' => 'Dealer']],
        ], $this->headers())->assertOk();

        $this->assertDatabaseHas('product_price_categories', ['name' => 'Dealer']);
        $dealer = ProductPriceCategory::where('name', 'Dealer')->value('id');
        $this->assertSame($dealer, Customer::where('code', 'CUS-1')->value('product_price_category_id'));

        // Blank clears the assignment (back to base-price fallback).
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'CUS-1', 'company' => 'Acme', 'product_price_category' => '']],
        ], $this->headers())->assertOk();
        $this->assertNull(Customer::where('code', 'CUS-1')->value('product_price_category_id'));
    }

    /** @test */
    public function customer_sync_accepts_legacy_product_type_wire_field()
    {
        $this->postJson('/api/v1/autocount/customers', [
            'customers' => [['code' => 'CUS-2', 'company' => 'Legacy Co', 'product_type' => 'Dealer']],
        ], $this->headers())->assertOk();

        $dealer = ProductPriceCategory::where('name', 'Dealer')->value('id');
        $this->assertSame($dealer, Customer::where('code', 'CUS-2')->value('product_price_category_id'));
    }

    /** @test */
    public function getprice_uses_category_price_then_falls_back_to_base()
    {
        $dealer = ProductPriceCategory::create(['name' => 'Dealer', 'status' => 1]);
        $retail = ProductPriceCategory::create(['name' => 'Retail', 'status' => 1]);
        $product = Product::create(['code' => 'ITM-9', 'name' => 'Item 9', 'price' => 10, 'status' => 1]);
        ProductPrice::create(['product_id' => $product->id, 'product_price_category_id' => $dealer->id, 'price' => 8]);

        $controller = app(InvoiceDetailController::class);

        // Customer tied to Dealer -> dealer price (8).
        $onDealer = $this->makeInvoice($this->makeCustomer('C-DEALER', $dealer->id)->id);
        $this->assertEquals(8, $this->price($controller->getprice($onDealer, $product->id)));

        // Customer with no category -> base price (10).
        $noCategory = $this->makeInvoice($this->makeCustomer('C-NONE', null)->id);
        $this->assertEquals(10, $this->price($controller->getprice($noCategory, $product->id)));

        // Customer on Retail, which has no price for this product -> base (10).
        $onRetail = $this->makeInvoice($this->makeCustomer('C-RETAIL', $retail->id)->id);
        $this->assertEquals(10, $this->price($controller->getprice($onRetail, $product->id)));
    }

    private function price($response)
    {
        return json_decode($response->getContent(), true)['data'];
    }

    private function makeCustomer(string $code, $priceCategoryId): Customer
    {
        $customer = new Customer();
        $customer->code = $code;
        $customer->company = $code;
        $customer->name = $code;            // legacy NOT NULL, not fillable
        $customer->paymentterm = 2;
        $customer->agent_id = 0;
        $customer->supervisor_id = 0;
        $customer->status = 1;
        $customer->product_price_category_id = $priceCategoryId;
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
