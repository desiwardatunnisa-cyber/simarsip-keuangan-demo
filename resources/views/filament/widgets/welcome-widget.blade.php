<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="{ now: new Date() }"
            x-init="setInterval(() => now = new Date(), 1000)"
            style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:20px;"
        >
            <div style="display:flex; align-items:center; gap:18px;">
                <div style="
                    flex-shrink:0; width:56px; height:56px; border-radius:9999px;
                    background: var(--sim-primary-soft, #EAF2FB); color: var(--sim-primary-dark, #0F3D6E);
                    display:flex; align-items:center; justify-content:center;">
                    <x-filament::icon icon="heroicon-o-building-office-2" style="width:28px; height:28px;" />
                </div>

                <div>
                    <h3 style="font-size:22px; font-weight:800; color:#1E293B; margin:0 0 2px 0;">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h3>
                    <p style="font-size:12.5px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:#1B5FA8; margin:0 0 6px 0;">
                        {{ $this->roleLabel() }}
                    </p>
                    <p style="font-size:14px; color:#475569; margin:0;">
                        Kelola arsip perusahaan dengan aman, cepat, dan profesional.
                    </p>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <div style="text-align:right;">
                    <p style="margin:0; font-size:13px; font-weight:600; color:#1E293B;" x-text="now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })"></p>
                    <p style="margin:0; font-size:12px; color:#64748B;" x-text="now.toLocaleTimeString('id-ID') + ' WIB'"></p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
