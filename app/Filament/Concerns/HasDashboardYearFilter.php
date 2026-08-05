<?php

namespace App\Filament\Concerns;

use Livewire\Attributes\On;

/**
 * Filter Tahun GLOBAL untuk Dashboard. Dropdown-nya hidup di widget
 * "Riwayat Dokumen"; begitu user memilih tahun, event 'dashboard-tahun-changed'
 * di-broadcast ke seluruh widget Livewire di halaman (Statistik, Riwayat
 * Dokumen, Aktivitas Login, Bar Chart, Donut Chart) tanpa reload halaman.
 *
 * Nilai yang dipilih juga disimpan di session, supaya tetap konsisten kalau
 * halaman di-refresh manual.
 */
trait HasDashboardYearFilter
{
    public string $selectedYear = 'all';

    public function mountHasDashboardYearFilter(): void
    {
        $this->selectedYear = (string) session('dashboard_tahun_filter', 'all');
    }

    #[On('dashboard-tahun-changed')]
    public function onDashboardTahunChanged(string $tahun): void
    {
        $this->selectedYear = $tahun;
    }

    /**
     * Null kalau "Semua Tahun" (tidak difilter), atau integer tahun terpilih.
     */
    protected function tahunTerpilih(): ?int
    {
        return $this->selectedYear === 'all' ? null : (int) $this->selectedYear;
    }

    /**
     * Opsi dropdown Tahun: Semua Tahun + 4 tahun terakhir (terbaru dulu).
     */
    public static function opsiTahunDashboard(): array
    {
        $tahunSekarang = (int) now()->year;

        return [
            'all' => 'Semua Tahun',
            (string) $tahunSekarang => (string) $tahunSekarang,
            (string) ($tahunSekarang - 1) => (string) ($tahunSekarang - 1),
            (string) ($tahunSekarang - 2) => (string) ($tahunSekarang - 2),
            (string) ($tahunSekarang - 3) => (string) ($tahunSekarang - 3),
        ];
    }
}
