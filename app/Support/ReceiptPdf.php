<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;
use Throwable;

/**
 * Sizes a narrow receipt PDF (invoice, SO, DO, payment) to its content, so
 * it is always one page: the app shows one long page the driver scrolls,
 * and the receipt printer prints it in one piece.
 *
 * The page height used to be guessed from the number of lines, which ran
 * short when long product names wrapped and split the receipt in two.
 */
class ReceiptPdf
{
    /** Tall enough for any receipt; the probe render only measures. */
    private const PROBE_HEIGHT = 14400;

    /** Room below the last line: the page's bottom margin plus a little. */
    private const BOTTOM_ROOM = 30;

    /**
     * @param callable $make   function (float $height): PDF, the configured receipt
     * @param float    $guess  the old estimated height, used if measuring fails
     */
    public static function fit(callable $make, float $guess): PDF
    {
        try {
            $probe = $make(self::PROBE_HEIGHT);
            $dompdf = $probe->getDomPDF();
            $bottom = 0;
            $dompdf->setCallbacks(['measure' => [
                'event' => 'end_frame',
                'f' => function ($info) use (&$bottom) {
                    $frame = is_array($info) ? ($info['frame'] ?? null) : $info;
                    if ($frame === null) {
                        return;
                    }
                    $tag = $frame->get_node()->nodeName;
                    if (in_array($tag, ['html', 'body', '#document'], true)) {
                        return;
                    }
                    $box = $frame->get_border_box();
                    $bottom = max($bottom, (float) $box['y'] + (float) $box['h']);
                },
            ]]);
            $dompdf->render();
            if ($bottom > 0 && $dompdf->getCanvas()->get_page_count() === 1) {
                return $make(ceil($bottom) + self::BOTTOM_ROOM);
            }
        } catch (Throwable $e) {
            // Fall back to the estimate below.
        }

        return $make($guess);
    }
}
