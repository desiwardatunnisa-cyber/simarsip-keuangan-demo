<?php
namespace App\Models;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bagian',
        'departemen',
        'avatar',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'staff']);
    }
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }
    public function isAdminIT(): bool
    {
        return $this->role === 'admin' && $this->bagian === 'admin_it';
    }
    public function isKabag(): bool
{
    return $this->role === 'admin' && $this->bagian === 'kabag';
}
public function isAdminBagian(): bool
{
    return $this->role === 'admin' && $this->bagian === 'admin_bagian';
}
public function isStaffAkuntan(): bool
{
    return $this->isStaff() && $this->departemen === 'akuntan';
}
public function isStaffKeuangan(): bool
{
    return $this->isStaff() && $this->departemen === 'keuangan';
}
public function documents()
{
    return $this->hasMany(Document::class);
}
    public function loginSessions()
    {
        return $this->hasMany(LoginSession::class);
    }
}