@php
    // Ringkasan (Jam Masuk/Jam Keluar/Durasi/IP) SENGAJA tidak ditampilkan
    // lagi di sini — data itu sudah ada di baris tabel utama (Monitoring
    // Staff / My Activity), jadi modal ini fokus HANYA menampilkan Riwayat
    // Aktivitas, sebagai satu tabel enterprise penuh (senada persis dengan
    // tabel Riwayat Dokumen / Monitoring Staff), tanpa elemen lain.
    $gabungan = collect();

    foreach ($accessLogs as $log) {
        // Path mentah "/admin/monitoring-staff" diterjemahkan jadi label
        // yang enak dibaca, tanpa tanda "/" — mis. "Monitoring Staff".
        $segmen = collect(explode('/', trim($log->url, '/')))
            ->filter()
            ->reject(fn ($s) => $s === 'admin')
            ->map(fn ($s) => ucwords(str_replace(['-', '_'], ' ', $s)));

        $gabungan->push([
            'waktu' => $log->created_at,
            'aksi_label' => 'Akses Halaman',
            'warna' => 'info',
            'detail' => $segmen->isEmpty() ? 'Dashboard' : $segmen->implode(' › '),
        ]);
    }

    foreach ($auditLogs as $log) {
        $warna = match (strtolower($log->aksi)) {
            'tambah', 'created' => 'success',
            'ubah', 'updated' => 'warning',
            'hapus', 'deleted' => 'danger',
            default => 'gray',
        };

        $labelAksi = match (strtolower($log->aksi)) {
            'created' => 'Tambah',
            'updated' => 'Ubah',
            'deleted' => 'Hapus',
            default => ucfirst($log->aksi),
        };

        $gabungan->push([
            'waktu' => $log->created_at,
            'aksi_label' => $labelAksi,
            'warna' => $warna,
            'detail' => $log->model . ($log->deskripsi ? ' — ' . $log->deskripsi : ''),
        ]);
    }

    $gabungan = $gabungan->sortByDesc('waktu')->values();

    $badgeColors = [
        'info'    => ['bg' => 'var(--sim-primary-soft, #EAF2FB)', 'text' => 'var(--sim-primary-dark, #0F3D6E)'],
        'success' => ['bg' => 'var(--sim-green-soft, #E9F5EE)',   'text' => 'var(--sim-green, #16A34A)'],
        'warning' => ['bg' => 'var(--sim-orange-soft, #FDF2E1)',  'text' => '#B9760F'],
        'danger'  => ['bg' => 'var(--sim-red-soft, #FCEAEA)',     'text' => 'var(--sim-red, #DC2626)'],
        'gray'    => ['bg' => '#F1F5F9',                          'text' => 'var(--sim-text-muted, #64748B)'],
    ];
@endphp

<div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
    <svg style="width:18px; height:18px; color:var(--sim-primary, #1B5FA8); flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
    <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--sim-text, #1F2937);">Riwayat Aktivitas ({{ $gabungan->count() }})</h3>
</div>

{{-- Tabel enterprise: container membulat + border, header abu uppercase,
     baris berselang-seling, hover interaktif — persis gaya tabel
     Riwayat Dokumen / Monitoring Staff. --}}
<div style="border:1px solid var(--sim-border, #E2E8F0); border-radius:var(--sim-radius-card, 0.75rem); overflow:hidden; box-shadow:var(--sim-shadow-card, 0 1px 2px rgba(16,24,40,.04));">
    <div style="max-height:26rem; overflow-y:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13.5px;">
            <thead style="position:sticky; top:0; z-index:1;">
                <tr>
                    <th style="text-align:left; padding:12px 18px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--sim-text-muted, #64748B); background:var(--sim-surface-alt, #F1F5F9); border-bottom:1px solid var(--sim-border, #E2E8F0); white-space:nowrap;">Waktu</th>
                    <th style="text-align:left; padding:12px 18px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--sim-text-muted, #64748B); background:var(--sim-surface-alt, #F1F5F9); border-bottom:1px solid var(--sim-border, #E2E8F0); white-space:nowrap;">Aksi</th>
                    <th style="text-align:left; padding:12px 18px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--sim-text-muted, #64748B); background:var(--sim-surface-alt, #F1F5F9); border-bottom:1px solid var(--sim-border, #E2E8F0);">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gabungan as $item)
                    <tr class="sim-detail-row" style="background:{{ $loop->even ? '#FBFCFE' : '#FFFFFF' }};">
                        <td style="padding:11px 18px; color:var(--sim-text-muted, #64748B); font-size:12.5px; white-space:nowrap; border-bottom:1px solid var(--sim-border, #E2E8F0); vertical-align:middle;">
                            {{ $item['waktu']->format('H:i:s') }}
                        </td>
                        <td style="padding:11px 18px; white-space:nowrap; border-bottom:1px solid var(--sim-border, #E2E8F0); vertical-align:middle;">
                            <span style="display:inline-block; white-space:nowrap; padding:4px 12px; border-radius:9999px; font-size:11px; font-weight:700; background:{{ $badgeColors[$item['warna']]['bg'] }}; color:{{ $badgeColors[$item['warna']]['text'] }};">
                                {{ $item['aksi_label'] }}
                            </span>
                        </td>
                        <td style="padding:11px 18px; color:var(--sim-text, #1F2937); font-weight:500; border-bottom:1px solid var(--sim-border, #E2E8F0); vertical-align:middle;">
                            {{ $item['detail'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="padding:32px 18px; text-align:center; color:var(--sim-text-faint, #94A3B8); font-size:13px;">Tidak ada aktivitas pada sesi ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .sim-detail-row:hover { background: var(--sim-primary-soft, #EAF2FB) !important; }
</style>
