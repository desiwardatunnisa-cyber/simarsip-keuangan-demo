<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'aksi', 'model', 'model_id', 'deskripsi', 'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Batasi log aktivitas sesuai peran, konsisten dengan pola
     * Document::scopeVisibleTo() dan LoginSession::scopeVisibleTo().
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdminIT() || $user->isKabag()) {
            return $query; // Super Admin & Kabag: lihat semua aktivitas
        }

        if ($user->isAdminBagian()) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('departemen', $user->departemen);
            });
        }

        return $query->where('user_id', $user->id);
    }

    public static function catat(string $aksi, string $model, ?int $modelId, ?string $deskripsi = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'model' => $model,
            'model_id' => $modelId,
            'deskripsi' => $deskripsi,
            'ip_address' => request()?->ip(),
        ]);
    }
}
