<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Tabel B di halaman Arsip Dokumen (di bawah tabel A "Dokumen
 * Terverifikasi"): berisi dokumen yang statusnya masih Menunggu
 * Verifikasi atau Perlu Revisi.
 *
 * Kolom Approval (✔ Verifikasi / ✏ Revisi) HANYA muncul untuk Admin
 * (Kabag/Admin Bagian) yang berwenang approve. Staff & Super Admin
 * tidak memiliki Approval (Super Admin upload langsung Terverifikasi,
 * jadi dokumennya tidak pernah muncul di tabel ini).
 */
class MenungguVerifikasiWidget extends BaseWidget
{
    protected static ?string $heading = 'Dokumen Menunggu Verifikasi';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()->visibleTo(auth()->user())->whereIn('status', ['pending', 'revisi'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('rowNumber')
                    ->label('No.')
                    ->state(static fn ($rowLoop) => $rowLoop->iteration ?? '-'),

                Tables\Columns\TextColumn::make('judul_dokumen')
                    ->label('Judul Dokumen')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.nama_kategori')
                    ->label('Kategori')
                    ->badge(),

                Tables\Columns\TextColumn::make('tipe_file')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pdf' => 'danger',
                        'xls', 'xlsx' => 'success',
                        'jpg', 'jpeg', 'png' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.departemen')
                    ->label('Divisi')
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '-')
                    ->visible(fn () => auth()->user()?->isAdminIT() ?? false)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diupload Oleh')
                    ->formatStateUsing(fn ($record) => view('filament.tables.columns.uploader', ['user' => $record->user])->render())
                    ->html(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'revisi' ? 'Perlu Revisi' : 'Menunggu Verifikasi')
                    ->color(fn (string $state) => $state === 'revisi' ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d M Y H:i'),
            ])
            ->recordUrl(fn (Document $record) => DocumentResource::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Document $record) => route('documents.download', $record))
                    ->openUrlInNewTab(),

                // Approval — hanya untuk Admin (Kabag/Admin Bagian). Tidak ada tombol Ditolak.
                Tables\Actions\Action::make('acc')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Document $record) => $record->status === 'pending' && DocumentResource::canApprove($record))
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Dokumen Ini?')
                    ->action(function (Document $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()->title('Dokumen berhasil diverifikasi')->success()->send();
                    }),

                Tables\Actions\Action::make('revisi')
                    ->label('Revisi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('danger')
                    ->visible(fn (Document $record) => $record->status === 'pending' && DocumentResource::canApprove($record))
                    ->requiresConfirmation()
                    ->modalHeading('Kembalikan Dokumen Ini untuk Direvisi?')
                    ->modalDescription('Status dokumen berubah jadi Perlu Revisi (tetap di tabel ini), dan staff pengunggah bisa mengedit lalu mengirim ulang.')
                    ->action(function (Document $record) {
                        $record->update(['status' => 'revisi']);

                        Notification::make()->title('Dokumen dikembalikan untuk direvisi')->warning()->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (Document $record) => DocumentResource::canDelete($record)),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Tidak ada dokumen menunggu verifikasi')
            ->emptyStateDescription('Semua dokumen sudah diverifikasi.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}