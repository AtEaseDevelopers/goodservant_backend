<?php

namespace App\Repositories;

use App\Models\SalesOrderDetail;
use App\Repositories\BaseRepository;

class SalesOrderDetailRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'sales_order_id',
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
        return SalesOrderDetail::class;
    }
}
