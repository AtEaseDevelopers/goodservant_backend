<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers syncing AutoCount's Item Group into the OMS product type
 * (products.type_id -> product_types). A product's type is a classification,
 * distinct from the price category (customer pricing tier). AutoCount is the
 * master: a non-empty item_group creates the product_type by name if missing
 * and is assigned on both create and update, overwriting any prior value. A
 * blank/absent item_group keeps the legacy default on create and leaves an
 * existing product's type (and image_path) untouched.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) so the configured dev DB is
 * rolled back, never rebuilt.
 */
class AutoCountProductTypeSyncTest extends TestCase
{
    use DatabaseTransactions;

    private function headers(): array
    {
        return ['X-AutoCount-Token' => (string) config('services.autocount.token')];
    }

    /** @test */
    public function item_group_creates_product_type_and_assigns_it()
    {
        $this->postJson('/api/v1/autocount/products', [
            'products' => [[
                'code' => 'PT-1', 'name' => 'Item 1', 'price' => 10,
                'item_group' => 'BEVERAGE',
            ]],
        ], $this->headers())->assertOk()->assertJson(['result' => true]);

        $this->assertDatabaseHas('product_types', ['name' => 'BEVERAGE']);

        $typeId = ProductType::where('name', 'BEVERAGE')->value('id');
        $this->assertSame($typeId, Product::where('code', 'PT-1')->value('type_id'));
    }

    /** @test */
    public function same_item_group_is_reused_not_duplicated()
    {
        $this->postJson('/api/v1/autocount/products', [
            'products' => [
                ['code' => 'PT-2', 'name' => 'Item 2', 'price' => 10, 'item_group' => 'SNACK'],
                ['code' => 'PT-3', 'name' => 'Item 3', 'price' => 10, 'item_group' => 'SNACK'],
            ],
        ], $this->headers())->assertOk();

        $this->assertSame(1, ProductType::where('name', 'SNACK')->count());

        $typeId = ProductType::where('name', 'SNACK')->value('id');
        $this->assertSame($typeId, Product::where('code', 'PT-2')->value('type_id'));
        $this->assertSame($typeId, Product::where('code', 'PT-3')->value('type_id'));
    }

    /** @test */
    public function changed_item_group_reassigns_type_id()
    {
        $this->postJson('/api/v1/autocount/products', [
            'products' => [['code' => 'PT-4', 'name' => 'Item 4', 'price' => 10, 'item_group' => 'OLD GROUP']],
        ], $this->headers())->assertOk();

        $oldId = ProductType::where('name', 'OLD GROUP')->value('id');
        $this->assertSame($oldId, Product::where('code', 'PT-4')->value('type_id'));

        // Re-sync with a different item group -> product is reassigned.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [['code' => 'PT-4', 'name' => 'Item 4', 'price' => 10, 'item_group' => 'NEW GROUP']],
        ], $this->headers())->assertOk();

        $newId = ProductType::where('name', 'NEW GROUP')->value('id');
        $this->assertNotNull($newId);
        $this->assertSame($newId, Product::where('code', 'PT-4')->value('type_id'));
    }

    /** @test */
    public function blank_item_group_defaults_on_create_and_leaves_type_and_image_untouched_on_update()
    {
        $defaultTypeId = ProductType::query()->orderBy('id')->value('id');
        $this->assertNotNull($defaultTypeId, 'expected a seeded default product type');

        // Create with no item_group -> legacy default type.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [['code' => 'PT-5', 'name' => 'Item 5', 'price' => 10]],
        ], $this->headers())->assertOk();

        $product = Product::where('code', 'PT-5')->firstOrFail();
        $this->assertSame($defaultTypeId, $product->type_id);

        // Give it an OMS-owned image and a manually chosen type.
        $manualType = ProductType::create(['name' => 'MANUAL TYPE', 'status' => 1]);
        $product->type_id = $manualType->id;
        $product->image_path = 'images/pt-5.png';
        $product->save();

        // Re-sync with blank item_group -> type_id and image_path left untouched.
        $this->postJson('/api/v1/autocount/products', [
            'products' => [['code' => 'PT-5', 'name' => 'Item 5 renamed', 'price' => 12, 'item_group' => '']],
        ], $this->headers())->assertOk();

        $product->refresh();
        $this->assertSame($manualType->id, $product->type_id);
        $this->assertSame('images/pt-5.png', $product->image_path);
        $this->assertSame('Item 5 renamed', $product->name);
    }
}
