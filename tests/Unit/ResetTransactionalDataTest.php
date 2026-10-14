<?php

namespace Tests\Unit;

use App\Console\Commands\ResetTransactionalData;
use PHPUnit\Framework\TestCase;

/**
 * Guards the keep/truncate classification of the snoodle:reset-db command.
 * Pure assertions on the allowlist constants — no DB, so it is safe to run and
 * catches an accidental move of a load-bearing table into the wipe set.
 */
class ResetTransactionalDataTest extends TestCase
{
    /** @test */
    public function requested_and_dependent_tables_are_truncated()
    {
        foreach ([
            'products', 'customers', 'assigns',   // explicitly requested
            'product_prices', 'special_prices', 'focs', // orphaned dependents, re-synced
            'invoices', 'sales_orders', 'deliveryorders', 'tasks', 'trips',
            'inventory_balances', 'api_logs',
        ] as $table) {
            $this->assertContains($table, ResetTransactionalData::TRUNCATE_TABLES, "$table should be truncated");
        }
    }

    /** @test */
    public function kept_data_is_never_truncated()
    {
        foreach ([
            'users', 'roles', 'permissions',
            'reports', 'reportdetails',
            'codes', 'product_price_categories',
            'product_types', 'vendors', 'companies', 'locations', 'lorries',
            'drivers', 'agents', 'supervisors', 'commissionbyvendors',
            'migrations',
        ] as $table) {
            $this->assertNotContains($table, ResetTransactionalData::TRUNCATE_TABLES, "$table must be kept");
        }
    }

    /** @test */
    public function keep_guard_and_truncate_list_never_overlap()
    {
        $this->assertSame(
            [],
            array_values(array_intersect(
                ResetTransactionalData::TRUNCATE_TABLES,
                ResetTransactionalData::KEEP_GUARD
            )),
            'A table must not be on both the keep guard and the truncate list.'
        );
    }

    /** @test */
    public function running_number_pattern_targets_counters_not_customer_groups()
    {
        $like = ResetTransactionalData::RUNNING_NUMBER_CODE_LIKE;
        $regex = '/^' . str_replace('%', '.*', preg_quote($like, '/')) . '$/';

        foreach (['sorunningnumber', 'dorunningnumber', 'invoicerunningnumber', 'cashsalesrunningnumber'] as $code) {
            $this->assertMatchesRegularExpression($regex, $code, "$code should be reset");
        }
        $this->assertDoesNotMatchRegularExpression($regex, 'customer_group');
    }
}
