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

        return $dataTable->addColumn('action', 'sales_orders.datatables_actions')
            ->rawColumns(['converted_to', 'action']);
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
            ->addAction(['title' => 'Action', 'printable' => false])
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
                        'targets' => 5,
                        'render' => 'function(data, type){
                                var paymentTerms = {
                                    1: \'Cash\',
                                    2: \'Credit\',
                                    3: \'Online BankIn\',
                                    4: \'E-wallet\',
                                    5: \'Cheque\'
                                };
                                return paymentTerms[data] || \'-\';
                            }'
                    ],
                    [
                        'targets' => 6,
                        'render' => 'function(data, type){return data == 1 ? "Completed" : "New";}'
                    ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value="1">Completed</option><option value="0">New</option></select>\';
                            }else if(columns[index].title == \'Date\'){
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
                'title' => 'Sales Order No',
                'data' => 'sono',
                'name' => 'sono'
            ]),

            'date' => new \Yajra\DataTables\Html\Column([
                'title' => 'Date',
                'data' => 'date',
                'name' => 'date'
            ]),

            'customer_id' => new \Yajra\DataTables\Html\Column([
                'title' => 'Customer',
                'data' => 'customer.company',
                'name' => 'customer.company'
            ]),

            'driver_id' => new \Yajra\DataTables\Html\Column([
                'title' => 'Driver',
                'data' => 'driver.name',
                'name' => 'driver.name'
            ]),

            'paymentterm' => new \Yajra\DataTables\Html\Column([
                'title' => 'Payment Term',
                'data' => 'paymentterm',
                'name' => 'sales_orders.paymentterm'
            ]),

            'status' => new \Yajra\DataTables\Html\Column([
                'title' => 'Status',
                'data' => 'status',
                'name' => 'sales_orders.status'
            ]),

            'converted_to' => new \Yajra\DataTables\Html\Column([
                'title' => 'Converted To',
                'data' => 'converted_to',
                'name' => 'converted_to',
                'orderable' => false,
                'searchable' => false
            ]),
        ];
    }

    protected function filename()
    {
        return 'sales_orders_datatable_' . time();
    }
}
