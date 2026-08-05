<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDashboardYearFilter;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\LoginSession;
use Filament\Widgets\Widget;

/**
 * "Aktivitas Login Saya" — HANYA aktivitas milik user yang sedang login
 * (bukan aktivitas seluruh user). Menggabungkan riwayat Login/Logout
 * (LoginSession) dengan Audit Log (upload, edit, verifikasi, revisi,
 * backup database, kelola user) milik user itu sendiri. Mengikuti
 * filter Tahun GLOBAL Dashboard (dropdown ada di card Riwayat Dokumen).
 *
 * Catatan: aktivitas "Download Dokumen" belum tercatat di sistem manapun
 * (DocumentDownloadController tidak menulis Audit Log), jadi tidak
 * ditampilkan di sini — menambahkannya berarti mengubah Controller,
 * yang di luar cakupan (hanya tampilan Dashboard).
 */
class MyLoginActivityWidget extends Widget
{
    use HasDashboardYearFilter;

    protected static string $view = 'filament.widgets.my-login-activity-widget';

    protected static ?string $heading = 'Aktivitas Login Saya';

    protected int | string | array $columnSpan = 'full';

    public function getActivities()
    {
        $user = auth()->user();
        $tahun = $this->tahunTerpilih();

        $loginEvents = LoginSession::query()
            ->where('user_id', $user->id)
            ->when($tahun, fn ($query) => $query->whereYear('login_at', $tahun))
            ->latest('login_at')
            ->limit(15)
            ->get()
            ->flatMap(function (LoginSession $session) {
                $items = collect([[
                    'waktu' => $session->login_at,
                    'label' => 'Login',
                    'icon' => 'heroicon-o-arrow-right-on-rectangle',
                    'color' => 'success',
                ]]);

                if ($session->logout_at) {
                    $items->push([
                        'waktu' => $session->logout_at,
                        'label' => 'Logout',
                        'icon' => 'heroicon-o-arrow-left-on-rectangle',
                        'color' => 'gray',
                    ]);
                }

                return $items;
            });

        $auditEvents = AuditLog::query()
            ->where('user_id', $user->id)
            ->when($tahun, fn ($query) => $query->whereYear('created_at', $tahun))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (AuditLog $log) => [
                'waktu' => $log->created_at,
                'label' => $this->labelUntuk($log),
                'icon' => $this->iconUntuk($log),
                'color' => $this->colorUntuk($log),
            ]);

        return $loginEvents
            ->concat($auditEvents)
            ->sortByDesc('waktu')
            ->take(15)
            ->values();
    }

    private function labelUntuk(AuditLog $log): string
    {
        return match (true) {
            $log->model === 'Document' && $log->aksi === 'created' => 'Upload Dokumen' . ($log->deskripsi ? ": {$log->deskripsi}" : ''),
            $log->model === 'Document' && $log->aksi === 'deleted' => 'Hapus Dokumen' . ($log->deskripsi ? ": {$log->deskripsi}" : ''),
            $log->model === 'Document' && $log->aksi === 'updated' => $this->labelUpdateDokumen($log),
            $log->model === 'User' => 'Kelola User' . ($log->deskripsi ? ": {$log->deskripsi}" : ''),
            $log->model === 'Category' => 'Kelola Kategori' . ($log->deskripsi ? ": {$log->deskripsi}" : ''),
            $log->model === 'Backup' => 'Backup Database',
            default => ucfirst($log->aksi) . ' ' . $log->model,
        };
    }

    /**
     * AuditLog cuma mencatat "updated" secara umum (dari Observer generik),
     * jadi label persisnya (Verifikasi/Revisi/Edit) ditentukan dari status
     * dokumen SAAT INI — pendekatan terbaik tanpa mengubah Observer/Model.
     */
    private function labelUpdateDokumen(AuditLog $log): string
    {
        $status = Document::find($log->model_id)?->status;

        return match ($status) {
            'approved' => 'Verifikasi Dokumen' . ($log->deskripsi ? ": {$log->deskripsi}" : ''),
            'revisi' => 'Revisi Dokumen' . ($log->deskripsi ? ": {$log->deskripsi}" : ''),
            default => 'Edit Dokumen' . ($log->deskripsi ? ": {$log->deskripsi}" : ''),
        };
    }

    private function iconUntuk(AuditLog $log): string
    {
        return match (true) {
            $log->model === 'Document' && $log->aksi === 'created' => 'heroicon-o-document-arrow-up',
            $log->model === 'Document' && $log->aksi === 'deleted' => 'heroicon-o-trash',
            $log->model === 'Document' && $log->aksi === 'updated' => 'heroicon-o-pencil-square',
            $log->model === 'User' => 'heroicon-o-users',
            $log->model === 'Category' => 'heroicon-o-tag',
            $log->model === 'Backup' => 'heroicon-o-circle-stack',
            default => 'heroicon-o-bolt',
        };
    }

    private function colorUntuk(AuditLog $log): string
    {
        return match (true) {
            $log->model === 'Document' && $log->aksi === 'created' => 'primary',
            $log->model === 'Document' && $log->aksi === 'deleted' => 'danger',
            $log->model === 'Backup' => 'warning',
            default => 'gray',
        };
    }
}
