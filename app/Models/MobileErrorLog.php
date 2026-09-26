<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileErrorLog extends Model
{
    use HasFactory;

    public $table = 'mobile_error_logs';

    protected $fillable = [
        'driver_id',
        'app_version',
        'screen',
        'message',
        'stack_trace',
    ];

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id', 'id');
    }
}
