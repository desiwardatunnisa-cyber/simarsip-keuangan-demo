<div>
    {{-- Baris 1: Donut Chart (kategori dokumen) + Bar Chart (dokumen per
         bulan) sejajar 50/50, langsung di bawah card statistik. --}}
    <div class="sim-dashboard-row" style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; align-items:start;">
        <div style="min-width:0;">
            @livewire(\App\Filament\Widgets\DocumentsByCategoryChart::class)
        </div>

        <div style="min-width:0;">
            @livewire(\App\Filament\Widgets\DocumentsPerMonthChart::class)
        </div>
    </div>

    {{-- Baris 2: Dokumen Terbaru, satu tabel penuh selebar halaman. --}}
    <div style="margin-top:24px;">
        @livewire(\App\Filament\Widgets\LatestDocumentsWidget::class)
    </div>

    <style>
        @media (max-width: 1024px) {
            .sim-dashboard-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</div>