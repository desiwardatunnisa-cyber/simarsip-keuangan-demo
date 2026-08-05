@php
    use Filament\Support\Enums\MaxWidth;
    $livewire ??= null;
    $isLoginPage = $livewire instanceof \Filament\Pages\Auth\Login;
@endphp
<x-filament-panels::layout.base :livewire="$livewire">
    @props([
        'after' => null,
        'heading' => null,
        'subheading' => null,
    ])

    @if ($isLoginPage)
        <style>
    .rajawali-split-shell {
        display: flex;
        width: 100%;
        max-width: 1200px;
        margin: 32px auto;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid #E2E8F0;
        box-shadow: 0 8px 30px rgba(0,52,111,0.10);
        background: #ffffff;
    }
    .rajawali-split-hero {
        flex: 1 1 56%;
        position: relative;
        background:
            linear-gradient(160deg, rgba(0,52,111,0.90) 0%, rgba(0,74,153,0.90) 100%),
            url('{{ asset('images/office-logo.jpeg') }}');
        background-size: cover;
        background-position: center;
        padding: 48px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        color: #ffffff;
        min-height: 620px;
    }
    .rajawali-split-form {
        flex: 1 1 44%;
        padding: 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 320px;
    }
    /* ==== Logo pada panel form login ====
       Header bawaan Filament (logo lama + teks "SIMARSIP" + judul "Sign
       in") dimatikan total lewat aturan .fi-simple-header di bawah, KHUSUS
       saat isLoginPage true, jadi tidak memengaruhi Sidebar atau halaman
       simple lain (mis. reset password). Logo tunggal yang tampil sekarang
       dirender langsung sebagai satu img di login dot blade dot php
       (class rajawali-login-logo), supaya tidak ada logo dobel. */
    .fi-simple-header {
        display: none !important;
    }
    .rajawali-login-logo {
        display: block;
        height: 56px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        margin: 8px auto 10px;
    }
    .rajawali-login-desc {
        margin: 0 0 20px;
        font-size: 13px;
        line-height: 1.6;
        color: #64748B;
        text-align: center;
    }
</style>
@verbatim
<style>
    @media (max-width: 991px) {
        .rajawali-split-shell { flex-direction: column; margin: 0; border-radius: 0; border: none; box-shadow: none; }
        .rajawali-split-hero { display: none; }
        .rajawali-split-form { padding: 32px 24px; }
    }
</style>
@endverbatim
        <div class="fi-simple-layout" style="display:flex; min-height:100vh; background:#F8F9FA; padding:16px;">
            <div class="rajawali-split-shell">
                <div class="rajawali-split-hero">
                    <div>
                        <h1 style="font-size:26px; font-weight:800; line-height:1.35; margin:0 0 16px 0;">
                            SISTEM INFORMASI MANAJEMEN PENGARSIPAN DOKUMEN AKUNTANSI & KEUANGAN
                        </h1>
                        <div style="width:56px; height:4px; background:#ffffff; margin-bottom:20px;"></div>
                        <p style="font-size:14px; opacity:0.9; max-width:400px; line-height:1.7; margin:0;">
                            Digitalisasi Pengarsipan Dokumen Akuntansi & keuangan PT. PG Rajawali I – Unit Krebet Baru.
                        </p>
                    </div>
                    <div style="margin-top:40px; font-size:11px; letter-spacing:0.12em; text-transform:uppercase; opacity:0.6;">
                        PT. PG RAJAWALI I Unit Krebet Baru
                    </div>
                </div>
                <div class="rajawali-split-form">
                    {{ $slot }}
                </div>
            </div>
        </div>
    @else
        <div class="fi-simple-layout flex min-h-screen flex-col items-center">
            @if (($hasTopbar ?? true) && filament()->auth()->check())
                {{-- Halaman "simple" (mis. Profile) TIDAK menampilkan lonceng
                     notifikasi & avatar di pojok kanan atas — sesuai
                     permintaan, di sini cukup 1 tombol "Back" saja
                     yang mengarah balik ke Dashboard. --}}
                <div class="absolute end-0 top-0 flex h-16 items-center gap-x-4 pe-4 md:pe-6 lg:pe-8">
                    <a
                        href="{{ \App\Filament\Pages\Dashboard::getUrl() }}"
                        style="display:inline-flex; align-items:center; font-size:14px; font-weight:700; color:var(--sim-text-muted, #64748B); text-decoration:none; padding:8px 14px; border-radius:var(--sim-radius-input, 0.5rem); border:1px solid var(--sim-border, #E2E8F0); background:var(--sim-surface, #fff);"
                    >
                        Back
                    </a>
                </div>
            @endif
            <div class="fi-simple-main-ctn flex w-full flex-grow items-center justify-center">
                <main
                    @class([
                        'fi-simple-main my-16 w-full bg-white px-6 py-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:rounded-xl sm:px-12',
                        match ($maxWidth ??= (filament()->getSimplePageMaxContentWidth() ?? MaxWidth::Large)) {
                            MaxWidth::ExtraSmall, 'xs' => 'max-w-xs',
                            MaxWidth::Small, 'sm' => 'max-w-sm',
                            MaxWidth::Medium, 'md' => 'max-w-md',
                            MaxWidth::Large, 'lg' => 'max-w-lg',
                            MaxWidth::ExtraLarge, 'xl' => 'max-w-xl',
                            MaxWidth::TwoExtraLarge, '2xl' => 'max-w-2xl',
                            MaxWidth::ThreeExtraLarge, '3xl' => 'max-w-3xl',
                            MaxWidth::FourExtraLarge, '4xl' => 'max-w-4xl',
                            MaxWidth::FiveExtraLarge, '5xl' => 'max-w-5xl',
                            MaxWidth::SixExtraLarge, '6xl' => 'max-w-6xl',
                            MaxWidth::SevenExtraLarge, '7xl' => 'max-w-7xl',
                            MaxWidth::Full, 'full' => 'max-w-full',
                            MaxWidth::MinContent, 'min' => 'max-w-min',
                            MaxWidth::MaxContent, 'max' => 'max-w-max',
                            MaxWidth::FitContent, 'fit' => 'max-w-fit',
                            MaxWidth::Prose, 'prose' => 'max-w-prose',
                            MaxWidth::ScreenSmall, 'screen-sm' => 'max-w-screen-sm',
                            MaxWidth::ScreenMedium, 'screen-md' => 'max-w-screen-md',
                            MaxWidth::ScreenLarge, 'screen-lg' => 'max-w-screen-lg',
                            MaxWidth::ScreenExtraLarge, 'screen-xl' => 'max-w-screen-xl',
                            MaxWidth::ScreenTwoExtraLarge, '2xl' => 'max-w-screen-2xl',
                            default => $maxWidth,
                        },
                    ])
                >
                    {{ $slot }}
                </main>
            </div>
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire?->getRenderHookScopes()) }}
        </div>
    @endif
</x-filament-panels::layout.base>
