<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    // Tombol "Back" di header dihapus — form ini sudah punya tombol
    // "Cancel" di bawah (bawaan Filament), jadi Back jadi redundan.
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Ganti label tombol simpan bawaan Filament ("Create" / "Create &
     * create another") jadi "Submit" / "Submit & create another" —
     * konsisten dengan modal Upload Folder & Import Excel yang sudah
     * memakai "Submit". Perilaku simpannya sama persis, cuma teksnya.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Submit'),
            $this->getCreateAnotherFormAction()->label('Submit & create another'),
            $this->getCancelFormAction(),
        ];
    }

    /**
     * Dokumen yang diupload oleh Admin (Super Admin/Admin IT, Kepala Bagian,
     * maupun Admin Bagian) tidak perlu menunggu verifikasi — otomatis
     * langsung berstatus Terverifikasi. Verifikasi hanya diperlukan untuk
     * dokumen yang diupload oleh Staff.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Diisi oleh FileFailoverStorage::simpan() lewat saveUploadedFileUsing() di
        // FileUpload — 'lokal' kalau berhasil simpan di server ini, 'cadangan' kalau
        // storage lokal bermasalah dan otomatis dialihkan ke server 192.168.1.10.
        $data['lokasi_penyimpanan'] = \App\Support\FileFailoverStorage::$lokasiTerakhir ?? 'lokal';

        if (auth()->user()?->isAdmin()) {
            $data['status'] = 'approved';
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        static::renameFileSesuaiId($this->record);
    }

    // Judul dokumen TIDAK diubah sama sekali (persis input user / nama file asli).
    // Hanya nama file FISIK di storage yang diberi prefix ID, supaya tidak
    // bentrok kalau ada 2 dokumen dengan judul yang sama persis.
    public static function renameFileSesuaiId($document): void
    {
        $pathLama = $document->path_file;
        $disk = \App\Support\FileFailoverStorage::cariDisk((string) $pathLama, $document->lokasi_penyimpanan);

        if (! $pathLama || ! $disk) {
            return;
        }

        $ekstensi = pathinfo($pathLama, PATHINFO_EXTENSION);
        $folder = pathinfo($pathLama, PATHINFO_DIRNAME);
        $slug = Str::slug($document->judul_dokumen, '_') ?: 'dokumen';

        $namaBaru = $document->id . '-' . $slug . '.' . $ekstensi;
        $pathBaru = $folder . '/' . $namaBaru;

        Storage::disk($disk)->move($pathLama, $pathBaru);

        $document->updateQuietly([
            'path_file' => $pathBaru,
            'nama_file_sistem' => $namaBaru,
        ]);
    }
}