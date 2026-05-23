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
        $schedule->command('transactions:update-expired')->daily();
        $schedule->command('monitor:serials')
            ->hourly()
            ->withoutOverlapping();

        // Membersihkan file temporary upload Livewire yang sudah lebih dari 24 jam
        $schedule->command('livewire:configure-s3-temporary-file-upload-directory')->daily();

        // Pembersihan manual folder livewire-tmp (file > 3 jam)
        $schedule->exec('find ' . storage_path('app/livewire-tmp') . ' -type f -mmin +180 -delete')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
