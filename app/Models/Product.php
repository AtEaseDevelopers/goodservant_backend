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
        'classification_code'

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
        'type_id' => 'required',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function productType()
    {
        return $this->belongsTo(\App\Models\ProductType::class, 'type_id', 'id');
    }
}
