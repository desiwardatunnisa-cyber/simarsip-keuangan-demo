<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\HtmlString;

class EditProfile extends BaseEditProfile
{
    // 'none' = mode preview, 'avatar' = sedang ubah foto, 'account' = sedang ubah info akun
    public string $editMode = 'none';

    /**
     * Tombol "Back" di header halaman Profile, konsisten dengan tombol
     * kembali di halaman Detail/Edit/Tambah lain (lihat HasBackButton).
     * Profile bukan bagian dari Resource sehingga arahnya langsung ke
     * Dashboard, bukan ::getResource()::getUrl('index').
     *
     * Disembunyikan selama sedang mengedit (editMode !== 'none') karena
     * saat itu tombol "Batal" sudah tampil di footer form — dua tombol
     * "keluar dari form" sekaligus jadi redundan.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Back')
                ->color('gray')
                ->visible(fn () => $this->editMode === 'none')
                ->url(fn () => \App\Filament\Pages\Dashboard::getUrl()),
        ];
    }

    public function startEditingAvatar(): void
    {
        $this->editMode = 'avatar';
    }

    public function startEditingAccount(): void
    {
        $this->editMode = 'account';
    }

    public function cancelEditing(): void
    {
        $this->editMode = 'none';
        $this->fillForm();
    }

    // Dipanggil otomatis oleh Filament saat tombol Save ditekan
    public function save(): void
    {
        parent::save();
        $this->editMode = 'none';
        $this->fillForm();
    }

    protected function getFormActions(): array
    {
        if ($this->editMode === 'none') {
            $user = auth()->user();

            // Staff tidak punya apa pun untuk diedit di sini selain foto
            // (foto punya tombolnya sendiri tepat di bawah avatar), jadi tidak perlu action footer.
            if ($user->isStaff()) {
                return [];
            }

            return [
                Action::make('editAccount')
                    ->label('Edit Informasi Akun')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->action('startEditingAccount'),
            ];
        }

        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save'),

            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->action('cancelEditing'),
        ];
    }

    // <-- paksa tombol footer selalu dihitung ulang, tidak pakai cache basi
    public function getCachedFormActions(): array
    {
        return $this->getFormActions();
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();

        $bagianLabel = match ($user->bagian) {
            'admin_it' => 'Admin IT',
            'kabag' => 'Kepala Bagian (Kabag)',
            'admin_bagian' => 'Admin Bagian',
            'staff' => 'Staff',
            default => '-',
        };

        $departemenLabel = match ($user->departemen) {
            'akuntan' => 'Akuntansi',
            'keuangan' => 'Keuangan',
            'akuntansi_dan_keuangan' => 'Akuntansi dan Keuangan',
            'admin_it', 'kabag' => 'Akuntansi dan Keuangan', // nilai lama, tetap ditampilkan benar
            default => '-',
        };

        $roleLabel = match (true) {
            $user->isAdminIT() => 'Super Admin',
            $user->isKabag() => 'Kepala Bagian',
            $user->isAdminBagian() => 'Admin Bagian',
            $user->role === 'admin' => 'Admin',
            default => 'Staff',
        };
        $tanggalBergabung = $user->created_at?->translatedFormat('d F Y');

        // Avatar default (inisial nama) dipakai sebagai fallback kalau user belum punya
        // foto ATAU kalau file fotonya gagal dimuat (rusak/terhapus/URL bermasalah).
        $inisial = strtoupper(substr($user->name, 0, 1));
        $avatarFallbackHtml = '<div class="simarsip-avatar-fallback" style="width:96px;height:96px;max-width:100%;border-radius:9999px;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.5rem;color:#6b7280;">' . e($inisial) . '</div>';

        $avatarImg = $user->avatar
            // onerror: kalau URL foto gagal dimuat (404/rusak), sembunyikan <img> dan
            // tampilkan fallback inisial yang sudah disiapkan di sebelahnya — supaya
            // yang terlihat bukan ikon "gambar rusak" bawaan browser.
            ? '<img src="' . e(\Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar)) . '" alt="Foto profil ' . e($user->name) . '"'
                . ' style="width:96px;height:96px;max-width:100%;border-radius:9999px;object-fit:cover;"'
                . ' onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">'
                . '<div class="simarsip-avatar-fallback" style="display:none;width:96px;height:96px;max-width:100%;border-radius:9999px;background:#e5e7eb;align-items:center;justify-content:center;font-weight:700;font-size:1.5rem;color:#6b7280;">' . e($inisial) . '</div>'
            : $avatarFallbackHtml;

        // Foto + tombol "Ubah Foto" tepat di bawahnya, terpusat. flex-wrap & max-width
        // menjaga tampilan tetap rapi di layar kecil (mobile).
        $avatarBlockHtml = '
            <div style="display:flex;flex-direction:column;align-items:center;gap:10px;flex-wrap:wrap;max-width:100%;text-align:center;">
                ' . $avatarImg . '
                <button type="button" wire:click="startEditingAvatar"
                    style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border:1px solid #C2C6D3;border-radius:8px;background:#ffffff;color:#00346F;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C3.05 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.8-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                    </svg>
                    Ubah Foto
                </button>
            </div>
        ';

        return $form->schema([

            // ===== MODE PREVIEW =====
            Section::make('Informasi Akun')
                ->visible(fn () => $this->editMode === 'none')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Placeholder::make('avatar_preview')
                        ->label('Foto Profil')
                        ->columnSpan(2)
                        ->content(new HtmlString(
                            '<div style="display:flex;justify-content:center;">' . $avatarBlockHtml . '</div>'
                        )),

                    Placeholder::make('name_display')
                        ->label('Name')
                        ->content($user->name),

                    Placeholder::make('email_display')
                        ->label('Email address')
                        ->content($user->email),

                    Placeholder::make('bagian_display_preview')
                        ->label('Bagian')
                        ->content($bagianLabel),

                    Placeholder::make('departemen_display_preview')
                        ->label('Departemen')
                        ->content($departemenLabel),

                    Placeholder::make('role_display_preview')
                        ->label('Role / Hak Akses')
                        ->content($roleLabel),

                    Placeholder::make('created_at_display_preview')
                        ->label('Tanggal Bergabung')
                        ->content($tanggalBergabung),
                ]),

            // ===== MODE UBAH FOTO =====
            Section::make('Ubah Foto Profil')
                ->visible(fn () => $this->editMode === 'avatar')
                ->schema([
                    FileUpload::make('avatar')
                        ->label('')
                        ->avatar()
                        ->disk('public')
                        ->directory('avatars'),
                ]),

            // ===== MODE UBAH INFORMASI AKUN =====
            Section::make('Informasi Akun')
                ->visible(fn () => $this->editMode === 'account')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    $this->getNameFormComponent()
                        ->disabled(fn () => $user->isStaff())
                        ->dehydrated(fn () => ! $user->isStaff())
                        ->helperText(fn () => $user->isStaff() ? 'Staff tidak dapat mengubah nama. Hubungi admin.' : null),

                    $this->getEmailFormComponent()
                        ->disabled(fn () => $user->isStaff())
                        ->dehydrated(fn () => ! $user->isStaff())
                        ->helperText(fn () => $user->isStaff() ? 'Staff tidak dapat mengubah email. Hubungi admin.' : null),

                    Placeholder::make('bagian_display')
                        ->label('Bagian')
                        ->content($bagianLabel),

                    Placeholder::make('departemen_display')
                        ->label('Departemen')
                        ->content($departemenLabel),

                    Placeholder::make('role_display')
                        ->label('Role / Hak Akses')
                        ->content($roleLabel),

                    Placeholder::make('created_at_display')
                        ->label('Tanggal Bergabung')
                        ->content($tanggalBergabung),
                ]),

            Section::make('Ubah Password')
                ->visible(fn () => $this->editMode === 'account' && ! $user->isStaff())
                ->schema([
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                ]),
        ]);
    }
}
