@php
    $navigation = filament()->getNavigation();
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div style="display:flex; min-height:100vh; width:100%; background:#F8F9FA;">
        <x-filament-panels::sidebar :navigation="$navigation" />

        <div
            x-cloak
            x-data="{}"
            x-on:click="$store.sidebar.close()"
            x-show="$store.sidebar.isOpen"
            x-transition.opacity.300ms
            class="lg:hidden"
            style="position:fixed; inset:0; z-index:29; background:rgba(10,15,20,0.5);"
        ></div>

        <div class="rajawali-main-ctn" style="display:flex; width:100%; flex:1 1 auto; flex-direction:column;">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_BEFORE, scopes: $livewire?->getRenderHookScopes()) }}

            <x-filament-panels::topbar :navigation="$navigation" :livewire="$livewire" />

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_AFTER, scopes: $livewire?->getRenderHookScopes()) }}

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_BEFORE, scopes: $livewire?->getRenderHookScopes()) }}

            <main class="fi-main" style="margin-inline:auto; height:100%; width:100%; padding:24px;">
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, scopes: $livewire?->getRenderHookScopes()) }}

                {{ $slot }}

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_END, scopes: $livewire?->getRenderHookScopes()) }}
            </main>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_AFTER, scopes: $livewire?->getRenderHookScopes()) }}

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire?->getRenderHookScopes()) }}
        </div>
    </div>
</x-filament-panels::layout.base>

@verbatim
<style>
    .rajawali-main-ctn {
        margin-inline-start: 0;
        transition: margin-inline-start .25s ease;
    }
    /* Sidebar desktop default mini (80px). Saat di-hover dan melebar jadi
       288px, konten utama (.rajawali-main-ctn) OTOMATIS ikut geser ke kanan
       mengikuti lebar sidebar saat itu juga — supaya tidak ada bagian
       konten yang ketutup/kepotong sidebar. */
    @media (min-width: 1024px) {
        .rajawali-main-ctn {
            margin-inline-start: 80px;
        }
        .rajawali-sidebar:hover ~ .rajawali-main-ctn {
            margin-inline-start: 288px;
        }
    }
</style>
@endverbatim