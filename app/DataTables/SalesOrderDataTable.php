<?php

namespace App\DataTables;

use App\Models\SalesOrder;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class SalesOrderDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        $dataTable->addColumn('converted_to', function ($salesOrder) {
            if (!empty($salesOrder->deliveryorder)) {
                return '<a href="' . route('deliveryOrders.show', encrypt($salesOrder->deliveryorder->id)) . '">' . e($salesOrder->deliveryorder->dono) . '</a>';
            }
            if (!empty($salesOrder->invoice)) {
                return '<a href="' . route('invoices.show', encrypt($salesOrder->invoice->id)) . '">' . e($salesOrder->invoice->invoiceno) . '</a>';
            }
            return '-';
        });

        $dataTable->addColumn('off_day_label', function ($salesOrder) {
            return $salesOrder->off_day
                ? '<span class="badge badge-info">Off Day</span>'
                : '-';
        });

        // The off_day column shows an "Off Day"/"-" badge, so the default LIKE
        // search never matches. The header filter is a Yes/No select sending 1/0.
        $dataTable->filterColumn('sales_orders.off_day', function ($query, $keyword) {
            $keyword = strtolower(trim($keyword));
            if (in_array($keyword, ['1', 'yes'], true)) {
                $query->where('sales_orders.off_day', 1);
            } elseif (in_array($keyword, ['0', 'no'], true)) {
                $query->where(function ($q) {
                    $q->where('sales_orders.off_day', 0)->orWhereNull('sales_orders.off_day');
                });
            }
        });

        return $dataTable->addColumn('action', function ($salesOrder) {
            return view('sales_orders.datatables_actions', [
                'id'       => $salesOrder->id,
                'off_day'  => (bool) $salesOrder->off_day,
                'editable' => empty($salesOrder->deliveryorder_id)
                    && empty($salesOrder->invoice_id)
                    && (int) $salesOrder->status !== 2,
            ])->render();
        })->rawColumns(['converted_to', 'off_day_label', 'action']);
    }

    public function query(SalesOrder $model)
    {
        return $model->newQuery()
            ->with('customer')
            ->with('driver')
            ->with('deliveryorder')
            ->with('invoice')
            ->select('sales_orders.*');
    }

    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [], ['type' => 'POST', 'headers' => ['X-HTTP-Method-Override' => 'GET']])
            ->addAction(['title' => trans('sales_orders.action'), 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[2, 'desc']],
                'lengthMenu' => [[10, 50, 100, 300], ['10 rows', '50 rows', '100 rows', '300 rows']],
                'buttons' => [
                    [
                        'extend' => 'create',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-plus"></i> Create',
                    ],
                    [
                        'extend' => 'reset',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> Reset',
                    ],
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> Reload',
                    ],
                    [
                        'extend' => 'colvis',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-columns"></i> Column'
                    ],
                    [
                        'extend' => 'pageLength',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => 'Show 10 rows'
                    ],
                ],
                'columnDefs' => [
                    [
                        'targets' => -1,
                        'visible' => true
                    ],
                    [
                        'targets' => 0,
                        'visible' => true,
                        'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    ],
                    [
                        'targets' => 6,
                        'render' => 'function(data, type){
                                var paymentTerms = {
                                    1: \'Cash\',
                                    2: \'Credit\',
                                    3: \'Online Banking (QR Code)\',
                                    4: \'E-wallet\',
                                    5: \'Cheque\'
                                };
                                return paymentTerms[data] || \'-\';
                            }'
                    ],
                    [
                        'targets' => 7,
                        'render' => 'function(data, type){return data == 2 ? "Cancelled" : (data == 1 ? "Completed" : "New");}'
                    ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'' . addslashes(trans('sales_orders.status')) . '\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Completed</option><option value="0">New</option><option value="2">Cancelled</option></select>\';
                            }else if(columns[index].title == \'' . addslashes(trans('sales_orders.off_day')) . '\'){
                                var saved = String(column.search() || \'\').toLowerCase();
                                var current = (saved == \'1\' || saved == \'yes\') ? \'1\' : ((saved == \'0\' || saved == \'no\') ? \'0\' : \'\');
                                var input = $(\'<select class="border-0" style="width: 100%;"><option value="">All</option><option value="1">Yes</option><option value="0">No</option></select>\').val(current);
                            }else if(columns[index].title == \'' . addslashes(trans('sales_orders.date')) . '\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                column.search($(this).val(),true,false).draw();
                                ShowLoad();
                            })
                        }
                    });
                }'
            ]);
    }

    protected function getColumns()
    {
        return [
            'checkbox' => new \Yajra\DataTables\Html\Column([
                'title' => '<input type="checkbox" id="selectallcheckbox">',
                'data' => 'id',
                'name' => 'id',
                'orderable' => false,
                'searchable' => false
            ]),

            'sono' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.sales_order_no'),
                'data' => 'sono',
                'name' => 'sono'
            ]),

            'date' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.date'),
                'data' => 'date',
                'name' => 'date'
            ]),

            'customer_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.customer'),
                'data' => 'customer.company',
                'name' => 'customer.company'
            ]),

            'second_description' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.second_description'),
                'data' => 'customer.second_description',
                'name' => 'customer.second_description',
                'defaultContent' => ''
            ]),

            'driver_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.driver'),
                'data' => 'driver.name',
                'name' => 'driver.name'
            ]),

            'paymentterm' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.payment_term'),
                'data' => 'paymentterm',
                'name' => 'sales_orders.paymentterm'
            ]),

            'status' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.status'),
                'data' => 'status',
                'name' => 'sales_orders.status'
            ]),

            'converted_to' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.converted_to'),
                'data' => 'converted_to',
                'name' => 'converted_to',
                'orderable' => false,
                'searchable' => false
            ]),

            'off_day' => new \Yajra\DataTables\Html\Column([
                'title' => trans('sales_orders.off_day'),
                'data' => 'off_day_label',
                'name' => 'sales_orders.off_day',
                'orderable' => false
            ]),
        ];
    }

    protected function filename()
    {
        return 'sales_orders_datatable_' . time();
    }
}
