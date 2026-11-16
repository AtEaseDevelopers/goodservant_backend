@include('partials.order_summary', [
    'order' => $invoice,
    'number' => $invoice->invoiceno,
    'total' => $total ?? null,
])
