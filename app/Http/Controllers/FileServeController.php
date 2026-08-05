<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileServeController extends Controller
{
    /**
     * Menyajikan file dari disk "public" (storage/app/public) langsung lewat aplikasi,
     * tanpa bergantung pada symlink "public/storage" (php artisan storage:link).
     *
     * Ini memperbaiki kasus dimana symlink belum/tidak dibuat di server (hosting yang
     * tidak mengizinkan SSH, atau symlink hilang saat deploy ulang/upload manual), yang
     * menyebabkan foto profil, avatar, dan preview berkas (PDF/Excel/CSV/Word/Gambar)
     * gagal tampil (404 / "not found") padahal file fisiknya ada.
     */
    public function show(string $path): Response
    {
        // Avatar & sebagian besar file selalu ada di disk "public" (lokal). Dokumen
        // yang failover ke server cadangan (lihat FileFailoverStorage) tetap memakai
        // URL /file/{path} yang sama persis (disk "cadangan" dikonfigurasi dengan url
        // yang sama), jadi di sini cukup coba "public" dulu lalu fallback "cadangan".
        $disk = Storage::disk('public')->exists($path)
            ? Storage::disk('public')
            : Storage::disk('cadangan');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
