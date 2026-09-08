<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class SalesOrder extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'sales_orders';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'sono',
        'date',
        'customer_id',
        'driver_id',
        'kelindan_id',
        'agent_id',
        'supervisor_id',
        'paymentterm',
        'status',
        'remark',
        'chequeno',
        'deliveryorder_id',
        'invoice_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'sono' => 'string',
        'date' => 'datetime:d-m-Y H:i:s',
        'customer_id' => 'integer',
        'driver_id' => 'integer',
        'kelindan_id' => 'integer',
        'agent_id' => 'integer',
        'supervisor_id' => 'integer',
        'paymentterm' => 'integer',
        'status' => 'integer',
        'remark' => 'string',
        'deliveryorder_id' => 'integer',
        'invoice_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'sono' => 'nullable|string|max:255',
        'date' => 'required',
        'customer_id' => 'required',
        'status' => 'required',
        'remark' => 'nullable|string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function kelindan()
    {
        return $this->belongsTo(\App\Models\Kelindan::class, 'kelindan_id', 'id');
    }

    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class, 'agent_id', 'id');
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\Supervisor::class, 'supervisor_id', 'id');
    }

    public function salesorderdetail()
    {
        return $this->hasMany(\App\Models\SalesOrderDetail::class, 'sales_order_id');
    }

    public function deliveryorder()
    {
        return $this->belongsTo(\App\Models\DeliveryOrder::class, 'deliveryorder_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'invoice_id', 'id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }
}
