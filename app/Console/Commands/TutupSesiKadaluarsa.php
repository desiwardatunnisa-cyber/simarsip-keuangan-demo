<?php

namespace App\Console\Commands;

use App\Models\LoginSession;
use Illuminate\Console\Command;

class TutupSesiKadaluarsa extends Command
{
    protected $signature = 'sessions:tutup-kadaluarsa';
    protected $description = 'Auto Logout: tutup otomatis sesi yang Idle (>2 jam tanpa aktivitas, mengikuti Session Lifetime) dan sesi lama yang nyangkut (>8 jam, jaring pengaman)';

    public function handle(): void
    {
        // Auto Logout utama: sesi IDLE (tidak ada aktivitas) melebihi
        // Session Lifetime (config('session.lifetime'), default 2 jam).
        $idle = LoginSession::tutupSesiIdle();
        $this->info("$idle sesi Idle (session timeout) berhasil di-auto-logout.");

        // Jaring pengaman keras: sesi yang entah kenapa tidak pernah
        // ter-update updated_at-nya sama sekali (edge case), tetap ditutup
        // setelah 8 jam sejak login.
        $kadaluarsa = LoginSession::tutupSesiKadaluarsa();
        $this->info("$kadaluarsa sesi kadaluarsa (jaring pengaman 8 jam) berhasil ditutup otomatis.");
    }
}