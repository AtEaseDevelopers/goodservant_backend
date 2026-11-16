{{-- Drawn from resources/receipts/delivery_order.json (the same layout the driver app prints offline). Edit the JSON, not this file. --}}
{!! \App\Support\ReceiptTemplate::html('delivery_order', \App\Support\ReceiptTemplate::deliveryOrderData($deliveryOrder)) !!}
