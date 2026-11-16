{{-- Drawn from resources/receipts/sales_order.json (the same layout the driver app prints offline). Edit the JSON, not this file. --}}
{!! \App\Support\ReceiptTemplate::html('sales_order', \App\Support\ReceiptTemplate::salesOrderData($salesOrder)) !!}
