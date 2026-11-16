@include('partials.order_summary', [
    'order' => $deliveryOrder,
    'number' => $deliveryOrder->dono,
    'total' => $total ?? null,
])
