@props(['navigation'])
<div class="fi-topbar rajawali-topbar">
    <nav class="rajawali-topbar-nav">
        <x-filament::icon-button
            color="gray"
            icon="heroicon-o-bars-3"
            icon-size="lg"
            label="Buka menu"
            x-data="{}"
            x-on:click="$store.sidebar.open()"
            x-show="! $store.sidebar.isOpen"
            class="lg:hidden"
        />
        <x-filament::icon-button
            color="gray"
            icon="heroicon-o-x-mark"
            icon-size="lg"
            label="Tutup menu"
            x-data="{}"
            x-on:click="$store.sidebar.close()"
            x-show="$store.sidebar.isOpen"
            class="lg:hidden"
        />

        @if (filament()->isGlobalSearchEnabled())
            <div style="flex:1 1 auto; max-width:420px;">
                @livewire(Filament\Livewire\GlobalSearch::class)
            </div>
        @endif

        <div style="margin-inline-start:auto; display:flex; align-items:center; gap:8px;">
            @if (filament()->hasDatabaseNotifications())
                @livewire(Filament\Livewire\DatabaseNotifications::class, [
    'lazy' => false
])s
            @endif

            <div style="width:1px; height:32px; background:#C2C6D3; margin-inline:8px;"></div>

            @if (filament()->auth()->check())
                <x-filament-panels::user-menu />
            @endif
        </div>
    </nav>
</div>

@verbatim
<style>
    .rajawali-topbar {
        position: sticky;
        top: 0;
        z-index: 20;
    }
    .rajawali-topbar-nav {
        display: flex;
        align-items: center;
        gap: 16px;
        height: 64px;
        border-bottom: 1px solid #C2C6D3;
        background: #F8F9FA;
        padding-inline: 16px;
    }
    @media (min-width: 768px) {
        .rajawali-topbar-nav { padding-inline: 24px; }
    }
    @media (min-width: 1024px) {
        .rajawali-topbar-nav { padding-inline: 32px; }
    }
</style>
@endverbatim