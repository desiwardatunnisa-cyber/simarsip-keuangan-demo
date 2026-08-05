<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

{{-- mammoth.js: dipakai untuk preview file Word (.doc/.docx) di halaman View
     Dokumen. Dimuat di sini (global, sekali per halaman lewat render hook
     HEAD_END) supaya file blade halaman View Dokumen tidak perlu tag
     <script> sendiri — komponen Livewire cuma boleh punya 1 elemen root. --}}
<script src="https://cdn.jsdelivr.net/npm/mammoth@1.7.0/mammoth.browser.min.js" defer></script>

<style>
    /* =====================================================================
       SIMARSIP KEUANGAN — ENTERPRISE DESIGN SYSTEM
       PT PG Rajawali I Unit Krebet Baru
       Satu sumber token untuk seluruh tampilan. Tidak mengubah struktur,
       route, model, atau logic — murni lapisan visual di atas Filament v3.
       ===================================================================== */
    :root {
        /* --- Brand palette (sesuai identitas perusahaan) --- */
        --sim-primary: #1B5FA8;         /* Primary Blue */
        --sim-primary-dark: #0F3D6E;    /* Dark Blue */
        --sim-primary-soft: #EAF2FB;    /* tint utk background/active state */
        --sim-orange: #F59E0B;          /* Warning (palet mockup) */
        --sim-orange-soft: #FDF2E1;
        --sim-green: #16A34A;           /* Success (palet mockup) */
        --sim-green-soft: #E9F5EE;
        --sim-red: #DC2626;             /* Danger (palet mockup) */
        --sim-red-soft: #FCEAEA;

        /* --- Neutral / surface --- */
        --sim-bg: #F8FAFC;
        --sim-surface: #FFFFFF;
        --sim-surface-alt: #F1F5F9;
        --sim-border: #E2E8F0;          /* Border (palet mockup) */
        --sim-text: #1F2937;            /* Text Primary (palet mockup) */
        --sim-text-muted: #64748B;      /* Text Secondary (palet mockup) */
        --sim-text-faint: #94A3B8;

        /* --- Sidebar Dark Steel (palet mockup) --- */
        --sim-sidebar-bg: #243447;
        --sim-sidebar-hover: #30475E;
        --sim-sidebar-active: #2563EB;
        --sim-sidebar-icon: #B7C5D6;

        /* --- Typography --- */
        --sim-font: 'Plus Jakarta Sans', 'Inter', ui-sans-serif, system-ui, sans-serif;

        /* --- Radius scale --- */
        --sim-radius-card: 1rem;      /* rounded-xl */
        --sim-radius-input: 0.5rem;   /* rounded-lg */
        --sim-radius-full: 9999px;    /* rounded-full (avatar/badge) */

        /* --- Soft shadow scale --- */
        --sim-shadow-sm: 0 1px 2px rgba(15, 61, 110, 0.05);
        --sim-shadow-card: 0 1px 3px rgba(15, 61, 110, 0.06), 0 8px 24px -12px rgba(15, 61, 110, 0.12);
        --sim-shadow-hover: 0 12px 28px -10px rgba(15, 61, 110, 0.20);
        --sim-shadow-pop: 0 20px 45px -12px rgba(15, 61, 110, 0.28);

        /* --- Motion --- */
        --sim-ease: cubic-bezier(0.4, 0, 0.2, 1);
        --sim-speed: 180ms;

        /* Legacy aliases kept so older blade partials (welcome-widget, laporan,
           uploader) that still reference --corp-* keep resolving correctly. */
        --font-family: var(--sim-font);
        --corp-primary: var(--sim-primary-dark);
        --corp-primary-container: var(--sim-primary);
        --corp-on-surface: var(--sim-text);
        --corp-on-surface-variant: var(--sim-text-muted);
        --corp-outline: var(--sim-text-faint);
        --corp-outline-variant: var(--sim-border);
        --corp-surface: var(--sim-bg);
        --corp-surface-container: var(--sim-surface-alt);
        --corp-surface-container-high: var(--sim-border);
        --corp-secondary-container: var(--sim-primary-soft);
        --corp-on-secondary-container: var(--sim-primary-dark);
    }

    * { font-family: var(--sim-font) !important; }

    html, body { overflow-x: hidden; }

    body { background: var(--sim-bg) !important; color: var(--sim-text); }

    ::selection { background: var(--sim-primary-soft); color: var(--sim-primary-dark); }

    /* Halus-kan semua transisi bawaan Filament, tanpa mengubah komponennya */
    .fi-sidebar-item-button,
    .fi-btn,
    .fi-icon-btn,
    .fi-badge,
    .fi-ta-row,
    .fi-wi-stats-overview-stat,
    .fi-section,
    .fi-dropdown-list-item,
    .fi-tabs-item {
        transition: background-color var(--sim-speed) var(--sim-ease),
                    border-color var(--sim-speed) var(--sim-ease),
                    box-shadow var(--sim-speed) var(--sim-ease),
                    transform var(--sim-speed) var(--sim-ease),
                    color var(--sim-speed) var(--sim-ease) !important;
    }

    @keyframes sim-fade-in {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fi-main > * { animation: sim-fade-in 260ms var(--sim-ease); }

    /* ===================== SIDEBAR ===================== */
    /* Sidebar sekarang bertema Dark Steel (lihat komponen
       filament-panels::sidebar untuk detail warna & perilaku mini/expand).
       Override lama yang memaksa background putih dihapus agar tidak
       menimpa tema baru. */
    .fi-sidebar {
        background: var(--sim-sidebar-bg) !important;
        border-right: 1px solid rgba(255,255,255,.08) !important;
        box-shadow: none !important;
    }

    /* Catatan: styling header/logo sidebar (.rajawali-sidebar-header,
       .rajawali-brand, .rajawali-brand-icon, .rajawali-brand-text, dst)
       sepenuhnya diatur di resources/views/vendor/filament-panels/
       components/sidebar/index.blade.php agar satu sumber kebenaran saja
       untuk tinggi baris header (harus selalu sinkron dengan topbar). */

    .fi-sidebar-nav {
        padding-top: 1rem;
        padding-bottom: 1rem;
        padding-left: 1.25rem !important;
        padding-right: 1.25rem !important;
    }

    .fi-sidebar-nav-groups { gap: 1.75rem; }

    /* Semua item & label grup sejajar pada margin kiri/kanan yang sama
       dengan header sidebar (1.25rem), supaya tidak ada elemen yang
       terlihat menjorok/bergeser. */
    .fi-sidebar-group-label,
    .fi-sidebar-item-button {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .fi-sidebar-group-label {
        color: var(--sim-text-muted) !important;
        font-weight: 700 !important;
        font-size: 10.5px !important;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .fi-sidebar-item-button {
        color: var(--sim-text-muted) !important;
        border-radius: 0.625rem !important;
        font-weight: 500 !important;
    }

    .fi-sidebar-item-icon { color: var(--sim-text-faint) !important; transition: color var(--sim-speed) var(--sim-ease); }

    .fi-sidebar-item-button:hover {
        background: var(--sim-surface-alt) !important;
        transform: translateX(2px);
    }

    /* Active indicator: garis aksen di kiri menu, bukan cuma background */
    .fi-sidebar-item-active .fi-sidebar-item-button {
        background: var(--sim-primary-soft) !important;
        color: var(--sim-primary-dark) !important;
        font-weight: 700 !important;
        box-shadow: none !important;
        position: relative;
    }

    .fi-sidebar-item-active .fi-sidebar-item-button::before {
        content: "";
        position: absolute;
        left: -0.75rem;
        top: 8%;
        height: 84%;
        width: 3px;
        border-radius: var(--sim-radius-full);
        background: var(--sim-primary);
    }

    .fi-sidebar-item-active .fi-sidebar-item-icon { color: var(--sim-primary-dark) !important; }

    .fi-sidebar-item-button .fi-badge {
        background: var(--sim-orange-soft) !important;
        color: #B9760F !important;
        font-weight: 700 !important;
    }

    /* ===================== TOPBAR ===================== */
    .fi-topbar nav {
        background: rgba(248, 250, 252, 0.92) !important;
        backdrop-filter: blur(6px) !important;
        border-bottom: 1px solid var(--sim-border);
        box-shadow: none !important;
    }

    /* ===================== KARTU / SECTION ===================== */
    .fi-section,
    .fi-ta-ctn,
    .fi-modal-window {
        border-radius: var(--sim-radius-card) !important;
        border: 1px solid var(--sim-border) !important;
        box-shadow: var(--sim-shadow-card) !important;
        background: var(--sim-surface) !important;
    }

    .fi-section:hover { box-shadow: var(--sim-shadow-hover) !important; }

    .fi-modal-window { box-shadow: var(--sim-shadow-pop) !important; animation: sim-fade-in 200ms var(--sim-ease); }

    .fi-section-header-heading,
    .fi-header-heading {
        font-weight: 700 !important;
        color: var(--sim-text) !important;
        letter-spacing: -0.01em;
    }

    /* ===================== STAT / KPI WIDGETS ===================== */
    .fi-wi-stats-overview-stat {
        border-radius: var(--sim-radius-card) !important;
        border: 1px solid var(--sim-border) !important;
        box-shadow: var(--sim-shadow-card) !important;
        background: linear-gradient(165deg, #FFFFFF 0%, var(--sim-surface-alt) 130%) !important;
        padding: 1.25rem !important;
        position: relative;
        overflow: hidden;
    }

    /* "Progress indicator" tipis di dasar kartu KPI — aksen visual, bukan data */
    .fi-wi-stats-overview-stat::after {
        content: "";
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--sim-primary), var(--sim-orange));
        opacity: .8;
    }

    .fi-wi-stats-overview-stat:hover {
        border-color: var(--sim-primary) !important;
        box-shadow: var(--sim-shadow-hover) !important;
        transform: translateY(-2px);
    }

    .fi-wi-stats-overview-stat-value {
        font-weight: 800 !important;
        font-size: 1.75rem !important;
        color: var(--sim-text) !important;
    }

    .fi-wi-stats-overview-stat-icon {
        width: 3rem !important;
        height: 3rem !important;
        border-radius: var(--sim-radius-full) !important;
        background: var(--sim-primary-soft) !important;
        color: var(--sim-primary-dark) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .fi-wi-stats-overview-stat-description {
        font-size: 11px !important;
        text-transform: uppercase !important;
        letter-spacing: .04em !important;
        color: var(--sim-text-muted) !important;
    }

    /* "• Online X/Y" adalah jumlah pengguna yang sedang aktif — tetap
       ditampilkan hijau (mengikuti descriptionColor('success') di widget
       PHP-nya) alih-alih tertimpa abu-abu oleh aturan default di atas. */
    .fi-wi-stats-overview-stat-description.fi-color-success {
        color: var(--sim-green) !important;
        font-weight: 800 !important;
    }

    /* Warna kontekstual untuk kartu KPI berdasarkan warna Filament yang dipakai
       di widget (success/warning/danger) — tanpa mengubah PHP widget-nya. */
    .fi-wi-stats-overview-stat.fi-color-success .fi-wi-stats-overview-stat-icon {
        background: var(--sim-green-soft) !important; color: var(--sim-green) !important;
    }
    .fi-wi-stats-overview-stat.fi-color-warning .fi-wi-stats-overview-stat-icon {
        background: var(--sim-orange-soft) !important; color: #B9760F !important;
    }
    .fi-wi-stats-overview-stat.fi-color-danger .fi-wi-stats-overview-stat-icon {
        background: var(--sim-red-soft) !important; color: var(--sim-red) !important;
    }

    /* ===================== LOGIN PAGE ===================== */
    .fi-simple-main-ctn {
        border-radius: var(--sim-radius-card) !important;
        box-shadow: var(--sim-shadow-pop) !important;
        border: 1px solid var(--sim-border) !important;
    }

    /* ===================== BUTTON ===================== */
    .fi-btn.fi-color-primary {
        background: var(--sim-primary) !important;
        font-weight: 700 !important;
        border-radius: var(--sim-radius-input) !important;
        box-shadow: var(--sim-shadow-sm) !important;
    }

    .fi-btn.fi-color-primary:hover {
        background: var(--sim-primary-dark) !important;
        box-shadow: var(--sim-shadow-hover) !important;
        transform: translateY(-1px);
    }

    /* Font tombol dibuat lebih tegas (weight lebih berat & tracking sedikit
       lebih rapat) supaya lebih terbaca sebagai aksi, tanpa jadi berlebihan
       atau memakai font lain. */
    .fi-btn,
    .fi-icon-btn {
        border-radius: var(--sim-radius-input) !important;
        font-weight: 700 !important;
        letter-spacing: 0.01em;
    }

    .fi-btn .fi-btn-label {
        font-weight: 700 !important;
    }

    .fi-icon-btn:hover { background: var(--sim-surface-alt) !important; transform: scale(1.05); }

    /* ===================== INPUT ===================== */
    .fi-input,
    .fi-select-input,
    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="search"],
    textarea {
        border-radius: var(--sim-radius-input) !important;
        border-color: var(--sim-border) !important;
        transition: border-color var(--sim-speed) var(--sim-ease), box-shadow var(--sim-speed) var(--sim-ease);
    }

    .fi-input:focus,
    input:focus {
        border-color: var(--sim-primary) !important;
        box-shadow: 0 0 0 3px var(--sim-primary-soft) !important;
    }

    /* ===================== USER MENU (Profile dropdown) ===================== */
    .rajawali-menu-avatar {
        width: 44px !important;
        height: 44px !important;
        flex-shrink: 0;
    }

    .fi-dropdown-panel {
        border-radius: var(--sim-radius-card) !important;
        box-shadow: var(--sim-shadow-pop) !important;
        border: 1px solid var(--sim-border) !important;
        overflow: hidden;
    }

    /* ===================== AVATAR ===================== */
    .fi-avatar,
    .fi-user-avatar,
    .fi-user-menu-trigger img {
        border-radius: var(--sim-radius-full) !important;
    }

    .fi-user-menu-trigger {
        background: var(--sim-primary) !important;
        border-radius: var(--sim-radius-full) !important;
    }

    /* ===================== BADGE / STATUS (pill) ===================== */
    .fi-badge {
        font-weight: 700 !important;
        border-radius: var(--sim-radius-full) !important;
        font-size: 11px !important;
    }

    .fi-badge.fi-color-success { background: var(--sim-green-soft) !important; color: var(--sim-green) !important; }
    .fi-badge.fi-color-warning { background: var(--sim-orange-soft) !important; color: #B9760F !important; }
    .fi-badge.fi-color-danger  { background: var(--sim-red-soft) !important; color: var(--sim-red) !important; }
    .fi-badge.fi-color-primary { background: var(--sim-primary-soft) !important; color: var(--sim-primary-dark) !important; }

    /* ===================== TABEL ===================== */
    .fi-ta-table thead th {
        background: var(--sim-surface-alt) !important;
        font-weight: 700 !important;
        font-size: 11px !important;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--sim-text-muted) !important;
        border-bottom: 1px solid var(--sim-border) !important;
    }

    .fi-ta-row:nth-child(even) { background: #FBFCFE; }

    .fi-ta-row:hover {
        background: var(--sim-primary-soft) !important;
    }

    .fi-ta-row td { border-color: var(--sim-border) !important; }

    /* ===================== DATA TABLE HEADER (STANDARDIZED) =====================
       Menyatukan baris Judul Tabel + Search/Filter/View/Aksi menjadi SATU baris,
       konsisten di seluruh halaman yang memakai Filament Table. Murni CSS —
       tidak mengubah struktur Blade, komponen, atau logic apa pun; hanya
       mengubah arah flex dari kolom (bertingkat) menjadi baris (sejajar). */
    .fi-ta-header-ctn {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        justify-content: space-between !important;
        row-gap: 0.75rem;
        column-gap: 1rem;
        border-bottom: 1px solid var(--sim-border) !important;
    }

    /* Karena sekarang sejajar dalam satu baris, hilangkan garis pembatas
       (divide-y) yang tadinya memisahkan baris judul & baris toolbar. */
    .fi-ta-header-ctn > div {
        border: none !important;
    }

    .fi-ta-header {
        flex: 0 1 auto;
        padding: 0.875rem 1.5rem !important;
        min-height: 3.25rem;
    }

    .fi-ta-header-heading {
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
        white-space: nowrap;
    }

    /* Sembunyikan sub-teks dinamis di bawah judul tabel (mis. "Menampilkan
       2 dari 2 dokumen.") — cukup Judul Tabel saja yang tampil. */
    .fi-ta-header-description {
        display: none !important;
    }

    .fi-ta-header-toolbar {
        flex: 1 1 auto;
        padding: 0.875rem 1.5rem !important;
        min-height: 3.25rem;
        justify-content: flex-end !important;
    }

    /* Sembunyikan teks jumlah data pada paginasi (mis. "Showing 1 to 10 of
       50 results.") di seluruh tabel — tombol halaman tetap tampil. */
    .fi-pagination-overview {
        display: none !important;
    }

    /* ===================== CHART CARDS ===================== */
    .fi-wi-chart { border-radius: var(--sim-radius-card) !important; }

    /* ===================== NOTIFIKASI ===================== */
    /* Judul saat notifikasi dibuka dibuat polos: abu-abu & berat normal,
       tidak tebal/gelap mencolok seperti bawaan Filament. */
    .fi-no-notification-title {
        color: var(--sim-text-muted) !important;
        font-weight: 500 !important;
    }

    /* ===================== SIMARSIP FOOTER ===================== */
    .sim-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
        margin-top: auto;
        border-top: 1px solid var(--sim-border);
        background: var(--sim-bg);
        font-size: 12px;
        color: var(--sim-text-muted);
        /* Halaman tanpa sidebar (mis. Profile) memakai "simple layout" yang
           membungkus konten dalam flex-column ber-"items-center", sehingga
           elemen anak default-nya shrink-to-content (footer jadi sempit &
           di tengah). "stretch" di sini memaksa footer tetap selebar
           halamannya sendiri, sama seperti di halaman ber-sidebar. */
        width: 100%;
        align-self: stretch;
        box-sizing: border-box;
    }

    .sim-footer strong { color: var(--sim-text); font-weight: 700; }

    /* ===================== ICONS ===================== */
    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined' !important;
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        font-size: 22px;
        line-height: 1;
        vertical-align: middle;
    }

    /* Reduced motion: hormati preferensi pengguna */
    @media (prefers-reduced-motion: reduce) {
        *, .fi-main > * { animation: none !important; transition: none !important; }
    }

    /* ===================== TOMBOL (semua tombol di seluruh web) =====================
       Teks tombol dibuat lebih tegas/bold — kesannya lebih profesional &
       rapi (tombol "Back", "Save", "Lihat Detail", "Upload Dokumen", dst). */
    .fi-btn .fi-btn-label,
    .fi-icon-btn,
    .fi-link {
        font-weight: 700 !important;
    }

    /* ===================== PANEL NOTIFIKASI ===================== */
    /* Panel "Notifications" (lonceng di topbar) sebelumnya ikut menggelapkan
       SELURUH halaman di belakangnya (termasuk judul halaman seperti
       "Dashboard" jadi terlihat abu-abu pudar) — sama seperti modal besar
       lain. Untuk panel notifikasi kecil ini kita hilangkan lapisan gelap
       itu saja (modal/slide-over lain seperti "Lihat Detail" TIDAK
       terpengaruh, overlay gelapnya tetap seperti biasa di sana), supaya
       judul halaman di belakangnya tetap terlihat normal/gelap seperti
       semestinya.
     */
    .fi-modal[data-fi-modal-id="database-notifications"] .fi-modal-close-overlay {
        display: none !important;
    }
</style>

<script>
    // ===================== SESI/HALAMAN "EXPIRED" =====================
    // Bawaan Livewire: kalau ADA request Livewire apa pun (termasuk
    // wire:poll di widget-widget dashboard yang jalan otomatis di
    // belakang layar tiap beberapa detik) yang gagal karena token/sesi
    // sudah basi (HTTP 419), Livewire memunculkan dialog native browser
    // "This page has expired. Would you like to refresh the page?"
    // dengan tombol OK/Cancel — ini BUKAN dari tombol Logout, tapi bisa
    // muncul tiba-tiba kapan saja saat sedang membuka halaman (termasuk
    // saat pengguna sedang mengarahkan kursor ke tombol Logout, sehingga
    // terlihat seperti "gara-gara" diklik Logout).
    //
    // Di sini dialog itu dimatikan total dan digantikan dengan tindakan
    // yang lebih masuk akal: karena sesinya memang sudah tidak valid,
    // pengguna otomatis diarahkan langsung ke halaman Login tanpa perlu
    // konfirmasi apa pun — jadi baik saat polling latar belakang expired
    // MAUPUN saat menekan tombol apa pun (termasuk Logout), pengguna
    // langsung "keluar" ke halaman Login tanpa dialog yang mengganggu.
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status === 419) {
                    preventDefault();
                    window.location.href = @js(filament()->getLoginUrl());
                }
            });
        });
    });
</script>