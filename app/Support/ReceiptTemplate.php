<?php

namespace App\Support;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\SalesOrder;

/**
 * One receipt layout for both the server and the driver's phone.
 *
 * The layouts live in resources/receipts/*.json as a short list of blocks
 * (text, row of columns, rule, space, logo, a repeat over the items, and an
 * "if"). The server draws them to HTML for Dompdf here; the app downloads
 * the same files (GET /api/v1/driver/offline/setup) and draws them on the
 * phone, so a receipt printed without signal looks like the server one.
 * To change a receipt, edit the JSON: phones pick it up on their next sync.
 *
 * Block reference:
 * Top level: "size" (default text size, px), "margin" [top, right, bottom, left].
 *
 *   {"logo": true, "height": 106, "width": 168}
 *   {"text": "Hi {{doc.number}}", "align": "left|center|right", "size": 14,
 *    "bold": false, "top": 0, "bottom": 0, "border_top": "dashed",
 *    "cjk": true}   (cjk: the font that can show Chinese names)
 *   {"row": [{"text": "...", "width": 42, "align": "right", "bold": true}], "size": 14, "pad": 8}
 *   {"rule": "solid|dashed", "thickness": 3, "width": 100}
 *   {"space": 6}
 *   {"each": "items", "blocks": [...]}      (inside: {{name}}, {{qty}}...)
 *   {"if": "doc.remark", "blocks": [...]}    (shown when the value is not empty)
 *
 * The data (all values already formatted as text) comes from
 * invoiceData(), deliveryOrderData() and salesOrderData(); the app builds
 * the same keys for an offline document.
 */
class ReceiptTemplate
{
    public const TYPES = ['invoice', 'delivery_order', 'sales_order'];

    /** Default text size of the template being drawn ("size" at the top). */
    private static $size = 14;

    public const PAYMENT_TYPES = [
        1 => 'Cash',
        2 => 'Credit',
        3 => 'Online Banking (QR Code)',
        4 => 'E-wallet',
        5 => 'Cheque',
    ];

    public static function load(string $type): array
    {
        $path = resource_path('receipts/' . $type . '.json');
        $template = json_decode(file_get_contents($path), true);
        if (!is_array($template) || !isset($template['blocks'])) {
            throw new \RuntimeException("Receipt template $type is not valid JSON");
        }

        return $template;
    }

    /** Changes whenever any template or the company header changes. */
    public static function version(): string
    {
        $parts = [json_encode(static::company())];
        foreach (static::TYPES as $type) {
            $parts[] = file_get_contents(resource_path('receipts/' . $type . '.json'));
        }

        return substr(md5(implode('|', $parts)), 0, 12);
    }

    public static function company(): array
    {
        return [
            'name' => (string) config('invoice.name'),
            'ssm' => (string) config('invoice.ssm'),
            'address1' => (string) config('invoice.address1'),
            'address2' => (string) config('invoice.address2'),
            'address3' => (string) config('invoice.address3'),
            'phone' => (string) config('invoice.phone'),
        ];
    }

    // ------------------------------------------------------------------ data

    public static function invoiceData(Invoice $invoice): array
    {
        $discountProductId = InvoiceDiscount::productId();
        $items = [];
        $subtotal = 0;
        $discount = 0;
        foreach ($invoice->invoicedetail as $line) {
            if ((int) $line->product_id === (int) $discountProductId) {
                $discount += abs($line->totalprice);
                continue;
            }
            $subtotal += $line->totalprice;
            $items[] = static::item(optional($line->product)->name, $line->quantity, $line->price, $line->totalprice);
        }
        $total = $subtotal - $discount;

        $cashReceived = optional($invoice->invoicepayment->where('type', 1)->whereNotNull('cash_received')->first())->cash_received;
        $hasChange = !empty($cashReceived) && $cashReceived >= $total;
        $created = $invoice->getRawOriginal('created_at');
        $rawDate = $invoice->getRawOriginal('date');

        return [
            'company' => static::company(),
            'doc' => [
                'title' => (int) $invoice->paymentterm === 2 ? 'INVOICE' : 'CASH SALE',
                'number' => (string) $invoice->invoiceno,
                'date' => date('d-m-Y', strtotime($rawDate)),
                'datetime' => date('d-m-Y H:i:s', strtotime($rawDate)),
                'payment_type' => static::PAYMENT_TYPES[(int) $invoice->paymentterm] ?? '-',
                'is_cheque' => (int) $invoice->paymentterm === 5,
                'cheque_no' => (string) $invoice->chequeno,
                'customer_name' => optional($invoice->customer)->company ?? '-',
                'customer_address' => optional($invoice->customer)->address ?? '-',
                'salesperson' => optional($invoice->driver)->name ?? '-',
                'subtotal' => static::money($subtotal),
                'discount' => static::money($discount),
                'has_discount' => $discount > 0,
                'total' => static::money($total),
                'cash_received' => static::money((float) $cashReceived),
                'change' => static::money((float) $cashReceived - $total),
                'has_change' => $hasChange,
                'printed_at' => $created ? date('d/m/Y H:i', strtotime($created)) : date('d/m/Y', strtotime($rawDate)),
                'remark' => (string) $invoice->remark,
            ],
            'items' => $items,
        ];
    }

