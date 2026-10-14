<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resets the OMS for a fresh start: wipes products, customers, assigns and all
 * transactional data (orders, deliveries, invoices, trips, tasks, inventory,
 * driver finance, e-invoices, logs) while PRESERVING configuration and the
 * reference data the operation is built on — users/roles/permissions, reports,
 * customer groups + running-number codes, and master data (product types,
 * pricing tiers, vendors, companies, locations, lorries, agents, supervisors,
 * drivers, kelindans, languages/translations, commission-by-vendor rates).
 *
 * products/customers/special_prices/product_prices/focs are wiped because they
 * are re-populated from AutoCount; the running-number counters are reset to 0
 * so freshly issued SO/DO/invoice numbers start at /0001 again.
 *
 * TRUNCATE_TABLES is an explicit allowlist — any table NOT listed here is left
 * untouched, so a newly added table is never silently wiped. KEEP_GUARD is a
 * defence-in-depth safety net: the command aborts if anything on it ever ends
 * up in the truncate set.
 *
 * Use --dry-run to print exactly what would be wiped/reset (with live row
 * counts) without writing anything.
 */
class ResetTransactionalData extends Command
{
    protected $signature = 'snoodle:reset-db {--dry-run : Show what would be wiped/reset without writing anything}
                                              {--force : Skip the interactive confirmation}';

    protected $description = 'Reset the OMS: wipe products, customers, assigns + all transactional data; keep users, roles, reports, customer groups and master data';

    /**
     * Tables whose every row is deleted. Explicit allowlist — anything not here
     * is preserved.
     */
    public const TRUNCATE_TABLES = [
        // Requested: re-synced from AutoCount
        'products',
        'customers',
        'assigns',
        // Product/customer-dependent pricing (orphaned by the wipe, re-synced)
        'product_prices',
        'special_prices',
        'focs',
        // Sales orders / delivery orders / invoices
        'sales_orders',
        'sales_order_details',
        'deliveryorders',
        'deliveryorder_details',
        'arc_delivery_orders',
        'invoices',
        'invoice_details',
        'invoice_payments',
        // Trips, tasks, inventory movement
        'trips',
        'trip_inventory_balances',
        'tasks',
        'task_transfers',
        'inventory_balances',
        'inventory_transactions',
        'inventory_transfers',
        // Driver finance / ops (transactional, not the driver master)
        'advances',
        'loans',
        'loanpayments',
        'claims',
        'compounds',
        'bonus',
        'paymentdetails',
        'driver_location',
        'servicedetails',
        // Accounting / e-invoice
        'einvoices',
        'consolidated_einvoices',
        'debit_notes',
        'credit_notes',
        // Logs / attachments
        'payment_attachments',
        'mobile_error_logs',
        'api_logs',
    ];

    /**
     * Must NEVER be truncated. If any of these ever appears in TRUNCATE_TABLES
     * the command refuses to run. Not exhaustive of everything kept (anything
     * absent from TRUNCATE_TABLES is kept) — just the load-bearing data whose
     * accidental loss we explicitly guard against.
     */
    public const KEEP_GUARD = [
        'users', 'roles', 'permissions',
        'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'user_has_roles',
        'reports', 'reportdetails',
        'codes', 'product_price_categories',
        'product_types', 'vendors', 'companies', 'locations', 'lorries',
        'agents', 'supervisors', 'drivers', 'kelindans',
        'items', 'prices', 'commissionbyvendors', 'saveviews',
        'languages', 'language_translations', 'mobile_translations', 'mobile_translation_versions',
        'migrations',
    ];

    /**
     * SQL LIKE pattern for the running-number rows in `codes` whose counter is
     * reset to 0 (so the next document number restarts at /0001). The other
     * `codes` rows — customer groups, commission rates, app version — are left
     * untouched.
     */
    public const RUNNING_NUMBER_CODE_LIKE = '%runningnumber';

    public function handle(): int
    {
        $collision = array_intersect(self::TRUNCATE_TABLES, self::KEEP_GUARD);
        if (! empty($collision)) {
            $this->error('Safety guard tripped: these tables are both kept and truncated: ' . implode(', ', $collision));
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        // Only act on tables that actually exist (schema has drifted before).
        $targets = [];
        foreach (self::TRUNCATE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $targets[$table] = DB::table($table)->count();
            }
        }

        $runningNumbers = [];
        if (Schema::hasTable('codes')) {
            $runningNumbers = DB::table('codes')
                ->where('code', 'like', self::RUNNING_NUMBER_CODE_LIKE)
                ->get(['code', 'value'])
                ->all();
        }

        $this->line('<info>Tables to TRUNCATE</info> (' . count($targets) . '):');
        $rows = [];
        $totalRows = 0;
        foreach ($targets as $table => $count) {
            $rows[] = [$table, number_format($count)];
            $totalRows += $count;
        }
        $this->table(['table', 'rows'], $rows);
        $this->line('Total rows to delete: <comment>' . number_format($totalRows) . '</comment>');

        $this->newLine();
        $this->line('<info>Running-number counters to RESET to 0</info> (' . count($runningNumbers) . '):');
        $this->table(
            ['code', 'current value'],
            array_map(fn ($r) => [$r->code, $r->value], $runningNumbers)
        );

        $this->newLine();
        $this->line('<info>Everything else is KEPT</info> — users, roles, reports, customer groups, and all other master data.');

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run — no changes written.');
            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->newLine();
            if (! $this->confirm('This permanently deletes the rows above. Continue?', false)) {
                $this->warn('Aborted — nothing was changed.');
                return self::SUCCESS;
            }
        }

        $this->performReset(array_keys($targets));

        $this->newLine();
        $this->info('Done — wiped ' . count($targets) . ' tables and reset ' . count($runningNumbers) . ' running-number counters.');
        return self::SUCCESS;
    }

    private function performReset(array $tables): void
    {
        // TRUNCATE is auto-committing DDL in MySQL, so FK checks can't be left
        // to a transaction; disable them for the pass and restore after.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
                $this->line('  truncated ' . $table);
            }

            DB::table('codes')
                ->where('code', 'like', self::RUNNING_NUMBER_CODE_LIKE)
                ->update(['value' => 0, 'STR_UDF1' => null]);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
