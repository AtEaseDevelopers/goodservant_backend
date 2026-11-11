<?php

namespace App\Console\Commands;

use App\Models\SalesOrder;
use Illuminate\Console\Command;

class ForwardOffDayOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offday:forward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Convert the previous day's off-day sales orders into normal orders for the new day";

    /**
     * Execute the console command.
     *
     * An off-day order is placed today but held back from today's delivery. The
     * next day it should become a normal, deliverable order dated for that day.
     * We forward any still-open off-day order dated before today (using "before
     * today" rather than strictly "yesterday" also recovers any day the job was
     * missed). Orders already converted to a DO/invoice or cancelled are left
     * untouched.
     *
     * @return int
     */
    public function handle()
    {
        $forwarded = SalesOrder::where('off_day', true)
            ->whereDate('date', '<', now()->toDateString())
            ->whereNull('deliveryorder_id')
            ->whereNull('invoice_id')
            ->where('status', '!=', 2)
            ->update([
                'off_day' => false,
                'date'    => now(),
            ]);

        $this->info("Forwarded {$forwarded} off-day sales order(s) to today.");

        return 0;
    }
}
