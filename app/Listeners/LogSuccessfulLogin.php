<?php

namespace App\Listeners;

use App\Models\LoginSession;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $session = LoginSession::create([
            'user_id' => $event->user->id,
            'login_at' => now(),
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
        ]);

        session(['login_session_id' => $session->id]);
    }
}