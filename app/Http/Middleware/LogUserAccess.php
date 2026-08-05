<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use App\Models\LoginSession;
use Closure;
use Illuminate\Http\Request;

class LogUserAccess
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check()) {
            // ===== Penanda "aktivitas terakhir" untuk deteksi Idle =====
            // Di-touch pada SETIAP request yang sudah login (termasuk request
            // AJAX Livewire — klik tabel, ganti filter, dsb.), bukan hanya
            // kunjungan halaman GET. Memakai kolom `updated_at` yang sudah
            // ada di tabel login_sessions, tidak ada kolom/tabel baru.
            // LoginSession::isOnline()/isIdle() membaca kolom ini untuk
            // menentukan status Online/Idle/Offline secara realtime.
            if ($sessionId = session('login_session_id')) {
                LoginSession::whereKey($sessionId)
                    ->whereNull('logout_at')
                    ->update(['updated_at' => now()]);
            }

            // ===== Riwayat kunjungan halaman (tidak diubah) =====
            if ($request->isMethod('get') && ! str_starts_with($request->path(), 'livewire')) {
                AccessLog::create([
                    'login_session_id' => session('login_session_id'),
                    'user_id' => auth()->id(),
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'route_name' => $request->route()?->getName(),
                    'ip_address' => $request->ip(),
                ]);
            }
        }

        return $response;
    }
}