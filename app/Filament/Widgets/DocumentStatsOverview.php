<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\DocumentResource;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * 4 Summary Card, tanpa filter Tahun/Divisi global (sudah dihapus dari
     * Dashboard). Kartu menampilkan data riil saat ini sesuai wireframe;
     * filter Tahun hanya ada di Bar Chart & Donut Chart, bukan di sini.
     */
    protected function getStats(): array
    {
        $user = auth()->user();

        return match (true) {
            $user->isAdminIT() => $this->superAdminStats($user),
            $user->isKabag(), $user->isAdminBagian() => $this->adminStats($user),
            default => $this->staffStats($user),
        };
    }

    /**
     * Super Admin: melihat seluruh perusahaan. Super Admin TIDAK memiliki
     * Approval, jadi tidak ada kartu "Menunggu Verifikasi" di sini.
     */
    private function superAdminStats(User $user): array
    {
        $totalDokumen = Document::query()->visibleTo($user)
            ->where('status', 'approved')
            ->count();

        $uploadBulanIni = Document::query()->visibleTo($user)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalKategori = Category::count();

        $superAdminCount = User::where('bagian', 'admin_it')->count();
        $adminKeuangan = User::where('bagian', 'admin_bagian')->where('departemen', 'keuangan')->count();
        $adminAkuntansi = User::where('bagian', 'admin_bagian')->where('departemen', 'akuntan')->count();
        $staffKeuangan = User::where('role', 'staff')->where('departemen', 'keuangan')->count();
        $staffAkuntansi = User::where('role', 'staff')->where('departemen', 'akuntan')->count();

        return [
            Stat::make('Total User', number_format($superAdminCount + $adminKeuangan + $adminAkuntansi + $staffKeuangan + $staffAkuntansi, 0, ',', '.'))
                ->description('Seluruh user perusahaan')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Total Dokumen', number_format($totalDokumen, 0, ',', '.'))
                ->description('Dokumen perusahaan yang sudah terverifikasi')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('primary'),

            Stat::make('Upload Bulan Ini', number_format($uploadBulanIni, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y') . ' • seluruh perusahaan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Kategori', number_format($totalKategori, 0, ',', '.'))
                ->description('Kategori dokumen aktif')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->url(CategoryResource::getUrl('index'))
                ->color('warning'),
        ];
    }

    /**
     * Admin (Kabag / Admin Bagian): data divisinya sendiri saja.
     * Kartu "Menunggu Verifikasi" mengarah ke Arsip Dokumen — di sana
     * tabel "Menunggu Verifikasi" sudah otomatis berisi status
     * pending & revisi sesuai alur approval yang berlaku.
     */
    private function adminStats(User $user): array
    {
        $labelDivisi = $user->departemen ? ucfirst($user->departemen) : 'Semua Divisi';

        $totalDokumen = Document::query()->visibleTo($user)
            ->where('status', 'approved')
            ->count();

        $uploadBulanIni = Document::query()->visibleTo($user)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $menungguVerifikasi = Document::query()->visibleTo($user)
            ->whereIn('status', ['pending', 'revisi'])
            ->count();

        $userQuery = $user->isKabag() ? User::query() : User::where('departemen', $user->departemen);
        $totalAdmin = (clone $userQuery)->where('bagian', 'admin_bagian')->count();
        $totalStaff = (clone $userQuery)->where('role', 'staff')->count();

        return [
            Stat::make('Total Dokumen', number_format($totalDokumen, 0, ',', '.'))
                ->description("Dokumen keuangan {$labelDivisi} yang sudah disetujui")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),

            Stat::make('Upload Bulan Ini', number_format($uploadBulanIni, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total User Divisi', number_format($totalAdmin + $totalStaff, 0, ',', '.'))
                ->description("Admin {$totalAdmin} • Staff {$totalStaff}")
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Menunggu Verifikasi', number_format($menungguVerifikasi, 0, ',', '.'))
                ->description('Klik untuk tinjau di Arsip Dokumen')
                ->descriptionIcon('heroicon-m-clock')
                ->url(DocumentResource::getUrl('index'))
                ->color('warning'),
        ];
    }

    /**
     * Staff: hanya data miliknya sendiri. "Perlu Revisi" & "Menunggu
     * Verifikasi" murni kartu penanda jumlah — keduanya klik menuju
     * Arsip Dokumen (tabel "Menunggu Verifikasi" di sana).
     */
    private function staffStats(User $user): array
    {
        $dokumenSaya = Document::query()->visibleTo($user)
            ->where('status', 'approved')
            ->count();

        $uploadBulanIni = Document::query()->visibleTo($user)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $menungguVerifikasi = Document::query()->visibleTo($user)
            ->where('status', 'pending')
            ->count();

        $perluRevisi = Document::query()->visibleTo($user)
            ->where('status', 'revisi')
            ->count();

        return [
            Stat::make('Dokumen Saya', number_format($dokumenSaya, 0, ',', '.'))
                ->description('Dokumen milik saya yang sudah terverifikasi')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('primary'),

            Stat::make('Upload Bulan Ini', number_format($uploadBulanIni, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Menunggu Verifikasi', number_format($menungguVerifikasi, 0, ',', '.'))
                ->description('Sedang ditinjau admin')
                ->descriptionIcon('heroicon-m-clock')
                ->url(DocumentResource::getUrl('index'))
                ->color('warning'),

            Stat::make('Perlu Revisi', number_format($perluRevisi, 0, ',', '.'))
                ->description('Klik untuk edit & kirim ulang')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->url(DocumentResource::getUrl('index'))
                ->color('danger'),
        ];
    }
}
