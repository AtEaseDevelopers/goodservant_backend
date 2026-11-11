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
        $schedule->command('CronJob:updatedo')
        ->everyMinute();

        // Keep only this month and last month of api_logs.
        $schedule->command('CronJob:pruneapilogs')
        ->lastDayOfMonth('23:00')
        ->withoutOverlapping();

        // Each morning, forward yesterday's off-day orders into normal,
        // deliverable orders for the new day.
        $schedule->command('offday:forward')
        ->dailyAt('00:05')
        ->timezone('Asia/Kuala_Lumpur')
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
