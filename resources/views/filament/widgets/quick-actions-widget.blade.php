<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Quick Action</x-slot>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px;">
            @foreach ($this->getActions() as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="sim-quick-action"
                    style="
                        display:flex; flex-direction:column; align-items:flex-start; gap:12px;
                        padding:20px; border-radius:1rem; border:1px solid var(--sim-border, #E5E7EB);
                        background:#fff; text-decoration:none;
                        box-shadow: var(--sim-shadow-card, 0 1px 3px rgba(15,61,110,.06));
                        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
                    "
                    onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--sim-shadow-hover, 0 12px 28px -10px rgba(15,61,110,.2))'; this.style.borderColor='var(--sim-primary, #1B5FA8)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--sim-shadow-card, 0 1px 3px rgba(15,61,110,.06))'; this.style.borderColor='var(--sim-border, #E5E7EB)';"
                >
                    <span
                        @class([
                            'flex items-center justify-center rounded-full',
                        ])
                        style="width:48px; height:48px; border-radius:9999px;
                            background: {{ match($action['color']) {
                                'primary' => 'var(--sim-primary-soft, #EAF2FB)',
                                'success' => 'var(--sim-green-soft, #E9F5EE)',
                                'warning' => 'var(--sim-orange-soft, #FDF2E1)',
                                'danger' => 'var(--sim-red-soft, #FCEAEA)',
                                default => '#EEF2FF',
                            } }};
                            color: {{ match($action['color']) {
                                'primary' => 'var(--sim-primary-dark, #0F3D6E)',
                                'success' => 'var(--sim-green, #2E8B57)',
                                'warning' => '#B9760F',
                                'danger' => 'var(--sim-red, #D32F2F)',
                                default => '#4338CA',
                            } }};">
                        <x-filament::icon :icon="$action['icon']" style="width:24px; height:24px;" />
                    </span>

                    <span style="font-size:14px; font-weight:700; color:var(--sim-text, #1E293B);">
                        {{ $action['label'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
