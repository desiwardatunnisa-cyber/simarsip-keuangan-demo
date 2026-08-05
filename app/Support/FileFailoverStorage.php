<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Menyimpan file dokumen dengan mekanisme failover:
 * 1) Coba simpan ke disk "public" (storage lokal, server 192.168.1.9) dulu — prioritas
 *    utama karena aplikasi web sendiri jalan di mesin yang sama.
 * 2) Kalau gagal (folder/drive penuh, rusak, permission bermasalah, dsb) ATAU hasil
 *    tulisannya tidak valid (ukuran file tidak sama dgn aslinya, indikasi disk penuh
 *    di tengah proses tulis) — otomatis coba simpan ke disk "cadangan" (server
 *    192.168.1.10 lewat jaringan, folder yang sama dgn yang dipakai fitur Backup).
 *
 * CATATAN PENTING: ini BUKAN solusi untuk "server 192.168.1.9 mati total" — kalau itu
 * yang terjadi, aplikasi web-nya sendiri ikut tidak bisa diakses. Ini murni menangani
 * "storage/drive lokal bermasalah tapi aplikasi & servernya sendiri tetap hidup".
 */
class FileFailoverStorage
{
    /**
     * Lokasi fisik terakhir tempat file BERHASIL disimpan: 'lokal' atau 'cadangan'.
     * null berarti simpan() belum pernah dipanggil di request ini, atau gagal total
     * di kedua lokasi. Dibaca oleh CreateDocument/EditDocument untuk mengisi kolom
     * `lokasi_penyimpanan` pada record yang baru saja disimpan.
     */
    public static ?string $lokasiTerakhir = null;

    public static function simpan(TemporaryUploadedFile $file, string $direktori): ?string
    {
        $namaUnik = (string) Str::ulid() . '.' . $file->getClientOriginalExtension();
        $direktori = trim($direktori, '/');

        if (static::simpanKeDisk($file, 'public', $direktori, $namaUnik)) {
            static::$lokasiTerakhir = 'lokal';

            return $direktori . '/' . $namaUnik;
        }

        Log::warning('FileFailoverStorage: gagal simpan ke storage lokal, mencoba server cadangan.', [
            'file' => $file->getClientOriginalName(),
        ]);

        if (static::simpanKeDisk($file, 'cadangan', $direktori, $namaUnik)) {
            static::$lokasiTerakhir = 'cadangan';

            return $direktori . '/' . $namaUnik;
        }

        Log::error('FileFailoverStorage: gagal simpan file ke storage lokal MAUPUN server cadangan.', [
            'file' => $file->getClientOriginalName(),
        ]);
        static::$lokasiTerakhir = null;

        return null;
    }

    protected static function simpanKeDisk(TemporaryUploadedFile $file, string $disk, string $direktori, string $namaFile): bool
    {
        // KHUSUS TESTING: kalau SIMULATE_LOCAL_STORAGE_FAILURE=true di .env, paksa
        // disk "public" (lokal) selalu dianggap gagal — tanpa perlu benar-benar
        // merusak folder/permission di Windows. Set balik ke false (atau hapus baris
        // env-nya) setelah selesai testing failover, lalu `php artisan config:clear`.
        if ($disk === 'public' && filter_var(env('SIMULATE_LOCAL_STORAGE_FAILURE', false), FILTER_VALIDATE_BOOL)) {
            Log::warning('FileFailoverStorage: SIMULATE_LOCAL_STORAGE_FAILURE aktif, memaksa gagal di disk lokal (mode testing).');

            return false;
        }

        try {
            $pathTersimpan = Storage::disk($disk)->putFileAs($direktori, $file, $namaFile, 'public');

            if ($pathTersimpan === false) {
                return false;
            }

            // Verifikasi: file benar-benar ada & ukurannya sama persis dengan aslinya —
            // bukan cuma "put() tidak return false" tapi ternyata file kepotong/0 byte
            // karena disk penuh di tengah proses tulis.
            if (! Storage::disk($disk)->exists($pathTersimpan)) {
                return false;
            }

            if (Storage::disk($disk)->size($pathTersimpan) !== $file->getSize()) {
                Storage::disk($disk)->delete($pathTersimpan);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("FileFailoverStorage: exception saat simpan ke disk '{$disk}': " . $e->getMessage());

            return false;
        }
    }

    /**
     * Hapus file dari disk yang sesuai. Dicoba dulu di disk sesuai `$lokasi` yang
     * tercatat; kalau tidak ada di situ (data lama / lokasi_penyimpanan belum akurat),
     * coba disk satunya sebagai fallback supaya file tidak "nyangkut".
     */
    public static function hapus(?string $path, ?string $lokasi = null): void
    {
        if (! $path) {
            return;
        }

        $urutanDisk = $lokasi === 'cadangan' ? ['cadangan', 'public'] : ['public', 'cadangan'];

        foreach ($urutanDisk as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);

                return;
            }
        }
    }

    /**
     * Cari disk mana yang benar-benar menyimpan file ini sekarang. Dipakai untuk
     * melayani lihat/download supaya tetap jalan walau kolom lokasi_penyimpanan belum
     * akurat (data lama sebelum kolom ini ada) atau file dipindah manual.
     */
    public static function cariDisk(string $path, ?string $lokasiPrioritas = null): ?string
    {
        $urutan = $lokasiPrioritas === 'cadangan' ? ['cadangan', 'public'] : ['public', 'cadangan'];

        foreach ($urutan as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }
}
