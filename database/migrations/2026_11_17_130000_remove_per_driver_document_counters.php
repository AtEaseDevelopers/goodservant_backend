<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Invoice, cash sale and DO numbers are now the driver's highest number of
 * the month + 1, read from the documents (Code::nextDocumentNumber), so the
 * per-driver counters (ivrunningnumber:18, csrunningnumber:23,
 * dorunningnumber:28 ...) are no longer used. Only those rows go; the shared
 * rows (invoicerunningnumber, cashsalesrunningnumber, dorunningnumber,
 * sorunningnumber) and every other code stay.
 */
class RemovePerDriverDocumentCounters extends Migration
{
    public function up()
    {
        DB::table('codes')->where('code', 'regexp', '^(iv|cs|do)runningnumber:[0-9]+$')->delete();
    }

    public function down()
    {
        // Not restored: the numbers are read from the documents now.
    }
}
