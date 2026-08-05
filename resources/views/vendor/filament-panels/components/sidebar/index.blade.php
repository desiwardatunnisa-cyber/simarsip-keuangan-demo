@props(['navigation'])

<aside
    x-data="{}"
    x-cloak
    x-bind:class="$store.sidebar.isOpen ? 'rajawali-sidebar-open' : ''"
    class="fi-sidebar rajawali-sidebar"
>
    <div class="rajawali-sidebar-header">
        @if ($homeUrl = filament()->getHomeUrl())
            <a href="{{ $homeUrl }}" style="display:flex; width:100%; text-decoration:none;">
                <x-filament-panels::logo />
            </a>
        @else
            <x-filament-panels::logo />
        @endif
    </div>

    <nav class="fi-sidebar-nav rajawali-sidebar-nav" style="scrollbar-gutter: stable">
        <ul style="display:flex; flex-direction:column; gap:24px; list-style:none; margin:0; padding:0;">
            @foreach ($navigation as $group)
                <li>
                    @if ($label = $group->getLabel())
                        <p class="rajawali-nav-group-label" style="padding:0 8px 8px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; margin:0;">
                            {{ $label }}
                        </p>
                    @endif

                    <ul style="display:flex; flex-direction:column; gap:4px; list-style:none; margin:0; padding:0;">
                        @foreach ($group->getItems() as $item)
                            @php
                                $active = $item->isActive();
                                $icon = $item->getIcon();
                                $materialIcon = match ($icon) {
                                    'heroicon-o-archive-box' => 'inventory_2',
                                    'heroicon-o-document-text', 'heroicon-o-document-chart-bar' => 'analytics',
                                    'heroicon-o-tag' => 'category',
                                    'heroicon-o-users' => 'group',
                                    'heroicon-o-clipboard-document-list' => 'history',
                                    'heroicon-o-circle-stack' => 'backup',
                                    'heroicon-o-eye' => 'monitoring',
                                    'heroicon-o-arrow-up-tray' => 'upload',
                                    'heroicon-o-chart-bar-square' => 'analytics',
                                    'heroicon-o-home' => 'dashboard',
                                    default => 'circle',
                                };
                            @endphp
                            <li>
                                <a href="{{ $item->getUrl() }}"
                                    x-on:click="window.matchMedia('(max-width: 1024px)').matches && $store.sidebar.close()"
                                    class="rajawali-nav-item {{ $active ? 'rajawali-nav-item-active' : '' }}"
                                    title="{{ $item->getLabel() }}"
                                >
                                    @if ($icon)
                                        <span class="material-symbols-outlined rajawali-nav-icon" style="font-size:22px; flex-shrink:0;">{{ $materialIcon }}</span>
                                    @endif
                                    <span class="rajawali-nav-label" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $item->getLabel() }}</span>
                                    @if ($badge = $item->getBadge())
                                        <span class="rajawali-nav-badge" style="border-radius:9999px; padding:2px 8px; font-size:11px; font-weight:600;">
                                            {{ $badge }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="rajawali-sidebar-footer" style="border-top:1px solid #C2C6D3; padding:16px;">
        <p style="text-align:center; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.02em; margin:0;">
            SIMARSIP Dokumen Akuntansi &amp; Keuangan
        </p>
    </div>
</aside>

@verbatim
<style>
    /* ===== Dark Steel sidebar (#243447) ===== */
    .rajawali-sidebar {
        position: fixed;
        inset-block: 0;
        inset-inline-start: 0;
        z-index: 31;
        display: flex;
        height: 100vh;
        width: 288px;
        flex-direction: column;
        border-right: 1px solid rgba(255,255,255,.08);
        background: var(--sim-sidebar-bg, #243447);
        transition: transform .2s ease, width .25s ease, box-shadow .25s ease;
        transform: translateX(-100%);
    }
    .rajawali-sidebar-open {
        transform: translateX(0) !important;
    }

    /* Desktop: mini by default, expand automatically on hover. No hamburger
       toggle is used on desktop — hovering the sidebar area is the trigger. */
    @media (min-width: 1024px) {
        .rajawali-sidebar {
            transform: none !important;
            width: 80px;
            overflow: hidden;
        }
        .rajawali-sidebar:hover {
            width: 288px;
            box-shadow: 6px 0 28px rgba(10,15,25,.35);
        }
    }

    /* ===== Header sidebar: tinggi TETAP di semua kondisi =====
       Tinggi baris brand dipaksa sama persis dengan tinggi Topbar (h-16 =
       4rem pada topbar/index.blade.php) dan TIDAK BERUBAH baik saat sidebar
       collapsed (mini, ikon saja) maupun expanded (hover desktop / mobile,
       ikon + nama aplikasi). Karena tingginya konstan, border-bottom sidebar
       & border-bottom topbar selalu berada pada koordinat Y yang sama,
       membentuk satu garis horizontal lurus dari kiri ke kanan — tidak
       pernah bergeser saat sidebar dibuka/ditutup. */
    .rajawali-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 4rem; /* HARUS sama dengan h-16 pada topbar/index.blade.php */
        border-bottom: 1px solid rgba(255,255,255,.08);
        padding: 0 16px;
        flex-shrink: 0;
        overflow: hidden;
    }
    /* Filament membungkus logo custom dalam .fi-logo dengan tinggi tetap
       (brandLogoHeight) — bebaskan supaya baris brand kita yang menentukan
       tingginya sendiri (4rem, lihat di atas). */
    .rajawali-sidebar-header .fi-logo {
        height: 100% !important;
        width: 100%;
    }
    .rajawali-brand {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        height: 100%;
    }

    /* Logo asli (logo-rajawali.png) — ikon "R", selalu tampil, ukuran tetap
       di semua kondisi supaya tidak ada lompatan ukuran saat hover/buka. */
    .rajawali-brand-icon {
        height: 34px;
        width: 34px;
        object-fit: contain;
        flex-shrink: 0;
    }

    /* Nama aplikasi — disembunyikan saat sidebar desktop collapsed (hanya
       ikon yang tampil, senada dengan label menu lain yang juga
       disembunyikan saat collapsed), tampil saat expanded (hover) atau di
       mobile. Tinggi baris header tidak ikut berubah karena sudah dikunci
       di atas. */
    .rajawali-brand-text {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 1px;
        line-height: 1.2;
        overflow: hidden;
        white-space: nowrap;
        text-align: left;
    }
    @media (min-width: 1024px) {
        .rajawali-sidebar:not(:hover) .rajawali-brand-text {
            display: none;
        }
        .rajawali-sidebar:not(:hover) .rajawali-brand {
            justify-content: center;
        }
    }
    .rajawali-brand-name {
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.03em;
        color: #ffffff;
    }
    .rajawali-brand-sub {
        font-size: 10.5px;
        font-weight: 500;
        color: rgba(255,255,255,.6);
    }

    .rajawali-sidebar-nav {
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 24px 16px;
    }
    .rajawali-nav-group-label {
        color: rgba(226,232,240,.55); /* Border tint dari palet, dipakai sbg label pudar di atas dasar gelap */
        white-space: nowrap;
        overflow: hidden;
    }
    .rajawali-nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        font-weight: 500;
        color: #E2E8F0; /* Border warna dari palet, dipakai sbg teks terang di atas dasar gelap */
        text-decoration: none;
        transition: background-color .15s ease, color .15s ease;
        white-space: nowrap;
    }
    .rajawali-nav-icon {
        color: var(--sim-sidebar-icon, #B7C5D6); /* Icon */
    }
    .rajawali-nav-item:hover {
        background: var(--sim-sidebar-hover, #30475E); /* Sidebar Hover */
        color: #ffffff;
    }
    .rajawali-nav-item:hover .rajawali-nav-icon {
        color: #ffffff;
    }
    .rajawali-nav-item-active,
    .rajawali-nav-item-active:hover {
        background: var(--sim-sidebar-active, #2563EB); /* Active Menu */
        color: #ffffff;
        font-weight: 600;
    }
    .rajawali-nav-item-active .rajawali-nav-icon,
    .rajawali-nav-item-active:hover .rajawali-nav-icon {
        color: #ffffff;
    }
    .rajawali-nav-badge {
        background: rgba(255,255,255,.15);
        color: #ffffff;
    }
    .rajawali-nav-item-active .rajawali-nav-badge {
        background: rgba(255,255,255,.25);
    }

    /* Sembunyikan label teks (grup, item, badge) saat sidebar desktop
       collapsed, supaya hanya ikon yang tampil dan tidak terpotong (wrap). */
    @media (min-width: 1024px) {
        .rajawali-sidebar:not(:hover) .rajawali-nav-label,
        .rajawali-sidebar:not(:hover) .rajawali-nav-badge,
        .rajawali-sidebar:not(:hover) .rajawali-nav-group-label,
        .rajawali-sidebar:not(:hover) .rajawali-sidebar-footer {
            display: none;
        }
        .rajawali-sidebar:not(:hover) .rajawali-nav-item {
            justify-content: center;
            padding-inline: 0;
        }
    }

    .rajawali-sidebar-footer p {
        color: rgba(255,255,255,.4);
    }
    .rajawali-sidebar-footer {
        border-top-color: rgba(255,255,255,.08) !important;
        flex-shrink: 0;
    }
</style>
@endverbatim