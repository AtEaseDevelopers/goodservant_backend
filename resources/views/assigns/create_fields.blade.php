<!-- Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('driver_id', __('assign.driver')) !!}<span class="asterisk"> *</span>
    {!! Form::select('driver_id', $driverItems, null, ['class' => 'form-control select2-driver', 'placeholder' => 'Pick a Driver...','autofocus']) !!}
</div>

<!-- Customers -->
<div class="form-group col-sm-12">
    <table class="table table-striped table-bordered" id="assign-rows" width="100%">
        <thead>
            <tr>
                <th>{{ __('invoices.customer') }}<span class="asterisk"> *</span></th>
                <th width="160">{{ __('assign.sequence') }}<span class="asterisk"> *</span></th>
                <th width="60"></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <button type="button" class="btn btn-success btn-sm" id="add-assign-row"><i class="fa fa-plus"></i> {{ __('Add Customer') }}</button>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('assign.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('assigns.index') }}" class="btn btn-secondary">{{ __('assign.cancel') }}</a>
</div>

@push('scripts')
    <script>
        var customerItems = @json($customerItems);
        var oldCustomers = @json(array_values((array) old('customer_id', [])));
        var oldSequences = @json(array_values((array) old('sequence', [])));

        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });

        function nextSequence() {
            var max = 0;
            $('#assign-rows tbody input[name="sequence[]"]').each(function() {
                max = Math.max(max, parseInt($(this).val()) || 0);
            });
            return max + 1;
        }

        function addAssignRow(customerId, sequence) {
            var $select = $('<select name="customer_id[]" class="form-control select2-customer" required></select>');
            $select.append('<option value=""></option>');
            // Sorted by company name; a plain object would come back ordered by id
            Object.keys(customerItems).sort(function(a, b) {
                return String(customerItems[a]).localeCompare(String(customerItems[b]));
            }).forEach(function(id) {
                $select.append($('<option></option>').val(id).text(customerItems[id]));
            });

            var $row = $('<tr></tr>');
            $row.append($('<td></td>').append($select));
            $row.append('<td><input type="number" name="sequence[]" class="form-control" min="0" step="1" required></td>');
            $row.append('<td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-assign-row"><i class="fa fa-trash"></i></button></td>');
            $row.find('input[name="sequence[]"]').val(sequence != null ? sequence : nextSequence());
            $('#assign-rows tbody').append($row);

            $select.val(customerId || '').select2({
                placeholder: "Search for a customer...",
                allowClear: true,
                width: '100%'
            });
        }

        $(document).on('click', '#add-assign-row', function() {
            addAssignRow();
        });

        $(document).on('click', '.remove-assign-row', function() {
            if ($('#assign-rows tbody tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });

        // The same customer can only be assigned once per driver
        $(document).on('change', 'select[name="customer_id[]"]', function() {
            var current = this;
            var value = $(this).val();
            if (!value) return;
            $('select[name="customer_id[]"]').not(current).each(function() {
                if ($(this).val() == value) {
                    noti('w', 'Warning', 'This customer is already in the list');
                    $(current).val('').trigger('change.select2');
                    return false;
                }
            });
        });

        $(document).ready(function () {
            $('.select2-driver').select2({
                placeholder: "Search for a driver...",
                allowClear: true,
                width: '100%'
            });

            if (oldCustomers.length) {
                oldCustomers.forEach(function(customerId, index) {
                    addAssignRow(customerId, oldSequences[index]);
                });
            } else {
                addAssignRow();
            }

            HideLoad();
        });
    </script>

    <style>
        .select2-container--default .select2-selection--single {
            border: 1px solid #ced4da;
            border-radius: .25rem;
            height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@endpush
