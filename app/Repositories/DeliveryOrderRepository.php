<?php

namespace App\Repositories;

use App\Models\DeliveryOrder;
use App\Repositories\BaseRepository;

class DeliveryOrderRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'dono',
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
        return DeliveryOrder::class;
    }
}
