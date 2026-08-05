@php
    // Avatar TIDAK lagi memakai dropdown (x-filament::dropdown + teleport).
    // Dropdown yang di-teleport ke <body> sebelumnya kadang gagal
    // ter-render ulang / error saat berpindah halaman lewat navigasi
    // Livewire (wire:navigate), karena node yang di-teleport tertinggal
    // di luar area yang di-morph. Sekarang avatar cukup jadi shortcut
    // langsung ke halaman Profile — sederhana dan tidak pernah error.
    $user = filament()->auth()->user();
    $profileUrl = filament()->getProfileUrl();
@endphp

{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_BEFORE) }}

<a
    href="{{ $profileUrl }}"
    aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
    title="{{ filament()->getUserName($user) }}"
    class="shrink-0"
    style="display:flex;"
>
    <x-filament-panels::avatar.user :user="$user" />
</a>

{{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::USER_MENU_AFTER) }}
