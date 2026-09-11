@section('css')
    @include('layouts.datatables_css')
    <style>
        /* Alternate a background per lorry group (not per row) - overrides
           table-striped's own odd/even row coloring, which doesn't line up
           with group boundaries since lorries have varying product counts. */
        tr.lorry-group-odd td {
            background-color: #ffffff !important;
        }
        tr.lorry-group-even td {
            background-color: #eef3ff !important;
        }
        tr.dtrg-start td {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #c8d4f0;
        }
        tr.dtrg-start.lorry-group-odd td {
            background-color: #e2e2e2 !important;
        }
        tr.dtrg-start.lorry-group-even td {
            background-color: #c9d8f7 !important;
        }
    </style>
@endsection

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-bordered'], true) !!}

@push('scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    
    <script>
        $(document).ready(function () {

            $(".buttons-reset").click(function(e){
                $('#dataTableBuilder tfoot th input').val('');
                $('#dataTableBuilder tfoot th select').val(1);
            });
            var table = $('#dataTableBuilder').DataTable();
            table.on( 'draw', function () {
                setcheckbox(window.checkboxid);
                checkcheckbox();
                HideLoad();

                // Alternate a background color per lorry group (not per row). Bound
                // here (after RowGroup's own 'draw' listener, since it registers
                // during table init before this handler is attached) rather than via
                // the 'drawCallback' init option, which fires before RowGroup has
                // inserted/updated the .dtrg-start header rows for this draw.
                var toggle = 0;
                $(table.table().body()).find('tr').each(function(){
                    if($(this).hasClass('dtrg-start')){
                        toggle = 1 - toggle;
                    }
                    $(this).toggleClass('lorry-group-even', toggle === 1);
                    $(this).toggleClass('lorry-group-odd', toggle === 0);
                });
            });
            table.on( 'preDraw', function () {
                ShowLoad();
            });
            if(resize == 1){
                $('#dataTableBuilder').resizableColumns();
            }
        });

        function getTableCode(data)
        {
            var tbl  = document.createElement("table");
            tbl.className = "table table-sm table-striped";
            var tr = tbl.insertRow(-1);
            $.each( data[0], function( key, value ) {
                var td = tr.insertCell();
                td.appendChild(document.createTextNode(key.charAt(0).toUpperCase() + key.slice(1)));
            });
            for (var i = 0; i < data.length; ++i)
            {
                var tr = tbl.insertRow();
                $.each( data[i], function( key, value ) {
                    var td = tr.insertCell();
                    td.appendChild(document.createTextNode(value.toString()));
                });
            }
            return tbl;
        }
        
        function searchDateColumn(i){
            $('#columnid').val(i.id);
            $('#dateModel').modal('show')
        }

        function dateRange(steps = 1) {
            if($('#datefrommodel').val() == ''){
                noti('i','Date From cannot be empty','Please select the Date From');
                return;
            }
            if($('#datetomodel').val() == ''){
                noti('i','Date To cannot be empty','Please select the Date To');
                return;
            }
            if($('#datetomodel').val() < $('#datefrommodel').val()){
                noti('i','Date From cannot greater than Date To','Please select the Date again');
                return;
            }
            var dateArray = '';
            var currentDateParts = $('#datefrommodel').val().split("-");
            var endDateParts = $('#datetomodel').val().split("-");
            var currentDate = new Date(+currentDateParts[2], currentDateParts[1] - 1, +currentDateParts[0]);
            var endDate = new Date(+endDateParts[2], endDateParts[1] - 1, +endDateParts[0]);
                
            while (currentDate <= endDate) {
                dateArray=dateArray+moment(currentDate).format("YYYY-MM-DD")+'|';
                currentDate.setUTCDate(currentDate.getUTCDate() + steps);
            }

            $('#'+$('#columnid').val()).val(dateArray.substring(0, dateArray.length-1)).change();
            $('#dateModel').modal('hide')
        }
        var start = moment();
        var end = moment();

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
            $('#datefrommodel').val(start.format('DD-MM-YYYY'));
            $('#datetomodel').val(end.format('DD-MM-YYYY'));
        }
        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        window.checkboxid = [];
        $(document).on("change", ".checkboxselect", function(e){
            if(this.checked){
                addcheckboxid($(this).attr('checkboxid'));
            }
            else{
                removecheckboxid($(this).attr('checkboxid'));
            }
            checkcheckbox();
        });
        $(document).on("change", "#selectallcheckbox", function(e){
            var checkall = this.checked;
            $('.checkboxselect').each(function(i, obj) {
                if(checkall){
                    if(!obj.checked){
                        addcheckboxid($(obj).attr('checkboxid'));
                        $(obj).prop( "checked", checkall );
                    }
                }else{
                    if(obj.checked){
                        removecheckboxid($(obj).attr('checkboxid'));
                        $(obj).prop( "checked", checkall );
                    }
                }
            });
        });
        function addcheckboxid(checkboxid){
            window.checkboxid.push(checkboxid);
            // console.log(window.checkboxid);
        }
        function removecheckboxid(checkboxid){
            window.checkboxid = jQuery.grep(window.checkboxid, function(value) {
                return value != checkboxid;
            });
            // console.log(window.checkboxid);
        }
        function setcheckbox(checkboxids){
            // console.log(checkboxid);
            for (i = 0; i < checkboxids.length; ++i) {
                $('input[class="checkboxselect"][checkboxid="'+checkboxids[i]+'"]').prop( "checked", true );
            }
        }
        function checkcheckbox(){
            var checked = 0;
            var checkbox = $('.checkboxselect');
            checkbox.each(function(i, obj) {
                if(obj.checked){
                    checked ++;
                }
            });
            if(checked == checkbox.length){
                $('#selectallcheckbox').prop( "checked", true );
            }else{
                $('#selectallcheckbox').prop( "checked", false );
            }
        }
    </script>
@endpush

<div class="modal fade" id="dateModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="infoModelLabel">Select a date range</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <input id="columnid" type="hidden" value="">
            <input id="datefrommodel" type="hidden" value="">
            <input id="datetomodel" type="hidden" value="">
            <div class="form-group col-sm-12">
                <label for="datefrommodel">Date:</label>
                <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                    <i class="fa fa-calendar"></i>&nbsp;
                    <span></span> <i class="fa fa-caret-down"></i>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="dateRange();">Search</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
</div>