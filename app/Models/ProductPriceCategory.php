<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A customer pricing tier (e.g. "Price 1".."Price 6", "Dealer"). Distinct from
 * ProductType, which classifies the product itself (e.g. "Noodle"). A line price
 * resolves as the customer's price category -> that product's product_prices row
 * for the category -> products.price. Categories are synced from AutoCount and
 * created by name on the fly.
 */
class ProductPriceCategory extends Model
{
    use HasFactory;

    public $table = 'product_price_categories';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'status' => 'integer',
    ];

    public static $rules = [
        'name' => 'required|string|max:255',
        'status' => 'required',
    ];

    public function customers()
    {
        return $this->hasMany(\App\Models\Customer::class, 'product_price_category_id', 'id');
    }

    public function prices()
    {
        return $this->hasMany(\App\Models\ProductPrice::class, 'product_price_category_id', 'id');
    }
}
