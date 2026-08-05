<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Widget pembungkus khusus untuk Dashboard: baris Donut Chart + Bar Chart
 * berdampingan 50/50, diikuti baris Dokumen Terbaru selebar penuh, tepat
 * di bawah card statistik (sesuai mockup terbaru).
 *
 * Ini murni penataan tampilan — masing-masing bagian tetap widget Livewire
 * yang sudah ada (DocumentsByCategoryChart, DocumentsPerMonthChart,
 * LatestDocumentsWidget), hanya disatukan dalam blade agar rapi sejajar
 * tanpa perlu mengubah widget grid bawaan Filament yang tidak mendukung
 * penataan seperti ini secara langsung. "My Activity" (riwayat login &
 * aktivitas sendiri) kini jadi widget tabel tersendiri di Dashboard,
 * ditata seperti tabel Monitoring Staff — lihat MyActivityWidget.
 */
class DashboardActivityChartSection extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-activity-chart-section';

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;
}
