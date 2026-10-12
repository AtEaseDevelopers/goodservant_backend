<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * One product's price for one product type (the type doubles as the pricing
 * category). Synced from the plugin, keyed (product_id, product_type_id). A line
 * price resolves as the buying customer's product type -> this row's price,
 * falling back to products.price.
 */
class ProductPrice extends Model
{
    use HasFactory;

    public $table = 'product_prices';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'product_id',
        'product_type_id',
        'price',
    ];

    protected $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'product_type_id' => 'integer',
        'price' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }

    public function productType()
    {
        return $this->belongsTo(\App\Models\ProductType::class, 'product_type_id', 'id');
    }
}
