{{-- Drawn from resources/receipts/invoice.json (the same layout the driver app prints offline). Edit the JSON, not this file. --}}
{!! \App\Support\ReceiptTemplate::html('invoice', \App\Support\ReceiptTemplate::invoiceData($invoice)) !!}
