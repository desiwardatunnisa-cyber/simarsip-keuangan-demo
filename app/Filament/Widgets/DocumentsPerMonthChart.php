<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasDashboardYearFilter;
use App\Models\Document;
use Filament\Widgets\ChartWidget;

class DocumentsPerMonthChart extends ChartWidget
{
    use HasDashboardYearFilter;

    protected static ?string $heading = 'Upload Dokumen per Bulan';

    protected static ?int $sort = 2;

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

        $namaBulan = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
        ];

        $jumlah = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $jumlah[] = Document::query()->visibleTo($user)
                ->when($tahun, fn ($query) => $query->whereYear('created_at', $tahun))
                ->whereMonth('created_at', $bulan)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Dokumen Diupload',
                    'data' => $jumlah,
                    'backgroundColor' => '#1B5FA8',
                    'borderColor' => '#0F3D6E',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $namaBulan,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
