<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDashboardYearFilter;
use App\Models\Category;
use Filament\Widgets\ChartWidget;

class DocumentsByCategoryChart extends ChartWidget
{
    use HasDashboardYearFilter;

    protected static ?string $heading = 'Distribusi Dokumen Berdasarkan Kategori';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '220px';

    /**
     * Tidak ada dropdown Tahun sendiri lagi — mengikuti filter Tahun
     * GLOBAL Dashboard (dropdown ada di card Riwayat Dokumen).
     */
    protected function getData(): array
    {
        $user = auth()->user();
        $tahun = $this->tahunTerpilih();

        $categories = Category::withCount(['documents as documents_count' => function ($query) use ($user, $tahun) {
                $query->visibleTo($user)->when($tahun, fn ($q) => $q->whereYear('created_at', $tahun));
            }])
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Dokumen',
                    'data' => $categories->pluck('documents_count')->toArray(),
                    'backgroundColor' => [
                        '#1B5FA8', '#F5A623', '#2E8B57', '#0F3D6E',
                        '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#D32F2F',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $categories->pluck('nama_kategori')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
