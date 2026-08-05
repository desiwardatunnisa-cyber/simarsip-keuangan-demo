<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AuditLogDetailWidget extends BaseWidget
{
    /**
     * Isi tabel ini adalah pemindahan langsung dari AuditLogResource lama
     * (yang sudah dihapus dari navigasi/menu). Ditampilkan sekarang hanya
     * lewat modal "Lihat Detail" di Dashboard, bukan sebagai menu/halaman
     * tersendiri. Kolom, filter, badge warna, dan poll otomatis sama persis
     * dengan versi lama — tidak ada fungsi yang hilang.
     */
    protected static ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AuditLog::query()
                    ->with('user')
                    ->when(
                        auth()->user(),
                        fn ($query, $user) => $query->visibleTo($user)
                    )
            )
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
            ->poll('30s'); // auto-refresh tiap 30 detik, sama seperti versi lama
    }
}