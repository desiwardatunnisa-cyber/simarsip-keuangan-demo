<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    /**
     * Auto Logout (Idle 2 jam mengikuti Session Lifetime) perlu terasa
     * realtime, jadi dijalankan tiap 5 menit — bukan sejam sekali seperti
     * sebelumnya (yang bisa membuat sesi idle "nyangkut" hampir 3 jam
     * sebelum ditutup). Query di dalam command ringan (hanya menutup sesi
     * yang sudah lewat batas), jadi aman dijalankan sesering ini.
     */
    protected function schedule(Schedule $schedule): void
{
    $schedule->command('sessions:tutup-kadaluarsa')->everyFiveMinutes();
}

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}