@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Activity Log</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            <div class="card">
                <div class="card-header"><i class="fa fa-history"></i> Activity Log</div>
                <div class="card-body">
                    <form method="GET" action="{{ route('activity-logs.index') }}">
                        <div class="row">
                            <div class="form-group col-sm-2">
                                <label>From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            </div>
                            <div class="form-group col-sm-2">
                                <label>To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                            </div>
                            <div class="form-group col-sm-2">
                                <label>User</label>
                                <input type="text" name="user" class="form-control" value="{{ request('user') }}" placeholder="Name">
                            </div>
                            <div class="form-group col-sm-2">
                                <label>Action</label>
                                <select name="event" class="form-control">
                                    <option value="">All</option>
                                    @foreach(['request' => 'Save / delete request', 'created' => 'Created', 'updated' => 'Changed', 'deleted' => 'Deleted', 'restored' => 'Restored', 'login' => 'Login', 'logout' => 'Logout', 'login_failed' => 'Failed login'] as $value => $label)
                                        <option value="{{ $value }}" {{ request('event') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label>Record type</label>
                                <select name="subject" class="form-control">
                                    <option value="">All</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject }}" {{ request('subject') === $subject ? 'selected' : '' }}>{{ $subject }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-2">
                                <label>Search</label>
                                <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="e.g. IV2611/BK07/0001">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mb-3"><i class="fa fa-search"></i> Show</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 140px;">Time</th>
                                    <th style="width: 150px;">User</th>
                                    <th style="width: 90px;">Action</th>
                                    <th>What</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d-m-Y H:i:s') }}</td>
                                        <td>{{ $log->user_name ?? '-' }}@if($log->user_type === 'customer') <span class="text-muted">(customer)</span>@endif<br><small class="text-muted">{{ $log->ip_address }}</small></td>
                                        <td>{{ $log->event }}@if($log->status) <small class="text-muted">{{ $log->status }}</small>@endif</td>
                                        <td>{{ $log->description }}<br><small class="text-muted text-break">{{ $log->method }} {{ \Illuminate\Support\Str::limit($log->url, 120) }}</small></td>
                                        <td style="max-width: 520px;">
                                            @if($log->old_values || $log->new_values)
                                                <details>
                                                    <summary>Show</summary>
                                                    @if($log->old_values)
                                                        <div class="small"><strong>Before</strong><pre class="mb-1" style="white-space: pre-wrap;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                                    @endif
                                                    @if($log->new_values)
                                                        <div class="small"><strong>{{ $log->event === 'request' ? 'Sent' : 'After' }}</strong><pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                                    @endif
                                                </details>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">Nothing logged for this filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    HideLoad();
});
</script>
@endpush
