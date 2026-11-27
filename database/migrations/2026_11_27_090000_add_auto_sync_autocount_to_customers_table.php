<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When `auto_sync_autocount` is on, invoices for this customer are queued for
 * AutoCount sync automatically on creation, and re-queued whenever they are
 * edited in the web admin (see Invoice::booted() and InvoiceController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'auto_sync_autocount')) {
                $table->boolean('auto_sync_autocount')->default(false)->after('credit_term_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'auto_sync_autocount')) {
                $table->dropColumn('auto_sync_autocount');
            }
        });
    }
};
