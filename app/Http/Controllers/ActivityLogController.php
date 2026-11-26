<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Portal activity log viewer. Not in the menu: open /activity-logs directly
 * (users with the "user" permission only).
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-d', strtotime('-6 days')));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        $logs = ActivityLog::query()
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->when($request->filled('user'), fn ($q) => $q->where('user_name', 'like', '%' . $request->user . '%'))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->event))
            ->when($request->filled('subject'), fn ($q) => $q->where('subject_type', $request->subject))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(function ($w) use ($term) {
                    $w->where('description', 'like', $term)->orWhere('url', 'like', $term)
                        ->orWhere('old_values', 'like', $term)->orWhere('new_values', 'like', $term);
                });
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('activity_logs.index', [
            'logs' => $logs,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'subjects' => ActivityLog::whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type'),
        ]);
    }
}
