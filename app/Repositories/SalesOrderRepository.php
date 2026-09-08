<?php

namespace App\Repositories;

use App\Models\SalesOrder;
use App\Repositories\BaseRepository;

class SalesOrderRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'sono',
        'date',
        'customer_id',
        'driver_id',
        'kelindan_id',
        'agent_id',
        'supervisor_id',
        'status',
        'remark'
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return SalesOrder::class;
    }
}
