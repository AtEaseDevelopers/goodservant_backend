<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * One product's price for one product price category. Synced from the plugin,
 * keyed (product_id, product_price_category_id). A line price resolves as the
 * buying customer's price category -> this row's price, falling back to
 * products.price.
 */
class ProductPrice extends Model
{
    use HasFactory;

    public $table = 'product_prices';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'product_id',
        'product_price_category_id',
        'price',
    ];

    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'product_price_category_id' => 'integer',
        'price' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }

    public function productPriceCategory()
    {
        return $this->belongsTo(\App\Models\ProductPriceCategory::class, 'product_price_category_id', 'id');
    }
}
