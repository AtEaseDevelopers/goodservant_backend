<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    public $table = 'product_types';

    protected $fillable = [
        'name',
        'status',
        'show_in_app',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'status' => 'integer',
        'show_in_app' => 'boolean',
    ];

    public static $rules = [
        'name' => 'required|string|max:255',
        'status' => 'required',
    ];

    public function products()
    {
        return $this->hasMany(\App\Models\Product::class, 'type_id', 'id');
    }
}
