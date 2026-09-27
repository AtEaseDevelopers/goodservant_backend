<?php

namespace App\DataTables;

use App\Models\Einvoice;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class InvoiceDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new QueryDataTable($query);

        $dataTable->addColumn('row_key', function ($row) {
            return $row->doc_type . ':' . $row->id;
        });

        // Add e-invoice column conditionally (Invoice rows only; Delivery
        // Order rows never carry e-invoice data of their own).
        if (config('services.e_invoice.enabled', false)) {
            $dataTable->addColumn('einvoice_submit_type', function ($row) {
                if ($row->doc_type !== 'invoice') {
                    return '';
                }
                $hasDirectSubmit = Einvoice::where('invoice_batch_id', $row->id)->whereNotNull('uuid')->exists();
                if ($hasDirectSubmit) {
                    return 'E-Invoice';
                }
                $hasConsolidated = DB::table('consolidated_einvoices')
                    ->join('consolidated_einvoice_invoices', 'consolidated_einvoice_invoices.consolidated_einvoice_id', '=', 'consolidated_einvoices.id')
                    ->where('consolidated_einvoice_invoices.invoice_id', $row->id)
                    ->whereNotNull('consolidated_einvoices.uuid')
                    ->exists();
                return $hasConsolidated ? 'Consolidated E-Invoice' : '';
            });
        }

        return $dataTable->addColumn('action', 'invoices.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * Unions Invoices and Delivery Orders into a single result set so the
     * admin can view/filter both document types from one page (Delivery
     * Orders no longer have their own top-level listing).
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        $invoices = DB::table('invoices')
            ->leftJoin('customers', 'customers.id', '=', 'invoices.customer_id')
            ->leftJoin('drivers', 'drivers.id', '=', 'invoices.driver_id')
            ->leftJoin('kelindans', 'kelindans.id', '=', 'invoices.kelindan_id')
            ->whereNull('invoices.deleted_at')
            ->select([
                'invoices.id',
                DB::raw("'invoice' as doc_type"),
                DB::raw("'Invoice' as doc_type_label"),
                'invoices.invoiceno as doc_no',
                'invoices.date',
                DB::raw('customers.company as customer_name'),
                DB::raw('drivers.name as driver_name'),
                DB::raw('kelindans.name as kelindan_name'),
                DB::raw('(select coalesce(sum(id.totalprice), 0) from invoice_details id where id.invoice_id = invoices.id) as total'),
                'invoices.paymentterm',
                'invoices.status',
            ]);

        $deliveryOrders = DB::table('deliveryorders')
            ->leftJoin('customers', 'customers.id', '=', 'deliveryorders.customer_id')
            ->leftJoin('drivers', 'drivers.id', '=', 'deliveryorders.driver_id')
            ->leftJoin('kelindans', 'kelindans.id', '=', 'deliveryorders.kelindan_id')
            ->whereNull('deliveryorders.deleted_at')
            ->select([
                'deliveryorders.id',
                DB::raw("'do' as doc_type"),
                DB::raw("'Delivery Order' as doc_type_label"),
                'deliveryorders.dono as doc_no',
                'deliveryorders.date',
                DB::raw('customers.company as customer_name'),
                DB::raw('drivers.name as driver_name'),
                DB::raw('kelindans.name as kelindan_name'),
                DB::raw('(select coalesce(sum(dd.totalprice), 0) from deliveryorder_details dd where dd.deliveryorder_id = deliveryorders.id) as total'),
                'deliveryorders.paymentterm',
                'deliveryorders.status',
            ]);

        // Wrapped as a subquery (rather than returning the union builder
        // directly) so that Yajra's per-column/global search and ordering -
        // which append where()/orderBy() calls on top of this builder - are
        // applied to the combined result set, not just the first half of
        // the union.
        return DB::query()->fromSub($invoices->unionAll($deliveryOrders), 'combined_docs');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [], ['type' => 'POST', 'headers' => ['X-HTTP-Method-Override' => 'GET']])
            ->addAction(['title' => trans('invoices.action'), 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[3, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons' => [
                    [
                        'extend' => 'create',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-plus"></i> ' . trans('table_buttons.create'),
                    ],
                    [
                        'extend' => 'print',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-print"></i> ' . trans('table_buttons.print'),
                    ],
                    [
                        'extend' => 'reset',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reset'),
                    ],
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reload'),
                    ],
                    [
                        'extend' => 'excelHtml5',
                        'text' => '<i class="fa fa-file-excel-o"></i> ' . trans('table_buttons.excel'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'invoice' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'invoice' . date('dmYHis')
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
                        'targets' => -1,
                        'visible' => true
                    ],
                    [
                        'targets' => 0,
                        'visible' => true,
                        'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    ],
                    [
                        'targets' => 7,
                        'visible' => true,
                        'render' => 'function(data, type){return parseFloat(data).toFixed(2);}'
                    ],
                    [
                    'targets' => 8,
                    'render' => 'function(data, type, row){
                            var paymentTerms = {
                                1: \'Cash\',
                                2: \'Credit\',
                                3: \'Online Banking (QR Code)\',
                                4: \'E-wallet\',
                                5: \'Cheque\'
                            };
                            return paymentTerms[data] || \'Unknown\';
                        }'
                    ],
                    [
                    'targets' => 9,
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
                            }else if(columns[index].title == \'Payment Term\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value=""></option><option value="1">Cash</option><option value="2">Credit</option><option value="3">Online Banking (QR Code)</option><option value="4">E-wallet</option><option value="5">Cheque</option></select>\';
                            }else if(columns[index].title == \'Type\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value=""></option><option value="Invoice">Invoice</option><option value="Delivery Order">Delivery Order</option></select>\';
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

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $columns = [
            'checkbox'=> new \Yajra\DataTables\Html\Column(['title' => '<input type="checkbox" id="selectallcheckbox">',
            'data' => 'row_key',
            'name' => 'row_key',
            'orderable' => false,
            'searchable' => false
            ]),

            'doc_type_label' => new \Yajra\DataTables\Html\Column([
                'title' => 'Type',
                'data' => 'doc_type_label',
                'name' => 'doc_type_label'
            ]),

            'doc_no' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.invoice_no'),
                'data' => 'doc_no',
                'name' => 'doc_no'
            ]),

            'date' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.date'),
                'data' => 'date',
                'name' => 'date'
            ]),

            'customer_name' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.customer'),
                'data' => 'customer_name',
                'name' => 'customer_name'
            ]),

            'driver_name' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.driver'),
                'data' => 'driver_name',
                'name' => 'driver_name'
            ]),

            'kelindan_name' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.kelindan'),
                'data' => 'kelindan_name',
                'name' => 'kelindan_name'
            ]),

            'total' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.total_price'),
                'data' => 'total',
                'name' => 'total',
                'searchable' => false
            ]),

            'paymentterm' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.payment_term'),
                'data' => 'paymentterm',
                'name' => 'paymentterm'
            ]),

            'status' => new \Yajra\DataTables\Html\Column([
                'title' => trans('invoices.status'),
                'data' => 'status',
                'name' => 'status'
            ]),
        ];

        if (config('services.e_invoice.enabled', false)) {
            $columns['einvoice_submit_type'] = new \Yajra\DataTables\Html\Column([
                'title' => 'E-Invoice Submit Type',
                'data' => 'einvoice_submit_type',
                'name' => 'einvoice_submit_type',
                'orderable' => false,
                'searchable' => false
            ]);
        }
        return $columns;

    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'invoices_datatable_' . time();
    }
}