    public static function deliveryOrderData(DeliveryOrder $do): array
    {
        $items = [];
        $total = 0;
        foreach ($do->deliveryorderdetail as $line) {
            $total += $line->totalprice;
            $items[] = static::item(optional($line->product)->name, $line->quantity, $line->price, $line->totalprice);
        }
        $rawDate = $do->getRawOriginal('date');

        return [
            'company' => static::company(),
            'doc' => [
                'title' => 'DELIVERY ORDER',
                'number' => (string) $do->dono,
                'date' => date('d-m-Y', strtotime($rawDate)),
                'datetime' => date('d-m-Y H:i:s', strtotime($rawDate)),
                'payment_type' => static::PAYMENT_TYPES[(int) $do->paymentterm] ?? '-',
                'is_cheque' => (int) $do->paymentterm === 5,
                'cheque_no' => (string) $do->chequeno,
                'customer_name' => optional($do->customer)->company ?? '-',
                'customer_address' => optional($do->customer)->address ?? '-',
                'salesperson' => optional($do->driver)->name ?? '-',
                'total' => static::money($total),
                'remark' => (string) $do->remark,
            ],
            'items' => $items,
        ];
    }

    public static function salesOrderData(SalesOrder $so): array
    {
        $items = [];
        $total = 0;
        foreach ($so->salesorderdetail as $line) {
            $total += $line->totalprice;
            $items[] = static::item(optional($line->product)->name, $line->quantity, $line->price, $line->totalprice);
        }
        $rawDate = $so->getRawOriginal('date');

        return [
            'company' => static::company(),
            'doc' => [
                'title' => 'SALES ORDER',
                'number' => (string) $so->sono,
                'date' => date('d-m-Y', strtotime($rawDate)),
                'datetime' => date('d-m-Y H:i:s', strtotime($rawDate)),
                'customer_name' => optional($so->customer)->company ?? '-',
                'customer_address' => optional($so->customer)->address ?? '-',
                'salesperson' => optional($so->driver)->name ?? '-',
                'total' => static::money($total),
                'remark' => (string) $so->remark,
            ],
            'items' => $items,
        ];
    }

    private static function item($name, $qty, $price, $lineTotal): array
    {
        $name = trim((string) $name);

        return [
            'name' => $name !== '' ? $name : '-',
            'qty' => number_format((float) $qty, 3),
            'qty_plain' => rtrim(rtrim(number_format((float) $qty, 3, '.', ''), '0'), '.'),
            'price' => static::money($price),
            'line_total' => static::money($lineTotal),
        ];
    }

    private static function money($value): string
    {
        return number_format((float) $value, 2);
    }

    // ------------------------------------------------------------------ HTML

    public static function html(string $type, array $data): string
    {
        $template = static::load($type);
        static::$size = (int) ($template['size'] ?? 14);
        $body = static::blocks($template['blocks'], $data, $data);
        $margin = array_map('intval', $template['margin'] ?? [6, 12, 8, 12]);

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
            . view('pdf.cjk_font')->render()
            . ' @page { margin: ' . implode('px ', $margin) . 'px; }'
            . ' body { font-size: 14px; margin: 0; }'
            . ' table { width: 100%; border-collapse: collapse; table-layout: fixed; }'
            . ' td { font-size: inherit; padding: 1px; vertical-align: middle; }'
            . ' p { margin: 0; }'
            . ' .zh { font-family: notosanssc, sans-serif; font-weight: normal; }'
            . '</style></head><body>' . $body . '</body></html>';
    }

