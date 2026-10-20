<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Driver extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'drivers';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'employeeid',
        'password',
        'name',
        'ic',
        'phone',
        'commissionrate',
        'bankdetails1',
        'bankdetails2',
        'firstvaccine',
        'secondvaccine',
        'temperature',
        'status',
        'remark',
        'session',
        'invoice_runningnumber',
        'doc_code',
        'trip_id',
        'lorry_id',
        // AutoCount Debtor Types this driver covers; drives auto-assignment of
        // matching customers (see App\Services\DebtorTypeAssignmentService).
        'debtor_types',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'employeeid' => 'string',
        'password' => 'string',
        'id' => 'integer',
        'name' => 'string',
        'ic' => 'string',
        'phone' => 'string',
        'commissionrate' => 'float',
        'bankdetails1' => 'string',
        'bankdetails2' => 'string',
        'firstvaccine' => 'date:d-m-Y',
        'secondvaccine' => 'date:d-m-Y',
        'temperature' => 'float',
        'status' => 'integer',
        'remark' => 'string',
        'session' => 'string',
        'trip_id' => 'integer',
        'lorry_id' => 'integer',
        'debtor_types' => 'array',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'employeeid' => 'required|string|max:20|unique:drivers,employeeid',
        'doc_code' => 'required|string|max:6|regex:/^[A-Za-z0-9]+$/|unique:drivers,doc_code',
        'commissionrate' => 'nullable|numeric|min:0|max:100',
        'password' => 'required|string|max:65535',
        'name' => 'required|string|max:255',
        'ic' => 'nullable|string|max:20|unique:drivers,ic',
        'phone' => 'nullable|string|max:255',
        // 'commissionrate' => 'required|numeric|min:0|max:100',
        'bankdetails1' => 'nullable|string|max:255',
        'bankdetails2' => 'nullable|string|max:255',
        'firstvaccine' => 'nullable',
        'secondvaccine' => 'nullable',
        'temperature' => 'nullable|numeric',
        'status' => 'required',
        'remark' => 'nullable|string|max:255',
        'debtor_types' => 'nullable|array',
        'debtor_types.*' => 'string|max:255',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * Short code printed in this driver's invoice and cash-sale numbers
     * (IV2610/CODE/0001). Falls back to the employee id when no code is set.
     */
    public function documentCode(): string
    {
        $raw = $this->doc_code ?: $this->employeeid;
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw));
        $code = substr($code, 0, 6);

        return $code !== '' ? $code : str_pad((string) $this->id, 2, '0', STR_PAD_LEFT);
    }

    public function getFirstVaccineAttribute($value)
    {
        if($value != ''){
            return Carbon::parse($value)->format('d-m-Y');
        }else{
            return '';
        }
    }

    public function getSecondVaccineAttribute($value)
    {
        if($value != ''){
            return Carbon::parse($value)->format('d-m-Y');
        }else{
            return '';
        }
    }


}
