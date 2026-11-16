<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Documents the drivers made without signal that need a look from the
 * office: saved as the phone made them, but something did not match
 * (renumbered, price changed, product inactive, no trip open at the time).
 * Also lists gaps in each driver's offline numbers,
 * which mean a document is still on a phone or the phone was lost.
 */
class OfflineReviewController extends Controller
{
    private const KINDS = [
        'invoice' => [Invoice::class, 'invoiceno', 'invoices.show', 'Invoice / Cash Sale'],
        'delivery_order' => [DeliveryOrder::class, 'dono', 'deliveryOrders.show', 'Delivery Order'],
        'sales_order' => [SalesOrder::class, 'sono', 'salesOrders.show', 'Sales Order'],
    ];

    public function index(Request $request)
    {
        $showDone = $request->boolean('done');
        $rows = collect();
        foreach (self::KINDS as $kind => [$model, $column, $route, $label]) {
            $query = $model::with('customer', 'driver')->whereNotNull('review_note');
            $showDone ? $query->whereNotNull('reviewed_at') : $query->whereNull('reviewed_at');
            foreach ($query->orderBy('id', 'desc')->limit(300)->get() as $doc) {
                $rows->push([
                    'kind' => $kind,
                    'label' => $label,
                    'id' => $doc->id,
                    'number' => $doc->{$column},
                    'renumbered_from' => $doc->renumbered_from,
                    'date' => $doc->getRawOriginal('date'),
                    'uploaded_at' => $doc->uploaded_at,
                    'customer' => optional($doc->customer)->company,
                    'driver' => optional($doc->driver)->name,
                    'note' => $doc->review_note,
                    'url' => route($route, encrypt($doc->id)),
                    'reviewed_at' => $doc->reviewed_at,
                    'reviewed_by' => $doc->reviewed_by,
                ]);
            }
        }
        $rows = $rows->sortByDesc('uploaded_at')->values();

        return view('offline_review.index', [
            'rows' => $rows,
            'gaps' => $this->gaps(),
            'showDone' => $showDone,
        ]);
    }

    public function done(Request $request, string $kind, $id)
    {
        abort_unless(isset(self::KINDS[$kind]), 404);
        $model = self::KINDS[$kind][0];
        $model::where('id', $id)->update([
            'reviewed_at' => now(),
            'reviewed_by' => optional(Auth::user())->name,
        ]);

        return redirect()->route('offlineReview.index')->with('status', 'Marked as checked.');
    }

    /**
     * Missing offline numbers per driver, type and month (this month and
     * the last), e.g. IV2611/BK07/A0003 when A0002 and A0004 exist.
     */
    private function gaps(): array
    {
        $gaps = [];
        $periods = [date('ym'), date('ym', strtotime('first day of -1 month'))];
        foreach (Driver::where('status', 1)->get() as $driver) {
            $code = $driver->documentCode();
            foreach (['IV' => Invoice::class, 'CS' => Invoice::class, 'DO' => DeliveryOrder::class, 'SO' => SalesOrder::class] as $prefix => $model) {
                $column = $model === Invoice::class ? 'invoiceno' : ($model === DeliveryOrder::class ? 'dono' : 'sono');
                foreach ($periods as $period) {
                    $stem = $prefix . $period . '/' . $code . '/A';
                    $used = $model::query()->where($column, 'like', $stem . '%')->pluck($column)
                        ->map(function ($n) use ($stem) { return (int) substr($n, strlen($stem)); })
                        ->filter()->unique()->sort()->values();
                    if ($used->isEmpty()) {
                        continue;
                    }
                    $missing = array_diff(range(1, $used->last()), $used->all());
                    if ($missing) {
                        $gaps[] = [
                            'driver' => $driver->name,
                            'numbers' => array_map(function ($n) use ($stem) { return $stem . sprintf('%04d', $n); }, array_values($missing)),
                            'last_upload' => $driver->offline_reported_at,
                            'pending' => (int) $driver->offline_pending,
                        ];
                    }
                }
            }
        }

        return $gaps;
    }
}
