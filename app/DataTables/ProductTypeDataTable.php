<?php

namespace App\DataTables;

use App\Models\ProductType;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class ProductTypeDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable->addColumn('action', 'product_types.datatables_actions');
    }

    public function query(ProductType $model)
    {
        return $model->newQuery()->select('product_types.id', 'product_types.name', 'product_types.status');
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
                'order'     => [[0, 'asc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons' => [
                    [
                        'extend' => 'create',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-plus"></i> ' . trans('table_buttons.create'),
                    ],
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reload'),
                    ],
                ],
                'columnDefs' => [
                    [
                        'targets' => 1,
                        'render' => 'function(data, type){return data == 1 ? "Active" : "Inactive";}'
                    ],
                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            var input = \'<input type="text" placeholder="Search ">\';
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
            'name'=> new \Yajra\DataTables\Html\Column(['title' => 'Name',
            'data' => 'name',
            'name' => 'name']),

            'status'=> new \Yajra\DataTables\Html\Column(['title' => 'Status',
            'data' => 'status',
            'name' => 'status']),
        ];
    }

    protected function filename()
    {
        return 'product_types_datatable_' . time();
    }
}
