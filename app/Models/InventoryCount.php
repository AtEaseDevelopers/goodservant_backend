<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCount extends Model
{
    use HasFactory;

    public $table = 'inventory_counts';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'driver_id',
        'items',
        'status',
        'approved_by',
        'rejected_by',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'trip_id',
        'remarks',
    ];

    protected $casts = [
        'id' => 'integer',
        'driver_id' => 'integer',
        'items' => 'array',
        'status' => 'string',
        'rejection_reason' => 'string',
        'remarks' => 'string',
        'approved_by' => 'integer',
        'rejected_by' => 'integer',
        'trip_id' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    public static $rules = [
        'driver_id' => 'required|exists:drivers,id',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:0',
        'rejection_reason' => 'nullable|string|required_if:status,rejected',
        'remarks' => 'nullable|string|max:500'
    ];

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by', 'id');
    }

    public function rejector()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by', 'id');
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function getTotalQuantityAttribute()
    {
        return collect($this->items)->sum('quantity');
    }

    public function getItemCountAttribute()
    {
        return count($this->items);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function canBeUpdated()
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    public function canBeApproved()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRejected()
    {
        return $this->status === self::STATUS_PENDING;
    }
}
