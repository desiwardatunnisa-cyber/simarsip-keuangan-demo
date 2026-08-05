@php($events = $this->getEvents())

<x-filament-widgets::widget>
    <x-filament::section class="sim-h-full">
        <x-slot name="heading">Aktivitas Login Saya</x-slot>

        @if ($events->isEmpty())
            <div style="text-align:center; padding:32px 16px; color:var(--sim-text-muted, #64748B);">
                <x-filament::icon icon="heroicon-o-clock" style="width:40px; height:40px; margin:0 auto 8px; opacity:.5;" />
                <p style="font-weight:700; color:var(--sim-text, #1E293B); margin:0 0 4px;">Belum ada aktivitas</p>
                <p style="font-size:13px; margin:0;">Aktivitas login &amp; dokumen Anda akan muncul di sini.</p>
            </div>
        @else
            <div style="display:flex; flex-direction:column;">
                @foreach ($events as $event)
                    <div style="display:flex; gap:12px; padding:10px 0; {{ ! $loop->last ? 'border-bottom:1px dashed var(--sim-border, #E5E7EB);' : '' }}">
                        <span style="
                            flex-shrink:0; width:36px; height:36px; border-radius:9999px;
                            display:flex; align-items:center; justify-content:center;
                            background: {{ match($event['color']) {
                                'success' => 'var(--sim-green-soft, #E9F5EE)',
                                'warning' => 'var(--sim-orange-soft, #FDF2E1)',
                                'danger' => 'var(--sim-red-soft, #FCEAEA)',
                                'primary' => 'var(--sim-primary-soft, #EAF2FB)',
                                'info' => 'var(--sim-primary-soft, #EAF2FB)',
                                default => '#F1F5F9',
                            } }};
                            color: {{ match($event['color']) {
                                'success' => 'var(--sim-green, #2E8B57)',
                                'warning' => '#B9760F',
                                'danger' => 'var(--sim-red, #D32F2F)',
                                'primary' => 'var(--sim-primary, #1B5FA8)',
                                'info' => 'var(--sim-primary-dark, #0F3D6E)',
                                default => 'var(--sim-text-muted, #64748B)',
                            } }};">
                            <x-filament::icon :icon="$event['icon']" style="width:18px; height:18px;" />
                        </span>

                        <div style="flex:1; min-width:0;">
                            <p style="margin:0; font-size:13.5px; font-weight:600; color:var(--sim-text, #1E293B);">
                                {{ $event['label'] }}
                            </p>
                            @if (!empty($event['detail']))
                                <p style="margin:1px 0 0; font-size:12px; color:var(--sim-text-muted, #64748B);">
                                    {{ \Illuminate\Support\Str::limit($event['detail'], 70) }}
                                </p>
                            @endif
                            <p style="margin:2px 0 0; font-size:11.5px; color:var(--sim-text-faint, #94A3B8);">
                                {{ $event['waktu']->translatedFormat('d M Y') }} · {{ $event['waktu']->format('H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
