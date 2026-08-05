<x-filament-panels::page>
    <div
        x-data
        @buka-cetak-laporan.window="
            const w = window.open('', '_blank');
            w.document.open();
            w.document.write($event.detail.html);
            w.document.close();
        "
    ></div>

    {{ $this->table }}
</x-filament-panels::page>
