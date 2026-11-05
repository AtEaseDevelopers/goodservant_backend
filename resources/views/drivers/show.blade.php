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
                                    <div class="mb-3 d-flex flex-wrap align-items-center">
                                        <input type="search" id="assign-search" class="form-control form-control-sm mr-2 mb-2" style="max-width: 280px;" placeholder="Search code, name, phone or address">
                                        <button type="button" class="btn btn-outline-secondary btn-sm mr-2 mb-2" id="sort-by-name">Sort by name</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mr-2 mb-2" id="sort-by-code">Sort by code</button>
                                        <span class="text-muted mb-2" id="assign-search-count"></span>
                                    </div>
                                    <p class="text-muted">Drag a row to change the order, then save. Search only hides other rows. Save still keeps the full order.</p>
                                    <p class="text-muted d-none" id="assign-search-empty">No customers match this search.</p>
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
                                                @php
                                                    $customer = $ass['customer'] ?? null;
                                                    if (empty($customer)) {
                                                        continue;
                                                    }
                                                @endphp
                                                <tr data-id="{{ $ass['id'] }}" data-code="{{ $customer['code'] ?? '' }}" data-name="{{ $customer['company'] ?? '' }}" data-search="{{ trim(($customer['code'] ?? '').' '.($customer['company'] ?? '').' '.($customer['phone'] ?? '').' '.($customer['address'] ?? '')) }}">
                                                    <td class="text-center"><span class="drag-handle" draggable="true">☰</span></td>
                                                    <td>{{ $customer['code'] ?? '-' }}</td>
                                                    <td>{{ $customer['company'] ?? '-' }}</td>
                                                    <td>{{ $customer['phone'] ?? '-' }}</td>
                                                    <td class="truncate">{{ $customer['address'] ?? '-' }}</td>
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

            var savedOrder = rows().map(function (row) {
                return row.getAttribute('data-id');
            }).join(',');
            var allowLeave = false;

            function sequenceChanged() {
                var order = rows().map(function (row) {
                    return row.getAttribute('data-id');
                }).join(',');
                return order !== savedOrder;
            }

            function confirmLeave() {
                if (!sequenceChanged()) {
                    return true;
                }
                return confirm('The customer sequence has not been saved. Leave this page?');
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

            var search = document.getElementById('assign-search');
            var searchCount = document.getElementById('assign-search-count');
            var searchEmpty = document.getElementById('assign-search-empty');

            function applySearch() {
                var query = (search.value || '').trim().toLowerCase();
                var shown = 0;
                var list = rows();
                list.forEach(function (row) {
                    var haystack = (row.getAttribute('data-search') || '').toLowerCase();
                    var match = query === '' || haystack.indexOf(query) !== -1;
                    row.style.display = match ? '' : 'none';
                    if (match) {
                        shown++;
                    }
                });
                if (searchCount) {
                    searchCount.textContent = query === '' ? '' : (shown + ' of ' + list.length);
                }
                if (searchEmpty) {
                    searchEmpty.classList.toggle('d-none', shown !== 0);
                }
            }

            search.addEventListener('input', applySearch);

            var scrollSpeed = 0;
            var scrolling = false;
            var lastX = 0;
            var lastY = 0;

            function scrollBox() {
                var el = tbody.parentElement;
                while (el && el !== document.body) {
                    var overflow = window.getComputedStyle(el).overflowY;
                    if ((overflow === 'auto' || overflow === 'scroll') && el.scrollHeight > el.clientHeight + 4) {
                        return el;
                    }
                    el = el.parentElement;
                }
                return document.scrollingElement || document.documentElement;
            }

            function placeRow(x, y) {
                var under = document.elementFromPoint(x, y);
                if (!under || !under.closest) {
                    return;
                }
                var row = under.closest('tr');
                if (!row || row === dragged || row.parentNode !== tbody || row.style.display === 'none') {
                    return;
                }
                var rect = row.getBoundingClientRect();
                var after = y > rect.top + rect.height / 2;
                tbody.insertBefore(dragged, after ? row.nextSibling : row);
            }

            function updateScroll(y) {
                var scroller = scrollBox();
                var edge = 80;
                var box = scroller === document.documentElement || scroller === document.scrollingElement
                    ? { top: 0, bottom: window.innerHeight }
                    : scroller.getBoundingClientRect();
                if (y < box.top + edge) {
                    scrollSpeed = -Math.max(4, Math.ceil((box.top + edge - y) / edge * 22));
                } else if (y > box.bottom - edge) {
                    scrollSpeed = Math.max(4, Math.ceil((y - (box.bottom - edge)) / edge * 22));
                } else {
                    scrollSpeed = 0;
                }
                if (scrollSpeed !== 0 && !scrolling) {
                    scrolling = true;
                    requestAnimationFrame(function tick() {
                        if (!dragged || scrollSpeed === 0) {
                            scrolling = false;
                            return;
                        }
                        scrollBox().scrollTop += scrollSpeed;
                        placeRow(lastX, lastY);
                        requestAnimationFrame(tick);
                    });
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
                event.dataTransfer.setData('text/plain', dragged.getAttribute('data-id'));
            });

            document.addEventListener('dragover', function (event) {
                if (!dragged) {
                    return;
                }
                event.preventDefault();
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
                lastX = event.clientX;
                lastY = event.clientY;
                updateScroll(event.clientY);
                placeRow(event.clientX, event.clientY);
            });

            document.addEventListener('drop', function (event) {
                if (!dragged) {
                    return;
                }
                event.preventDefault();
                scrollSpeed = 0;
                renumber();
            });

            document.addEventListener('dragend', function () {
                scrollSpeed = 0;
                if (dragged) {
                    dragged.classList.remove('dragging');
                }
                dragged = null;
                renumber();
            });

            form.addEventListener('submit', function () {
                allowLeave = true;
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

            window.addEventListener('beforeunload', function (event) {
                if (allowLeave || !sequenceChanged()) {
                    return;
                }
                event.preventDefault();
                event.returnValue = '';
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest('a[href]');
                if (!link || link.target === '_blank') {
                    return;
                }
                var href = link.getAttribute('href') || '';
                if (href === '' || href.charAt(0) === '#') {
                    return;
                }
                if (!confirmLeave()) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                allowLeave = true;
            }, true);

            document.addEventListener('submit', function (event) {
                if (event.target === form) {
                    return;
                }
                if (!confirmLeave()) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                allowLeave = true;
            }, true);
        })();
    </script>
@endpush