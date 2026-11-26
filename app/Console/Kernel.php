<?php

namespace App\Console;

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

        // Keep six months of the portal activity log.
        $schedule->command('CronJob:pruneactivitylogs')
        ->lastDayOfMonth('23:30')
        ->withoutOverlapping();

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
