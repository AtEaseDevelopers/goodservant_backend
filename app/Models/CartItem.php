<?php

namespace App\Models;

use Eloquent as Model;

/**
 * A line in a customer's ordering-portal cart (one row per product). Persisted
 * server-side so the cart follows the customer across devices and sessions.
 */
class CartItem extends Model
{
    public $table = 'cart_items';

    public $fillable = [
        'customer_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'product_id'  => 'integer',
        'quantity'    => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }
}
