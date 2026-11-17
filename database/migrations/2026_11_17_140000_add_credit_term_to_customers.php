<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AutoCount-owned credit term for a customer. `credit_term` keeps the debtor's
 * term verbatim as it reads in AutoCount ("30 DAYS", "C.O.D.", ...);
 * `credit_term_days` is the parsed net-day count for any date-driven use
 * (null when the term is not day-based). Distinct from `paymentterm`, which is
 * a Cash/Credit payment-method enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'credit_term')) {
                $table->string('credit_term')->nullable()->after('debtor_type');
            }
            if (!Schema::hasColumn('customers', 'credit_term_days')) {
                $table->integer('credit_term_days')->nullable()->after('credit_term');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'credit_term_days')) {
                $table->dropColumn('credit_term_days');
            }
            if (Schema::hasColumn('customers', 'credit_term')) {
                $table->dropColumn('credit_term');
            }
        });
    }
};