    private static function blocks(array $blocks, array $data, array $scope): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $html .= static::block($block, $data, $scope);
        }

        return $html;
    }

    private static function block(array $b, array $data, array $scope): string
    {
        if (!empty($b['logo'])) {
            $path = public_path('receipts_logo.png');
            if (!is_file($path)) {
                return '';
            }

            $size = 'height:' . (int) ($b['height'] ?? 106) . 'px;' . (isset($b['width']) ? 'width:' . (int) $b['width'] . 'px;' : 'width:auto;');

            return '<p style="text-align:center;"><img style="' . $size . 'margin-bottom:' . (int) ($b['bottom'] ?? 2) . 'px;" src="data:image/png;base64,'
                . base64_encode(file_get_contents($path)) . '"></p>';
        }

        if (isset($b['text'])) {
            $style = static::textStyle($b, $b['size'] ?? static::$size);
            if (($b['border_top'] ?? null) !== null) {
                $style .= 'border-top:1px ' . ($b['border_top'] === 'dashed' ? 'dashed' : 'solid') . ' #000;padding-top:8px;';
            }

            return '<p style="' . $style . '">' . static::fill($b['text'], $scope, $data, !empty($b['cjk'])) . '</p>';
        }

        if (isset($b['row'])) {
            $cells = '';
            $size = $b['size'] ?? static::$size;
            $pad = isset($b['pad']) ? ' style="padding-top:' . (int) $b['pad'] . 'px;padding-bottom:' . (int) $b['pad'] . 'px;"' : '';
            foreach ($b['row'] as $cell) {
                $width = isset($cell['width']) ? ' width="' . (int) $cell['width'] . '%"' : '';
                $cells .= '<td' . $width . $pad . '><p style="' . static::textStyle($cell, $cell['size'] ?? $size) . '">'
                    . static::fill($cell['text'] ?? '', $scope, $data, !empty($cell['cjk'])) . '</p></td>';
            }

            return '<table style="font-size:' . (int) $size . 'px;"><tr>' . $cells . '</tr></table>';
        }

        if (isset($b['rule'])) {
            $thickness = (int) ($b['thickness'] ?? 1);
            $width = (int) ($b['width'] ?? 100);
            $style = $b['rule'] === 'dashed' ? 'dashed' : 'solid';

            return '<div style="border-top:' . $thickness . 'px ' . $style . ' #000;width:' . $width . '%;margin:14px 0 12px;height:0;line-height:0;font-size:0;"></div>';
        }

        if (isset($b['space'])) {
            return '<div style="height:' . (int) $b['space'] . 'px;"></div>';
        }

        if (isset($b['each'])) {
            $html = '';
            foreach ((array) static::lookup($b['each'], $scope, $data) as $item) {
                $html .= static::blocks($b['blocks'] ?? [], $data, is_array($item) ? $item : []);
            }

            return $html;
        }

        if (isset($b['if'])) {
            return static::truthy(static::lookup($b['if'], $scope, $data))
                ? static::blocks($b['blocks'] ?? [], $data, $scope)
                : '';
        }

        return '';
    }

    private static function textStyle(array $b, $size): string
    {
        $align = in_array($b['align'] ?? 'left', ['left', 'center', 'right'], true) ? ($b['align'] ?? 'left') : 'left';

        return 'font-size:' . (int) $size . 'px;text-align:' . $align . ';'
            . 'font-weight:' . (!empty($b['bold']) ? 'bold' : 'normal') . ';'
            . (isset($b['top']) ? 'margin-top:' . (int) $b['top'] . 'px;' : '')
            . (isset($b['bottom']) ? 'padding-bottom:' . (int) $b['bottom'] . 'px;' : '');
    }

    /** Replace {{key}} with escaped values; Chinese text gets the CJK font. */
    private static function fill(string $text, array $scope, array $data, bool $cjk = false): string
    {
        $out = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function ($m) use ($scope, $data) {
            $value = static::lookup($m[1], $scope, $data);
            return is_scalar($value) ? (string) $value : '';
        }, $text);

        $escaped = e($out);
        if ($cjk || preg_match('/[\x{2E80}-\x{9FFF}\x{F900}-\x{FAFF}\x{FF00}-\x{FFEF}]/u', $out)) {
            return '<span class="zh">' . $escaped . '</span>';
        }

        return $escaped;
    }

    /** Dotted key, looked up in the current item first, then in the data. */
    private static function lookup(string $key, array $scope, array $data)
    {
        foreach ([$scope, $data] as $source) {
            $value = $source;
            $found = true;
            foreach (explode('.', $key) as $part) {
                if (is_array($value) && array_key_exists($part, $value)) {
                    $value = $value[$part];
                } else {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                return $value;
            }
        }

        return null;
    }

    private static function truthy($value): bool
    {
        return !($value === null || $value === false || $value === '' || $value === 0 || $value === '0' || $value === []);
    }
}
