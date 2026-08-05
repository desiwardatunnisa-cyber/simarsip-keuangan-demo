<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    /**
     * Download dokumen dengan validasi hak akses & pengecekan file fisik.
     * Menghindari 404 mentah dan memastikan browser benar-benar men-download (bukan membuka di tab).
     */
    public function __invoke(Document $document): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user, 403);

        // Pastikan user berhak mengakses dokumen ini sesuai hak akses (Staff hanya miliknya,
        // Admin Bagian hanya departemennya, Admin IT/Kabag semua).
        $bolehAkses = Document::query()
            ->visibleTo($user)
            ->whereKey($document->id)
            ->exists();

        abort_unless($bolehAkses, 403, 'Anda tidak memiliki akses ke dokumen ini.');

        $path = $document->path_file;

        // Cek disk sesuai lokasi_penyimpanan tercatat dulu (lokal/cadangan), dengan
        // fallback ke disk satunya kalau tidak ketemu (mis. data lama sebelum kolom
        // lokasi_penyimpanan ada). User tidak perlu tahu file ini fisiknya di server mana.
        $namaDisk = $path ? \App\Support\FileFailoverStorage::cariDisk($path, $document->lokasi_penyimpanan) : null;

        if (! $path || ! $namaDisk) {
            return back()->with([
                'download_error' => 'File fisik dokumen ini tidak ditemukan di server. Kemungkinan file terhapus atau path rusak. Hubungi Admin IT.',
            ]);
        }

        $namaUnduh = $document->nama_file_sistem
            ?: ($document->judul_dokumen . '.' . $document->tipe_file);

        return Storage::disk($namaDisk)->download($path, $namaUnduh);
    }
}
