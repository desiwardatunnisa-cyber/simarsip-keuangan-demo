<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kelola User';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->isAdminIT() || $user?->isKabag()) ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        // Hanya Super Admin (Admin IT & Kabag) yang boleh edit/hapus user.
        // Admin Bagian sekarang murni read-only di halaman ini — termasuk
        // untuk dirinya sendiri (tidak bisa edit/hapus akunnya sendiri
        // lewat halaman ini).
        return $user?->isAdminIT() || $user?->isKabag();
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isAdminBagian()) {
            // Admin Bagian melihat staff dari departemen yang sama, PLUS
            // dirinya sendiri (supaya akunnya sendiri juga tampil di tabel
            // ini) — read-only, tidak bisa edit/hapus siapa pun termasuk
            // dirinya sendiri (lihat canEdit()/canDelete() di atas).
            return $query->where(function (Builder $query) use ($user) {
                $query->where('id', $user->id)
                    ->orWhere(function (Builder $query) use ($user) {
                        $query->where('role', 'staff')->where('departemen', $user->departemen);
                    });
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(150),

            Forms\Components\Select::make('role')
                ->label('Role / Hak Akses')
                ->native(false)
                // Field ini murni "tampilan" (virtual) dengan 3 pilihan sesuai
                // permintaan: Staff, Admin Bagian, Super Admin. Nilai asli di
                // kolom `role` tabel users tetap cuma 2 (admin/staff) — supaya
                // tidak perlu ubah struktur database — makanya nilainya
                // dikonversi lagi saat disimpan (dehydrateStateUsing) dan saat
                // dibuka utuk edit (afterStateHydrated).
                ->options([
                    'staff' => 'Staff',
                    'admin_bagian' => 'Admin Bagian',
                    'super_admin' => 'Super Admin',
                ])
                ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                    if (! $record) {
                        return;
                    }

                    $component->state(match (true) {
                        $record->role === 'staff' => 'staff',
                        $record->bagian === 'admin_bagian' => 'admin_bagian',
                        default => 'super_admin', // admin_it / kabag
                    });
                })
                ->dehydrateStateUsing(fn ($state) => $state === 'staff' ? 'staff' : 'admin')
                ->required()
                ->default('staff')
                ->dehydrated()
                ->live()
                ->afterStateUpdated(fn (Forms\Set $set) => $set('bagian', null)),

            Forms\Components\Select::make('bagian')
                ->label('Bagian')
                ->native(false)
                // 6 opsi gabungan (Bagian + Departemen sekaligus) sesuai
                // permintaan, tersaring mengikuti Role yang dipilih di atas.
                // Memilih salah satu otomatis mengisi field Departemen di
                // bawah (lihat afterStateUpdated) — user cukup 2x klik untuk
                // mengisi 3 informasi (role, bagian, departemen) sekaligus.
                ->options(fn (Forms\Get $get) => match ($get('role')) {
                    'staff' => [
                        'staff_akuntansi' => 'Staff Akuntansi',
                        'staff_keuangan' => 'Staff Keuangan',
                    ],
                    'admin_bagian' => [
                        'admin_bagian_akuntansi' => 'Admin Bagian Akuntansi',
                        'admin_bagian_keuangan' => 'Admin Bagian Keuangan',
                    ],
                    'super_admin' => [
                        'admin_it' => 'Admin IT',
                        'kabag' => 'Kabag Akuntansi dan Keuangan',
                    ],
                    default => [],
                })
                ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                    if (! $record) {
                        return;
                    }

                    $component->state(match (true) {
                        $record->bagian === 'admin_it' => 'admin_it',
                        $record->bagian === 'kabag' => 'kabag',
                        $record->bagian === 'admin_bagian' && $record->departemen === 'keuangan' => 'admin_bagian_keuangan',
                        $record->bagian === 'admin_bagian' => 'admin_bagian_akuntansi',
                        $record->departemen === 'keuangan' => 'staff_keuangan',
                        default => 'staff_akuntansi',
                    });
                })
                ->dehydrateStateUsing(fn ($state) => match ($state) {
                    'staff_akuntansi', 'staff_keuangan' => 'staff',
                    'admin_bagian_akuntansi', 'admin_bagian_keuangan' => 'admin_bagian',
                    default => $state, // admin_it / kabag apa adanya
                })
                ->required()
                ->live()
                ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                    $set('departemen', match ($state) {
                        'staff_akuntansi', 'admin_bagian_akuntansi' => 'akuntan',
                        'staff_keuangan', 'admin_bagian_keuangan' => 'keuangan',
                        'admin_it', 'kabag' => 'akuntansi_dan_keuangan',
                        default => null,
                    });
                }),

            // Departemen BUKAN dropdown — otomatis terisi mengikuti pilihan
            // Bagian di atas (lihat afterStateUpdated pada field 'bagian').
            // Placeholder hanya menampilkan hasilnya sebagai teks biasa;
            // Hidden field di bawah yang benar-benar menyimpan nilainya ke
            // database, supaya tidak ada dropdown kosong yang membingungkan.
            Forms\Components\Placeholder::make('departemen_display')
                ->label('Departemen')
                ->content(fn (Forms\Get $get) => match ($get('departemen')) {
                    'akuntan' => 'Akuntansi',
                    'keuangan' => 'Keuangan',
                    'akuntansi_dan_keuangan' => 'Akuntansi dan Keuangan',
                    default => '-',
                }),

            Forms\Components\Hidden::make('departemen')
                ->required(),

            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->revealable()
                ->required(fn (string $context) => $context === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->helperText(fn (string $context) => $context === 'edit'
                    ? 'Kosongkan jika tidak ingin mengubah password (saat edit).'
                    : null)
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Data Pengguna')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->isAdminIT() => 'Super Admin',
                        $record->isKabag() => 'Kepala Bagian',
                        $record->isAdminBagian() => 'Admin Bagian',
                        $state === 'admin' => 'Admin',
                        default => 'Staff',
                    })
                    ->color(fn ($state) => $state === 'admin' ? 'danger' : 'info'),

                Tables\Columns\TextColumn::make('bagian')
                    ->label('Bagian')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'admin_it' => 'Admin IT',
                        'kabag' => 'Kabag',
                        'admin_bagian' => 'Admin Bagian',
                        'staff' => 'Staff',
                        default => '-',
                    }),

                Tables\Columns\TextColumn::make('departemen')
                    ->label('Departemen')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'akuntan' => 'Akuntansi',
                        'keuangan' => 'Keuangan',
                        'akuntansi_dan_keuangan' => 'Akuntansi dan Keuangan',
                        // Nilai lama (skema sebelumnya), tetap ditampilkan
                        // dengan benar kalau masih ada data lama:
                        'admin_it', 'kabag' => 'Akuntansi dan Keuangan',
                        default => '-',
                    }),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Dokumen Diupload')
                    ->counts('documents')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Super Admin',
                        'staff' => 'Staff',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->id !== auth()->id() && static::canDelete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}