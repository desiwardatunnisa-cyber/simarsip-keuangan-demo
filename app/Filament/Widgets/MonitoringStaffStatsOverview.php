<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Document;
use App\Models\LoginSession;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Storage;

class MonitoringStaffStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    // Angka "• Online X/Y" harus terasa realtime tanpa perlu refresh manual —
    // dipastikan eksplisit di sini (sebelumnya hanya mengandalkan default
    // bawaan Filament 5s dari trait CanPoll).
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $currentUser = auth()->user();
        $isRestricted = $currentUser?->isAdminBagian() ?? false;

        $userQuery = $isRestricted
            ? User::where('departemen', $currentUser->departemen)
            : User::query();

        $totalUser = (clone $userQuery)->count();
        $totalAdmin = (clone $userQuery)->where('role', 'admin')->count();
        $totalStaff = (clone $userQuery)->where('role', 'staff')->count();

        // Rincian per role & departemen (Super, Admin-AK, Staff-AK, Admin-Keu,
        // Staff-Keu) — hanya relevan untuk Super Admin/Kabag yang melihat
        // seluruh perusahaan. Admin Bagian tetap pakai ringkasan sederhana
        // karena datanya sudah dibatasi ke departemennya sendiri.
        $totalSuper = (clone $userQuery)->where('bagian', 'admin_it')->count();
        $totalAdminAk = (clone $userQuery)->where('bagian', 'admin_bagian')->where('departemen', 'akuntan')->count();
        $totalStaffAk = (clone $userQuery)->where('role', 'staff')->where('departemen', 'akuntan')->count();
        $totalAdminKeu = (clone $userQuery)->where('bagian', 'admin_bagian')->where('departemen', 'keuangan')->count();
        $totalStaffKeu = (clone $userQuery)->where('role', 'staff')->where('departemen', 'keuangan')->count();

        $totalDokumen = Document::query()->visibleTo($currentUser)->count();

        if ($isRestricted) {
            // Admin Bagian: hitung ukuran hanya dari dokumen departemennya sendiri
            $ukuranFolderDokumen = Document::query()->visibleTo($currentUser)->sum('ukuran_file');
            $ukuranFormatted = $ukuranFolderDokumen >= 1048576
                ? round($ukuranFolderDokumen / 1048576, 2) . ' MB'
                : round($ukuranFolderDokumen / 1024, 1) . ' KB';
        }

        // ===== Pengguna Aktif (Online) =====
        // Sesi dianggap Online kalau belum logout DAN masih ada aktivitas
        // dalam batas Session Lifetime (sama persis dengan LoginSession::
        // isOnline(), berbasis kolom updated_at yang di-touch middleware).
        // Dihitung dari user_id yang unik supaya user dengan beberapa sesi
        // tidak terhitung dobel.
        $batasOnline = now()->subMinutes(LoginSession::batasIdleMenit());

        $onlineUserIds = LoginSession::query()
            ->whereNull('logout_at')
            ->where('updated_at', '>', $batasOnline)
            ->distinct()
            ->pluck('user_id');

        $onlineCountFor = fn ($query) => (clone $query)->whereIn('id', $onlineUserIds)->count();

        if ($isRestricted) {
            $onlineUser = $onlineCountFor($userQuery);

            return [
                Stat::make('Total User Bagian Saya', $totalUser)
                    ->description("• Online {$onlineUser}/{$totalUser}")
                    ->descriptionColor('success')
                    ->color('primary'),

                Stat::make('Total Dokumen', $totalDokumen)
                    ->description('Dokumen departemen Anda')
                    ->descriptionIcon('heroicon-m-archive-box')
                    ->color('success'),

                Stat::make('Total Kategori', Category::count())
                    ->description('Kategori dokumen aktif')
                    ->descriptionIcon('heroicon-m-squares-2x2')
                    ->color('warning'),

                Stat::make('Ukuran Penyimpanan', $ukuranFormatted)
                    ->description('Ukuran file departemen Anda')
                    ->descriptionIcon('heroicon-m-server-stack')
                    ->color('danger'),
            ];
        }

        $onlineSuper = $onlineCountFor((clone $userQuery)->where('bagian', 'admin_it'));
        $onlineAdmin = $onlineCountFor((clone $userQuery)->where('bagian', 'admin_bagian'));
        $onlineStaffAk = $onlineCountFor((clone $userQuery)->where('role', 'staff')->where('departemen', 'akuntan'));
        $onlineStaffKeu = $onlineCountFor((clone $userQuery)->where('role', 'staff')->where('departemen', 'keuangan'));

        $totalAdminGabungan = $totalAdminAk + $totalAdminKeu;

        return [
            Stat::make('Total Super Admin', $totalSuper)
                ->description("• Online {$onlineSuper}/{$totalSuper}")
                ->descriptionColor('success')
                ->color('primary'),

            Stat::make('Total Admin', $totalAdminGabungan)
                ->description("• Online {$onlineAdmin}/{$totalAdminGabungan}")
                ->descriptionColor('success')
                ->color('gray'),

            Stat::make('Total Staff Akuntansi', $totalStaffAk)
                ->description("• Online {$onlineStaffAk}/{$totalStaffAk}")
                ->descriptionColor('success')
                ->color('success'),

            Stat::make('Total Staff Keuangan', $totalStaffKeu)
                ->description("• Online {$onlineStaffKeu}/{$totalStaffKeu}")
                ->descriptionColor('success')
                ->color('warning'),
        ];
    }
}