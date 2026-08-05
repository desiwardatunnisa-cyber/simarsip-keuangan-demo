<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    // Riwayat Audit Log sudah digabung ke tabel "Lihat Detail" di halaman
    // Monitoring Staff (lihat partials/detail-akses.blade.php), jadi menu ini
    // tidak perlu tampil lagi di sidebar. Resource, route, dan datanya tetap
    // ada (tidak dihapus) supaya tidak menyentuh logic yang sudah berjalan.
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // Audit log tidak boleh diedit/dihapus manual siapapun, murni catatan sistem
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Audit Log')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dilakukan Oleh')
                    ->searchable()
                    ->default('Sistem'),

                Tables\Columns\TextColumn::make('aksi')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'created' => 'Tambah',
                        'updated' => 'Ubah',
                        'deleted' => 'Hapus',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modul')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Detail')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('aksi')
                    ->options([
                        'created' => 'Tambah',
                        'updated' => 'Ubah',
                        'deleted' => 'Hapus',
                    ]),
                Tables\Filters\SelectFilter::make('model')
                    ->options([
                        'Document' => 'Dokumen',
                        'Category' => 'Kategori',
                        'User' => 'User',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // auto-refresh tiap 30 detik
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
