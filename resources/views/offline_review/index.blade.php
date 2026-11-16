@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Offline Review</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="card">
                <div class="card-header">
                    <i class="fa fa-exclamation-triangle"></i>
                    Offline documents to check
                    <span class="pull-right">
                        @if ($showDone)
                            <a href="{{ route('offlineReview.index') }}">Show to check</a>
                        @else
                            <a href="{{ route('offlineReview.index', ['done' => 1]) }}">Show checked</a>
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Made on the driver's phone without signal and already printed for the customer.
                        They are saved as the phone made them; the note says what did not match.
                    </p>
                    @if ($rows->isEmpty())
                        <p class="mb-0">Nothing {{ $showDone ? 'checked yet' : 'to check' }}.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Uploaded</th>
                                        <th>Driver</th>
                                        <th>Customer</th>
                                        <th>What to check</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        <tr>
                                            <td>
                                                <a href="{{ $row['url'] }}">{{ $row['number'] }}</a>
                                                @if ($row['renumbered_from'])
                                                    <br><small class="text-danger">printed as {{ $row['renumbered_from'] }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $row['label'] }}</td>
                                            <td>{{ $row['date'] ? date('d-m-Y H:i', strtotime($row['date'])) : '-' }}</td>
                                            <td>{{ $row['uploaded_at'] ? date('d-m-Y H:i', strtotime($row['uploaded_at'])) : '-' }}</td>
                                            <td>{{ $row['driver'] ?? '-' }}</td>
                                            <td>{{ $row['customer'] ?? '-' }}</td>
                                            <td style="white-space: pre-line;">{{ $row['note'] }}</td>
                                            <td>
                                                @if ($row['reviewed_at'])
                                                    <small>Checked {{ date('d-m-Y H:i', strtotime($row['reviewed_at'])) }}<br>{{ $row['reviewed_by'] }}</small>
                                                @else
                                                    <form method="POST" action="{{ route('offlineReview.done', [$row['kind'], $row['id']]) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-success">Mark checked</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fa fa-mobile"></i>
                    Offline numbers not uploaded yet
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Numbers skipped in a driver's offline series (this month and last month). The document is
                        usually still on the driver's phone waiting for signal. If the phone was lost or reset,
                        enter it from the printed copy.
                    </p>
                    @if (empty($gaps))
                        <p class="mb-0">No missing numbers.</p>
                    @else
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Driver</th>
                                    <th>Missing numbers</th>
                                    <th>Phone reports waiting</th>
                                    <th>Last report</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gaps as $gap)
                                    <tr>
                                        <td>{{ $gap['driver'] }}</td>
                                        <td>{{ implode(', ', $gap['numbers']) }}</td>
                                        <td>{{ $gap['pending'] }}</td>
                                        <td>{{ $gap['last_upload'] ? date('d-m-Y H:i', strtotime($gap['last_upload'])) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
