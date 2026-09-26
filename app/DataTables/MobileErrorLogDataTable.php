<?php

namespace App\DataTables;

use App\Models\MobileErrorLog;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class MobileErrorLogDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->addColumn('action', 'mobile_error_logs.datatables_actions')
            ->editColumn('created_at', function ($log) {
                return optional($log->created_at)->timezone('+08:00')->format('d-m-Y H:i:s');
            })
            ->editColumn('message', function ($log) {
                return \Illuminate\Support\Str::limit($log->message, 120);
            });
    }

    public function query(MobileErrorLog $model)
    {
        return $model->newQuery()
            ->with('driver:id,name')
            ->select('mobile_error_logs.*')
            ->orderBy('mobile_error_logs.id', 'desc');
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
            'name' => 'mobile_error_logs.id']),

            'created_at'=> new \Yajra\DataTables\Html\Column(['title' => 'Date',
            'data' => 'created_at',
            'name' => 'mobile_error_logs.created_at']),

            'driver.name'=> new \Yajra\DataTables\Html\Column(['title' => 'Driver',
            'data' => 'driver.name',
            'name' => 'driver.name',
            'orderable' => false]),

            'app_version'=> new \Yajra\DataTables\Html\Column(['title' => 'App Version',
            'data' => 'app_version',
            'name' => 'app_version']),

            'screen'=> new \Yajra\DataTables\Html\Column(['title' => 'Screen',
            'data' => 'screen',
            'name' => 'screen']),

            'message'=> new \Yajra\DataTables\Html\Column(['title' => 'Message',
            'data' => 'message',
            'name' => 'message']),
        ];
    }

    protected function filename()
    {
        return 'mobile_error_logs_datatable_' . time();
    }
}
