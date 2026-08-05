<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; width:100%;">
                <span>Riwayat Dokumen</span>

                <select
                    wire:model.live="selectedYear"
                    style="
                        border:1px solid var(--sim-border, #E5E7EB);
                        border-radius:0.5rem;
                        padding:6px 32px 6px 12px;
                        font-size:13px;
                        font-weight:600;
                        color:var(--sim-text, #1E293B);
                        background-color:#fff;
                        cursor:pointer;
                    "
                >
                    @foreach ($this->opsiTahunDashboard() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-widgets::widget>
