<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoginSession extends Model
{
    // Jaring pengaman keras: kalau sudah lebih dari sekian jam sejak login dan
    // belum pernah tersentuh sama sekali (edge case), dianggap otomatis offline.
    const TIMEOUT_JAM = 8;

    protected $fillable = [
        'user_id', 'login_at', 'logout_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Batasi data sesi yang boleh dilihat sesuai peran user yang login,
     * khusus untuk halaman Monitoring Staff (konsisten dengan pola akses
     * yang sudah dipakai di Document::scopeVisibleTo):
     * - Admin IT (Super Admin): lihat SEMUA staff & admin, tanpa batasan.
     * - Kabag: mengawasi semua departemen, tanpa batasan.
     * - Admin Bagian: hanya staff & admin di departemen yang sama dengan dirinya
     *   (tidak bisa melihat departemen lain, apalagi Super Admin).
     * - Selain itu (mis. Staff): hanya melihat sesi miliknya sendiri.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdminIT() || $user->isKabag()) {
            return $query; // Super Admin & Kabag: lihat semua
        }

        if ($user->isAdminBagian()) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('departemen', $user->departemen);
            });
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Batas waktu idle (menit), diambil dari Session Lifetime yang SUDAH ADA
     * (config/session.php -> env SESSION_LIFETIME, default 120 menit = 2 jam).
     * Tidak menambah konfigurasi baru.
     */
    public static function batasIdleMenit(): int
    {
        return (int) config('session.lifetime', 120);
    }

    /**
     * Waktu aktivitas terakhir pada sesi ini. Memakai kolom `updated_at`
     * yang sudah ada (di-touch oleh LogUserAccess middleware setiap request),
     * bukan kolom baru.
     */
    public function aktivitasTerakhir(): \Illuminate\Support\Carbon
    {
        return $this->updated_at ?? $this->login_at;
    }

    // Idle = belum logout resmi, TAPI sudah tidak ada aktivitas melebihi
    // Session Lifetime (default 2 jam).
    public function isIdle(): bool
    {
        if (! is_null($this->logout_at)) {
            return false;
        }

        return $this->aktivitasTerakhir()->lte(now()->subMinutes(self::batasIdleMenit()));
    }

    // Online = belum logout resmi DAN masih ada aktivitas dalam batas
    // Session Lifetime (realtime, bukan cuma berdasarkan jam login).
    public function isOnline(): bool
    {
        if (! is_null($this->logout_at)) {
            return false;
        }

        return $this->aktivitasTerakhir()->gt(now()->subMinutes(self::batasIdleMenit()));
    }

    // Ketahuan "nyangkut" kalau belum logout resmi tapi sudah lewat batas
    // waktu TIMEOUT_JAM sejak login (jaring pengaman keras, edge case sesi
    // yang tidak pernah ter-update sama sekali).
    public function isTimeout(): bool
    {
        return is_null($this->logout_at) && $this->login_at->lte(now()->subHours(self::TIMEOUT_JAM));
    }

    public function durasi(): string
    {
        $akhir = $this->logout_at ?? ($this->isTimeout() ? $this->login_at->addHours(self::TIMEOUT_JAM) : now());
        $diff = $this->login_at->diff($akhir);

        if ($diff->days > 0) {
            return $diff->days . ' hari ' . $diff->h . ' jam';
        }

        return $diff->h . ' jam ' . $diff->i . ' menit';
    }

    /**
     * Auto Logout: tutup otomatis sesi yang IDLE (tidak ada aktivitas)
     * melebihi Session Lifetime (2 jam). `logout_at` diisi dengan waktu
     * aktivitas terakhir (bukan waktu job scheduler berjalan) supaya durasi
     * kerja yang tercatat tetap akurat. Dipanggil oleh Scheduler yang sudah
     * ada (sessions:tutup-kadaluarsa).
     */
    public static function tutupSesiIdle(): int
    {
        $batas = now()->subMinutes(self::batasIdleMenit());

        $sesiIdle = static::whereNull('logout_at')
            ->where('updated_at', '<=', $batas)
            ->get();

        foreach ($sesiIdle as $sesi) {
            $sesi->forceFill(['logout_at' => $sesi->aktivitasTerakhir()])->save();
        }

        return $sesiIdle->count();
    }

    // Tutup paksa sesi-sesi lama yang "nyangkut" (jaring pengaman keras,
    // dipanggil scheduler otomatis)
    public static function tutupSesiKadaluarsa(): int
    {
        return static::whereNull('logout_at')
            ->where('login_at', '<=', now()->subHours(self::TIMEOUT_JAM))
            ->update(['logout_at' => now()]);
    }
}