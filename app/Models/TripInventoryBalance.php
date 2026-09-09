<?php

namespace App\Models;

use Eloquent as Model;

class TripInventoryBalance extends Model
{
    public $table = 'trip_inventory_balances';

    const TYPE_START = 1;
    const TYPE_END = 2;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'trip_id',
        'driver_id',
        'lorry_id',
        'product_id',
        'quantity',
        'type',
    ];

    protected $casts = [
        'id' => 'integer',
        'trip_id' => 'integer',
        'driver_id' => 'integer',
        'lorry_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'type' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id');
    }

    public function trip()
    {
        return $this->belongsTo(\App\Models\Trip::class, 'trip_id');
    }
}
