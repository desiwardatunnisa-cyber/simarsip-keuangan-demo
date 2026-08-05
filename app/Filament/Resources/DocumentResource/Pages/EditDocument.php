<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    // Tombol "Back" di header dihapus — form ini sudah punya tombol
    // "Cancel" di bawah (bawaan Filament), jadi Back jadi redundan.
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Kalau dokumen yang sedang diedit berstatus "revisi" (dikembalikan
     * Admin untuk diperbaiki), begitu staff simpan ulang otomatis
     * dikembalikan ke "pending" supaya masuk antrean verifikasi lagi.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // FileFailoverStorage::$lokasiTerakhir hanya terisi kalau saveUploadedFileUsing()
        // benar-benar dipanggil di request ini (artinya user upload ulang file baru).
        // Kalau tidak ganti file, biarkan lokasi_penyimpanan yang lama tidak berubah.
        if (\App\Support\FileFailoverStorage::$lokasiTerakhir !== null) {
            $data['lokasi_penyimpanan'] = \App\Support\FileFailoverStorage::$lokasiTerakhir;
        }

        if ($this->record->status === 'revisi') {
            $data['status'] = 'pending';
        }

        return $data;
    }
}