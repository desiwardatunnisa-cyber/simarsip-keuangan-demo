<div class="rjw-charts-grid">
    <div class="rjw-charts-grid-item">
        @livewire(\App\Filament\Widgets\DocumentsByCategoryChart::class)
    </div>
    <div class="rjw-charts-grid-item">
        @livewire(\App\Filament\Widgets\DocumentsPerMonthChart::class)
    </div>
</div>

<style>
    .rjw-charts-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 1.5rem !important;
        align-items: stretch !important;
        width: 100% !important;
    }
    .rjw-charts-grid-item {
        min-width: 0 !important;
        width: 100% !important;
    }
    /* Pastikan livewire root widget di dalam grid tidak dipaksa full-bleed
       oleh style bawaan Filament (fi-wi columnSpan full dsb). */
    .rjw-charts-grid-item > * {
        width: 100% !important;
        max-width: 100% !important;
    }
    @media (max-width: 1023px) {
        .rjw-charts-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
