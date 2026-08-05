@props([
    'actions' => [],
    'breadcrumbs' => [],
    'heading',
    'subheading' => null,
])

@php
    // Judul halaman (heading) sengaja TIDAK dirender di sini. Ia sudah tampil
    // di topbar global (sejajar dengan Search & Profile) agar konsisten di
    // seluruh halaman. Baris ini hanya menampilkan breadcrumb, subheading,
    // dan tombol aksi milik halaman.
    $hasVisibleContent = filled($breadcrumbs) || filled($subheading) || filled($actions);
@endphp

@if ($hasVisibleContent)
    <header
        {{ $attributes->class(['fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}
    >
        <div>
            @if ($breadcrumbs)
                <x-filament::breadcrumbs
                    :breadcrumbs="$breadcrumbs"
                    class="hidden sm:block"
                />
            @endif

            @if ($subheading)
                <p
                    class="fi-header-subheading mt-2 max-w-2xl text-lg text-gray-600 dark:text-gray-400"
                >
                    {{ $subheading }}
                </p>
            @endif
        </div>

        <div
            @class([
                'flex shrink-0 items-center gap-3',
            ])
        >
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, scopes: $this->getRenderHookScopes()) }}

            @if ($actions)
                <x-filament::actions :actions="$actions" />
            @endif

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER, scopes: $this->getRenderHookScopes()) }}
        </div>
    </header>
@endif
