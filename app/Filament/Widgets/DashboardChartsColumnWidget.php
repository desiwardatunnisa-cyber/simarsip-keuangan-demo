<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Widget "pembungkus" murni tampilan: menyusun Donut Chart dan Bar Chart
 * SEJAJAR (side-by-side) selebar penuh Dashboard, tepat di bawah Card
 * Statistik — sesuai mockup terbaru. Tidak mengandung query atau logic
 * sendiri, hanya me-mount dua widget chart yang sudah ada
 * (DocumentsByCategoryChart & DocumentsPerMonthChart) apa adanya.
 */
class DashboardChartsColumnWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-charts-column';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';
}
