@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">{{ __('trips.trips') }}</a></li>
        <li class="breadcrumb-item active">Task sequence</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-list"></i>
                            <strong>Trip #{{ $trip->id }}</strong>
                            — {{ $trip->driver->name ?? 'Driver' }}
                            <span class="text-muted">{{ $trip->date }}</span>
                            <span class="text-muted">({{ $tasks->count() }} stops)</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('trips.tasks.store', $tripKey) }}" class="mb-4">
                                @csrf
                                <div class="form-row align-items-end">
                                    <div class="form-group col-sm-8">
                                        <label>Add a stop</label>
                                        <select name="customer_id" class="form-control" required>
                                            <option value="">Select customer</option>
                                            @foreach($customers as $customerId => $company)
                                                <option value="{{ $customerId }}">{{ $company }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-plus"></i> Add task
                                        </button>
                                    </div>
                                </div>
                            </form>

                            @if($tasks->isEmpty())
                                <p class="text-muted">This trip has no tasks yet.</p>
                            @else
                                <form method="POST" action="{{ route('trips.tasks.sequence', $tripKey) }}">
                                    @csrf
                                    <p class="text-muted">Drag the rows into the order the driver should follow, then save.</p>
                                    <table class="table table-striped table-bordered" id="sortable-table">
                                        <thead>
                                            <tr>
                                                <th width="40"></th>
                                                <th width="80">Sequence</th>
                                                <th>Customer</th>
                                                <th width="120">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tasks as $index => $task)
                                                @php
                                                    $statusLabels = [0 => 'New', 1 => 'In-Progress', 8 => 'Completed', 9 => 'Cancelled'];
                                                @endphp
                                                <tr>
                                                    <td class="text-center"><span class="drag-handle" draggable="true">☰</span></td>
                                                    <td class="sequence-cell">{{ $index + 1 }}</td>
                                                    <td>
                                                        <input type="hidden" name="tasks[]" value="{{ $task->id }}">
                                                        {{ $task->customer_company ?: '-' }}
                                                    </td>
                                                    <td>{{ $statusLabels[$task->status] ?? $task->status }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save"></i> Save sequence
                                    </button>
                                    <a href="{{ route('trips.index') }}" class="btn btn-secondary">Back</a>
                                </form>
                            @endif

                            @if($tasks->isEmpty())
                                <a href="{{ route('trips.index') }}" class="btn btn-secondary">Back</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        #sortable-table tbody tr { cursor: move; }
        .sortable-placeholder { background-color: #f0f0f0; border: 2px dashed #ccc; height: 42px; }
        .drag-handle { cursor: move; color: #999; font-size: 18px; user-select: none; }
        #sortable-table tr.dragging { opacity: 0.5; }
    </style>
@endpush

@push('scripts')
    <script>
        HideLoad();
        (function () {
            var tbody = document.querySelector('#sortable-table tbody');
            if (!tbody) {
                return;
            }
            var dragged = null;

            function renumber() {
                var rows = tbody.querySelectorAll('tr');
                for (var i = 0; i < rows.length; i++) {
                    var cell = rows[i].querySelector('.sequence-cell');
                    if (cell) {
                        cell.textContent = String(i + 1);
                    }
                }
            }

            tbody.addEventListener('dragstart', function (event) {
                var handle = event.target.closest('.drag-handle');
                if (!handle) {
                    return;
                }
                dragged = handle.closest('tr');
                if (!dragged) {
                    return;
                }
                dragged.classList.add('dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', dragged.querySelector('input[name="tasks[]"]').value);
            });

            tbody.addEventListener('dragover', function (event) {
                if (!dragged) {
                    return;
                }
                event.preventDefault();
                var row = event.target.closest('tr');
                if (!row || row === dragged || row.parentNode !== tbody) {
                    return;
                }
                var rect = row.getBoundingClientRect();
                var after = event.clientY > rect.top + rect.height / 2;
                tbody.insertBefore(dragged, after ? row.nextSibling : row);
            });

            tbody.addEventListener('drop', function (event) {
                event.preventDefault();
                renumber();
            });

            tbody.addEventListener('dragend', function () {
                if (dragged) {
                    dragged.classList.remove('dragging');
                }
                dragged = null;
                renumber();
            });
        })();
    </script>
@endpush
