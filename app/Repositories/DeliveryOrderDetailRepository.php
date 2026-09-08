<?php

namespace App\Repositories;

use App\Models\DeliveryOrderDetail;
use App\Repositories\BaseRepository;

class DeliveryOrderDetailRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'deliveryorder_id',
        'product_id',
        'quantity',
        'price',
        'remark'
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return DeliveryOrderDetail::class;
    }
}
