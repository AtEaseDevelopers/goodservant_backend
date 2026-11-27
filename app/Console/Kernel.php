<?php

namespace App\Console;

use App\Models\Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // CronJob:updatedo (old D/O billing-rate job with e-mails) is not
        // used by Snoodle and must not run every minute once cron is on.

        // Keep only this month and last month of api_logs.
        $schedule->command('CronJob:pruneapilogs')
        ->lastDayOfMonth('23:00')
        ->withoutOverlapping();

        // Keep only this month and last month of the portal activity log.
        $schedule->command('CronJob:pruneactivitylogs')
        ->lastDayOfMonth('23:30')
        ->withoutOverlapping();

        // Nightly AutoCount sync safety net: flag all unsynced invoices as
        // pending at the admin-configured time. schedule:run re-reads the
        // Setting each run, so a UI change takes effect without a redeploy.
        // Guarded so a missing settings table (pre-migration) never breaks the
        // whole schedule.
        try {
            if (Setting::nightlySyncEnabled()) {
                $schedule->command('CronJob:autocountnightlysync')
                ->timezone('Asia/Kuala_Lumpur')
                ->dailyAt(Setting::nightlySyncTime())
                ->withoutOverlapping();
            }
        } catch (\Throwable $e) {
            // Settings unavailable (e.g. before migrations) — skip this job.
        }

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
