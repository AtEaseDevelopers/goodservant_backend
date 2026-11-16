@include('partials.order_summary', [
    'order' => $salesOrder,
    'number' => $salesOrder->sono,
    'total' => $total ?? null,
    'source' => $salesOrder->is_portal_order
        ? '<span class="badge badge-info align-middle">' . e(__('detail.source_portal')) . '</span>'
        : '<span class="badge badge-light border align-middle">' . e(__('detail.source_admin')) . '</span>',
])
