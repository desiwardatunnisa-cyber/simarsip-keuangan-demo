<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Tabel kedua di halaman Arsip Dokumen: berisi dokumen yang SUDAH
 * terverifikasi (status = approved). Begitu Admin/Kabag klik Verifikasi
 * pada tabel "Menunggu Verifikasi" di atas, dokumen otomatis pindah dan
 * muncul di sini. Murni tampilan riwayat — tidak ada aksi approval.
 */
class RiwayatDokumenWidget extends BaseWidget
{
    protected static ?string $heading = 'Riwayat Dokumen';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()->visibleTo(auth()->user())->where('status', 'approved')->latest('approved_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('judul_dokumen')
                    ->label('Judul Dokumen')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.nama_kategori')
                    ->label('Kategori')
                    ->badge(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diupload Oleh')
                    ->formatStateUsing(fn ($record) => view('filament.tables.columns.uploader', ['user' => $record->user])->render())
                    ->html(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn () => 'Terverifikasi')
                    ->color('success'),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Tanggal Verifikasi')
                    ->dateTime('d M Y H:i'),
            ])
            ->recordUrl(fn (Document $record) => \App\Filament\Resources\DocumentResource::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Document $record) => route('documents.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada dokumen terverifikasi')
            ->emptyStateDescription('Dokumen yang sudah diverifikasi akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
