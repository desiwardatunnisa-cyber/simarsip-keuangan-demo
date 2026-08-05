<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardActivityChartSection;
use App\Filament\Widgets\DocumentStatsOverview;
use App\Filament\Widgets\MyActivityWidget;
use App\Filament\Widgets\WelcomeWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * Layout final sesuai referensi (sama untuk semua role):
     * Welcome Card -> Summary Card (4) -> Baris 1: Donut Chart + Bar Chart
     * (sejajar 50/50) -> Baris 2: Dokumen Terbaru (penuh) -> Baris 3:
     * My Activity (tabel enterprise interaktif ala Monitoring Staff,
     * dibatasi ke data diri sendiri).
     *
     * Donut/Bar Chart & Dokumen Terbaru dirender manual di dalam
     * DashboardActivityChartSection supaya tata letaknya presisi (grid
     * 50/50 lalu baris penuh). My Activity terpisah sebagai widget
     * tersendiri karena butuh tabel Filament penuh (search/filter/sort/
     * aksi modal), bukan sekadar tampilan statis.
     *
     * Tidak ada lagi: filter Tahun/Divisi global, Quick Action.
     * Setiap widget membatasi datanya sendiri sesuai peran lewat
     * scope visibleTo() yang sudah ada di Document/LoginSession/AuditLog.
     */
    public function getWidgets(): array
    {
        return [
            WelcomeWidget::class,
            DocumentStatsOverview::class,
            DashboardActivityChartSection::class,
            MyActivityWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}