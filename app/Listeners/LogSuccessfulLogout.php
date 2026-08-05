<?php

namespace App\Listeners;

use App\Models\LoginSession;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        $sessionId = session('login_session_id');

        $query = $sessionId
            ? LoginSession::where('id', $sessionId)
            : LoginSession::where('user_id', $event->user?->id)->whereNull('logout_at')->latest('login_at');

        $query->first()?->update(['logout_at' => now()]);
    }
}