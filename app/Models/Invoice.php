<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'invoices';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public const STATUS_SYNCED_TO_XERO = 1;
    public const STATUS_VOIDED = 2;

    // AutoCount sync lifecycle (sync_status column).
    public const SYNC_NONE = 0;
    public const SYNC_PENDING = 1;
    public const SYNC_SYNCING = 2;
    public const SYNC_SYNCED = 3;
    public const SYNC_FAILED = 4;

    public $fillable = [
        'invoiceno',
        'date',
        'customer_id',
        'driver_id',
        'kelindan_id',
        'agent_id',
        'supervisor_id',
        'paymentterm',
        'status',
        'sync_status',
        'api_invoice_id',
        'sync_error',
        'synced_at',
        'remark',
        'chequeno',
        'trip_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'invoiceno' => 'string',
        'date' => 'date:d-m-Y',
        'customer_id' => 'integer',
        'driver_id' => 'integer',
        'kelindan_id' => 'integer',
        'agent_id' => 'integer',
        'supervisor_id' => 'integer',
        'paymentterm' => 'integer',
        'status' => 'integer',
        'sync_status' => 'integer',
        'api_invoice_id' => 'string',
        'remark' => 'string',
        'trip_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'invoiceno' => 'nullable|string|max:255|string|max:255',
        'date' => 'required',
        'customer_id' => 'required',
        'paymentterm' => 'required',
        'status' => 'required',
        'remark' => 'nullable|string|max:255|string|max:255',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    protected static function booted()
    {
        // Auto-queue an invoice for AutoCount sync when its customer is flagged
        // with auto_sync_autocount. Hooking `creating` covers every creation
        // path at once: web admin store, the driver API (addinvoice, bulk) and
        // the SO/DO -> invoice conversions. We only act when no explicit sync
        // state was already set, and read the flag with a lightweight lookup so
        // we don't depend on the relation being loaded.
        static::creating(function (self $invoice) {
            if ((int) ($invoice->sync_status ?? self::SYNC_NONE) !== self::SYNC_NONE) {
                return;
            }
            if (empty($invoice->customer_id)) {
                return;
            }
            $autoSync = \App\Models\Customer::whereKey($invoice->customer_id)
                ->value('auto_sync_autocount');
            if ($autoSync) {
                $invoice->sync_status = self::SYNC_PENDING;
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }

    public function trip()
    {
        return $this->belongsTo(\App\Models\Trip::class, 'trip_id', 'id');
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

    public function invoicedetail()
    {
        return $this->hasMany(\App\Models\InvoiceDetail::class);
    }

    public function invoicepayment()
    {
        return $this->hasMany(\App\Models\InvoicePayment::class);
    }

    public function einvoice()
    {
        return $this->hasOne(\App\Models\Einvoice::class, 'invoice_batch_id', 'id');
    }

    public function consolidatedEinvoices()
    {
        return $this->belongsToMany(\App\Models\ConsolidatedEinvoice::class, 'consolidated_einvoice_invoices', 'invoice_id', 'consolidated_einvoice_id');
    }

    public function salesorders()
    {
        return $this->hasMany(\App\Models\SalesOrder::class, 'invoice_id');
    }

    public function deliveryorders()
    {
        return $this->hasMany(\App\Models\DeliveryOrder::class, 'invoice_id');
    }

    public function paymentAttachments()
    {
        return $this->morphMany(\App\Models\PaymentAttachment::class, 'attachable');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }


}
