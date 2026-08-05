<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    protected $fillable = [
        'login_session_id', 'user_id', 'method', 'url', 'route_name', 'ip_address',
    ];

    public function loginSession(): BelongsTo
    {
        return $this->belongsTo(LoginSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}