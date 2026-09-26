<?php

namespace App\DataTables;

use App\Models\ApiLog;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class ApiLogDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->addColumn('action', 'api_logs.datatables_actions')
            ->editColumn('created_at', function ($log) {
                return optional($log->created_at)->timezone('+08:00')->format('d-m-Y H:i:s');
            })
            ->editColumn('status_code', function ($log) {
                return $log->status_code;
            });
    }

    public function query(ApiLog $model)
    {
        return $model->newQuery()
            ->with('driver:id,name')
            ->select('api_logs.*')
            ->orderBy('api_logs.id', 'desc');
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
                'order'     => [[0, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons' => [
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reload'),
                    ],
                    [
                        'extend' => 'colvis',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-columns"></i> ' . trans('table_buttons.column')
                    ],
                    [
                        'extend' => 'pageLength',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => trans('table_buttons.show_10_rows')
                    ],
                ],
                'columnDefs' => [
                    [
                        'targets' => 4,
                        'render' => 'function(data, type){
                            if(type !== "display") return data;
                            var color = (data >= 200 && data < 300) ? "#28a745" : "#dc3545";
                            return "<span style=\"color:" + color + ";font-weight:bold;\">" + data + "</span>";
                        }'
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
            'id'=> new \Yajra\DataTables\Html\Column(['title' => 'ID',
            'data' => 'id',
            'name' => 'api_logs.id']),

            'created_at'=> new \Yajra\DataTables\Html\Column(['title' => 'Date',
            'data' => 'created_at',
            'name' => 'api_logs.created_at']),

            'driver.name'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver',
            'data' => 'driver.name',
            'name' => 'driver.name',
            'orderable' => false]),

            'method'=> new \Yajra\DataTables\Html\Column(['title' => 'Method',
            'data' => 'method',
            'name' => 'method']),

            'status_code'=> new \Yajra\DataTables\Html\Column(['title' => 'Status',
            'data' => 'status_code',
            'name' => 'status_code']),

            'url'=> new \Yajra\DataTables\Html\Column(['title' => 'URL',
            'data' => 'url',
            'name' => 'url']),

            'ip_address'=> new \Yajra\DataTables\Html\Column(['title' => 'IP',
            'data' => 'ip_address',
            'name' => 'ip_address']),
        ];
    }

    protected function filename()
    {
        return 'api_logs_datatable_' . time();
    }
}
