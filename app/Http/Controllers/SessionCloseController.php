<?php

namespace App\Http\Controllers;

use App\Models\LoginSession;
use Illuminate\Http\Request;

class SessionCloseController extends Controller
{
    public function close(Request $request)
    {
        if (! auth()->check()) {
            return response()->noContent();
        }

        LoginSession::where('user_id', auth()->id())
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first()
            ?->update(['logout_at' => now()]);

        return response()->noContent();
    }
}