@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('drivers.index') }}">{{ __('drivers.drivers') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('drivers.detail') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('drivers.detail') }}</strong>
                                  <a href="{{ route('drivers.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('drivers.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>{{ __('drivers.customer') }}</strong>
                                <a class="pull-right" href="{{ route('drivers.assign', Crypt::encrypt($id)) }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             </div>
                             <div class="card-body">
                                @if(count($assign) == 0)
                                    <p class="text-muted">{{ __('drivers.no_matching_records_found') }}</p>
                                @else
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="sort-by-name">Sort by name</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="sort-by-code">Sort by code</button>
                                        <span class="text-muted ml-2">Drag a row to change the order, then save.</span>
                                    </div>
                                    <table class="table table-striped table-bordered" id="assign-table" width="100%">
                                        <thead>
                                            <tr>
                                                <th width="40"></th>
                                                <th>{{ __('drivers.code') }}</th>
                                                <th>{{ __('drivers.company') }}</th>
                                                <th>{{ __('drivers.phone') }}</th>
                                                <th>{{ __('drivers.address') }}</th>
                                                <th width="90">{{ __('drivers.sequence') }}</th>
                                                <th width="80">{{ __('drivers.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($assign as $i => $ass)
                                                <tr data-id="{{ $ass['id'] }}" data-code="{{ $ass['customer']['code'] ?? '' }}" data-name="{{ $ass['customer']['company'] ?? '' }}">
                                                    <td class="text-center"><span class="drag-handle" draggable="true">☰</span></td>
                                                    <td>{{ $ass['customer']['code'] ?? '-' }}</td>
                                                    <td>{{ $ass['customer']['company'] ?? '-' }}</td>
                                                    <td>{{ $ass['customer']['phone'] ?? '-' }}</td>
                                                    <td class="truncate">{{ $ass['customer']['address'] ?? '-' }}</td>
                                                    <td class="sequence-cell">{{ $i + 1 }}</td>
                                                    <td>
                                                    {!! Form::open(['route' => ['drivers.deleteassign', Crypt::encrypt($ass['id'])], 'method' => 'delete']) !!}
                                                        <div class='btn-group'>
                                                            {!! Form::button('<i class="fa fa-trash"></i>', [
                                                                'type' => 'submit',
                                                                'class' => 'btn btn-ghost-danger',
                                                                'onclick' => "return confirm('Are you sure to delete the Assign?')"
                                                            ]) !!}
                                                        </div>
                                                    {!! Form::close() !!}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <form method="POST" action="{{ route('drivers.assign.sequence', Crypt::encrypt($id)) }}" id="assign-sequence-form">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Save sequence
                                        </button>
                                    </form>
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
        #assign-table .drag-handle { cursor: move; color: #999; font-size: 18px; user-select: none; }
        #assign-table tr.dragging { opacity: 0.5; }
    </style>
@endpush

@push('scripts')
    <script>
        HideLoad();
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[1].click();
            }
        });

        (function () {
            var tbody = document.querySelector('#assign-table tbody');
            var form = document.getElementById('assign-sequence-form');
            if (!tbody || !form) {
                return;
            }
            var dragged = null;
            var sortState = { key: '', dir: 'asc' };

            function rows() {
                return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-id]'));
            }

            function renumber() {
                rows().forEach(function (row, index) {
                    var cell = row.querySelector('.sequence-cell');
                    if (cell) {
                        cell.textContent = String(index + 1);
                    }
                });
            }

            function sortBy(key) {
                var dir = (sortState.key === key && sortState.dir === 'asc') ? 'desc' : 'asc';
                sortState = { key: key, dir: dir };
                var ordered = rows().sort(function (a, b) {
                    var av = a.getAttribute(key) || '';
                    var bv = b.getAttribute(key) || '';
                    var compared = av.localeCompare(bv, undefined, { sensitivity: 'base', numeric: true });
                    return dir === 'asc' ? compared : -compared;
                });
                ordered.forEach(function (row) {
                    tbody.appendChild(row);
                });
                renumber();
                document.getElementById('sort-by-name').textContent = key === 'data-name'
                    ? (dir === 'asc' ? 'Name A–Z' : 'Name Z–A')
                    : 'Sort by name';
                document.getElementById('sort-by-code').textContent = key === 'data-code'
                    ? (dir === 'asc' ? 'Code A–Z' : 'Code Z–A')
                    : 'Sort by code';
            }

            document.getElementById('sort-by-name').addEventListener('click', function () {
                sortBy('data-name');
            });
            document.getElementById('sort-by-code').addEventListener('click', function () {
                sortBy('data-code');
            });

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
                event.dataTransfer.setData('text/plain', dragged.getAttribute('data-id'));
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

            form.addEventListener('submit', function () {
                form.querySelectorAll('input[name="assigns[]"]').forEach(function (input) {
                    input.remove();
                });
                rows().forEach(function (row) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'assigns[]';
                    input.value = row.getAttribute('data-id');
                    form.appendChild(input);
                });
            });
        })();
    </script>
@endpush