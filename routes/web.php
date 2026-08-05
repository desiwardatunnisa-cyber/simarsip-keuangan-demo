<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SessionCloseController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\FileServeController;

Route::get('/', function () {
    return redirect('/admin');
});

// Menyajikan file dari disk "public" (avatar, preview dokumen, dll) tanpa bergantung
// pada symlink "public/storage". Dipakai otomatis oleh Storage::disk('public')->url()
// karena disk "public" diarahkan ke sini lewat config/filesystems.php.
Route::get('/file/{path}', [FileServeController::class, 'show'])
    ->where('path', '.*')
    ->middleware('web')
    ->name('file.serve');

// Download dokumen terkontrol: cek hak akses & keberadaan file fisik sebelum stream download.
Route::get('/documents/{document}/download', DocumentDownloadController::class)
    ->middleware(['web', 'auth'])
    ->name('documents.download');

// Dipanggil otomatis lewat JavaScript (navigator.sendBeacon) saat tab/browser ditutup,
// supaya sesi login langsung ditandai selesai meskipun user tidak sempat klik Logout.
Route::post('/session-close', [SessionCloseController::class, 'close'])
    ->middleware('web')
    ->name('session.close');