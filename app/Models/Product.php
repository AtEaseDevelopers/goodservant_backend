<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Product
 * @package App\Models
 * @version June 20, 2023, 6:43 pm +08
 *
 * @property string $code
 * @property string $name
 * @property number $price
 * @property integer $status
 */
class Product extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'products';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    

    protected $dates = ['deleted_at'];



    public $fillable = [
        'code',
        'name',
        'image_path',
        'price',
        'status',
        'type',
        'type_id',
        'classification_code',
        'sequence'

    ];

    /**
     * Appended automatically so every JSON serialization of a Product (e.g.
     * eager-loaded via SalesOrderDetail/InvoiceDetail.product, not just the
     * product-listing endpoints that already built this manually per
     * controller) carries a ready-to-use image URL.
     */
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? url($this->image_path) : null;
    }

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'code' => 'string',
        'name' => 'string',
        'image_path' => 'string',
        'price' => 'float',
        'status' => 'integer',
        'type' => 'integer',
        'type_id' => 'integer',
        'classification_code' => 'string',
        'sequence' => 'integer',

    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'code' => 'required|string|max:255|unique:products,code',
        'name' => 'required|string|max:255|string|max:255',
        'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
        'price' => 'required|numeric|numeric',
        'status' => 'required',
        'type_ids' => 'required|array|min:1',
        'type_ids.*' => 'integer|exists:product_types,id',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    protected static function booted()
    {
        // New products (admin form or AutoCount sync) go to the end of the
        // arranged order until an admin moves them.
        static::creating(function ($product) {
            if (empty($product->sequence)) {
                $product->sequence = (int) static::max('sequence') + 1;
            }
        });
    }

    public function productType()
    {
        return $this->belongsTo(\App\Models\ProductType::class, 'type_id', 'id');
    }

    public function productTypes()
    {
        return $this->belongsToMany(\App\Models\ProductType::class, 'product_product_type', 'product_id', 'product_type_id');
    }

    /**
     * Per-price-category prices synced from AutoCount. A line price resolves as
     * the buying customer's category -> the matching row here, falling back to
     * $this->price.
     */
    public function prices()
    {
        return $this->hasMany(\App\Models\ProductPrice::class, 'product_id', 'id');
    }

    /**
     * Price for one customer. A special price wins, then that customer's price
     * category. A missing price or a 0 (AutoCount writes 0 when that category
     * has no price) falls back to the product's own price. A category stored
     * as 0 used to hide the real price for some customers only.
     */
    public static function priceForCustomer($productId, $basePrice, $customerId)
    {
        if ($customerId) {
            $special = SpecialPrice::where('product_id', $productId)
                ->where('customer_id', $customerId)
                ->where('status', 1)
                ->value('price');
            if ($special !== null && (float) $special > 0) {
                return (float) $special;
            }

            $categoryId = Customer::where('id', $customerId)->value('product_price_category_id');
            if ($categoryId) {
                $categoryPrice = ProductPrice::where('product_id', $productId)
                    ->where('product_price_category_id', $categoryId)
                    ->value('price');
                if ($categoryPrice !== null && (float) $categoryPrice > 0) {
                    return (float) $categoryPrice;
                }
            }
        }

        return (float) $basePrice;
    }
}
