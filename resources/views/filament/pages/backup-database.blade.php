<x-filament-panels::page>
    <div class="space-y-6">

        <div class="sim-backup-row" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:24px; align-items:stretch;">
            <div class="fi-section rounded-xl bg-white p-6 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">Backup Data</h2>

                    <button
                        type="button"
                        title="Tutorial &amp; Panduan Backup"
                        x-on:click="$dispatch('open-modal', { id: 'tata-cara-backup' })"
                        class="fi-icon-btn flex items-center justify-center rounded-lg w-8 h-8 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    >
                        <x-filament::icon icon="heroicon-o-ellipsis-vertical" style="width:1.25rem;height:1.25rem;" />
                    </button>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Membuat salinan (backup) database <code>.sql</code> beserta seluruh dokumen &amp; foto profil
                    yang tersimpan di sistem. Pilih salah satu tujuan penyimpanan di bawah — begitu diklik,
                    backup langsung berjalan dan tersimpan otomatis di server yang dipilih.
                </p>

                <div class="flex flex-col sm:flex-row gap-3">
                    <x-filament::button
                        wire:click="jalankanBackup('lokal')"
                        wire:loading.attr="disabled"
                        wire:target="jalankanBackup"
                        icon="heroicon-o-computer-desktop"
                        color="primary"
                    >
                        <span wire:loading.remove wire:target="jalankanBackup('lokal')">Backup ke Server Lokal (192.168.1.9)</span>
                        <span wire:loading wire:target="jalankanBackup('lokal')">Sedang memproses...</span>
                    </x-filament::button>

                    <x-filament::button
                        wire:click="jalankanBackup('jaringan')"
                        wire:loading.attr="disabled"
                        wire:target="jalankanBackup"
                        icon="heroicon-o-server-stack"
                        color="gray"
                    >
                        <span wire:loading.remove wire:target="jalankanBackup('jaringan')">Backup ke Server Cadangan (192.168.1.10)</span>
                        <span wire:loading wire:target="jalankanBackup('jaringan')">Menyalin ke server cadangan...</span>
                    </x-filament::button>
                </div>
            </div>

            {{-- Card Backup File Dokumen: khusus Super Admin (Admin IT), sesuai canAccess() halaman ini --}}
            <div class="fi-section rounded-xl bg-white p-6 dark:bg-gray-900">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Backup File</h2>
                <p class="text-sm text-gray-500 mb-4">
                    Membuat salinan (backup) khusus untuk file dokumen yang diupload (tanpa database),
                    dipadatkan menjadi satu file <code>.zip</code>. Pilih salah satu tujuan penyimpanan di bawah.
                </p>

                <div class="flex flex-col gap-3">
                    <x-filament::button
                        wire:click="jalankanBackupFile('lokal')"
                        wire:loading.attr="disabled"
                        wire:target="jalankanBackupFile"
                        icon="heroicon-o-computer-desktop"
                        color="primary"
                    >
                        <span wire:loading.remove wire:target="jalankanBackupFile('lokal')">Backup ke Server Lokal (192.168.1.9)</span>
                        <span wire:loading wire:target="jalankanBackupFile('lokal')">Sedang memproses...</span>
                    </x-filament::button>

                    <x-filament::button
                        wire:click="jalankanBackupFile('jaringan')"
                        wire:loading.attr="disabled"
                        wire:target="jalankanBackupFile"
                        icon="heroicon-o-server-stack"
                        color="gray"
                    >
                        <span wire:loading.remove wire:target="jalankanBackupFile('jaringan')">Backup ke Server Cadangan (192.168.1.10)</span>
                        <span wire:loading wire:target="jalankanBackupFile('jaringan')">Menyalin ke server cadangan...</span>
                    </x-filament::button>
                </div>
            </div>

            <div class="fi-wi-stats-overview-stat rounded-xl bg-white p-5 dark:bg-gray-900 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Penyimpanan</span>
                    <span class="fi-wi-stats-overview-stat-icon">
                        <x-filament::icon icon="heroicon-o-server-stack" style="width: 1.25rem; height: 1.25rem;" />
                    </span>
                </div>
                <div class="fi-wi-stats-overview-stat-value mt-2">{{ $ukuranPenyimpanan }}</div>
                <p class="fi-wi-stats-overview-stat-description mt-1">Total ukuran file di storage</p>
            </div>
        </div>

        <style>
            @media (max-width: 1280px) {
                .sim-backup-row { grid-template-columns: 1fr 1fr !important; }
            }
            @media (max-width: 768px) {
                .sim-backup-row { grid-template-columns: 1fr !important; }
            }
        </style>

        {{-- ===================== TUTORIAL (modal, dibuka lewat tombol titik tiga) ===================== --}}
        <x-filament::modal id="tata-cara-backup" width="2xl" alignment="start">
            <x-slot name="heading">
                Cara Kerja &amp; Panduan Backup
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-2 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-computer-desktop" style="width:1.1rem;height:1.1rem;" />
                        Backup ke Server Lokal (192.168.1.9)
                    </h3>
                    <ol class="list-decimal list-inside space-y-1.5 text-gray-600 dark:text-gray-300">
                        <li>Membuat dump database (<code>.sql</code>) dan menyimpannya di server ini juga (komputer yang menjalankan web SIMARSIP).</li>
                        <li>Cocok untuk backup harian yang cepat, tapi <strong>tetap berisiko</strong> kalau komputer/hard disk server ini rusak — data dokumen &amp; database ikut hilang bersamaan.</li>
                        <li>File hasil backup muncul di tabel &ldquo;Riwayat Backup Database&rdquo; di bawah, bisa langsung diunduh atau dihapus.</li>
                    </ol>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-800 dark:text-white mb-2 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-server-stack" style="width:1.1rem;height:1.1rem;" />
                        Backup ke Server Cadangan (192.168.1.10)
                    </h3>
                    <ol class="list-decimal list-inside space-y-1.5 text-gray-600 dark:text-gray-300">
                        <li>Sama seperti Backup Lokal, database di-dump dulu di server ini.</li>
                        <li>Setelah itu, <strong>seluruh folder <code>storage</code></strong> (dump SQL, dokumen, foto profil) otomatis disalin (mirror) ke folder share <code>\\192.168.1.10\simarsip_storage\storage</code> di komputer/server lain.</li>
                        <li><strong>Disarankan</strong> — kalau server utama (192.168.1.9) rusak/mati, salinan lengkap tetap ada di komputer lain.</li>
                    </ol>
                </div>
            </div>

            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4">
                <h4 class="font-semibold text-amber-800 dark:text-amber-300 mb-2 flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width:1.1rem;height:1.1rem;" />
                    Kalau &ldquo;Backup ke Server Cadangan&rdquo; gagal/muncul peringatan
                </h4>
                <p class="text-amber-700 dark:text-amber-200 mb-2">
                    Backup database di server lokal <strong>tetap berhasil</strong> walau penyalinan ke server cadangan gagal — jadi data
                    Anda tetap aman. Penyebab paling umum kegagalan menyalin ke server cadangan:
                </p>
                <ol class="list-decimal list-inside space-y-1 text-amber-700 dark:text-amber-200">
                    <li>
                        <strong>Folder share belum di-share dengan benar</strong> di komputer 192.168.1.10 — pastikan folder
                        <code>SIMARSIP_STORAGE</code> di-share dengan izin <em>Read/Write</em> untuk akun yang dipakai, atau untuk
                        <em>Everyone</em> kalau jaringan kantor tertutup/terpercaya.
                    </li>
                    <li>
                        <strong>Akun web server (Apache) belum &ldquo;kenal&rdquo; ke share tersebut</strong> — beda dengan saat Anda
                        menjalankan <code>robocopy</code> manual lewat Command Prompt (pakai akun Windows Anda sendiri). Solusinya,
                        di komputer server (192.168.1.9), buka Command Prompt <strong>sebagai user yang sama dengan yang menjalankan
                        Apache/XAMPP</strong> lalu jalankan sekali:
                        <code class="block bg-white dark:bg-gray-800 rounded px-2 py-1 mt-1 border border-amber-200 dark:border-amber-700">net use \\192.168.1.10\simarsip_storage /persistent:yes</code>
                        supaya kredensial jaringan tersimpan permanen untuk proses Apache juga.
                    </li>
                    <li>
                        <strong>Komputer 192.168.1.10 sedang mati/tidak terhubung ke jaringan</strong> — pastikan komputer tujuan
                        menyala dan berada di jaringan LAN yang sama sebelum mengklik tombol backup.
                    </li>
                    <li>
                        Path server cadangan bisa diganti (kalau IP-nya berubah) lewat pengaturan <code>BACKUP_NETWORK_PATH</code>
                        di file <code>.env</code> pada server, tanpa perlu ubah kode aplikasi.
                    </li>
                </ol>
            </div>
        </x-filament::modal>

        <div class="fi-section rounded-xl bg-white p-6 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Riwayat Backup Database</h2>

            @if (empty($daftarBackup))
                <p class="text-sm text-gray-400">Belum ada backup yang dibuat.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 font-semibold">Nama File</th>
                                <th class="py-2 font-semibold">Ukuran</th>
                                <th class="py-2 font-semibold">Tanggal</th>
                                <th class="py-2 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarBackup as $backup)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2">{{ $backup['nama'] }}</td>
                                    <td class="py-2">{{ $backup['ukuran'] }}</td>
                                    <td class="py-2">{{ $backup['tanggal'] }}</td>
                                    <td class="py-2 flex gap-2">
                                        <x-filament::button size="xs" color="success"
                                            wire:click="unduh('{{ $backup['path'] }}')">
                                            Unduh
                                        </x-filament::button>
                                        <x-filament::button size="xs" color="danger"
                                            wire:click="hapus('{{ $backup['path'] }}')"
                                            wire:confirm="Yakin ingin menghapus backup ini?">
                                            Hapus
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="fi-section rounded-xl bg-white p-6 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Riwayat Backup File</h2>

            @if (empty($daftarBackupFile))
                <p class="text-sm text-gray-400">Belum ada backup file yang dibuat.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 font-semibold">Nama File</th>
                                <th class="py-2 font-semibold">Ukuran</th>
                                <th class="py-2 font-semibold">Tanggal</th>
                                <th class="py-2 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarBackupFile as $backup)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2">{{ $backup['nama'] }}</td>
                                    <td class="py-2">{{ $backup['ukuran'] }}</td>
                                    <td class="py-2">{{ $backup['tanggal'] }}</td>
                                    <td class="py-2 flex gap-2">
                                        <x-filament::button size="xs" color="success"
                                            wire:click="unduh('{{ $backup['path'] }}')">
                                            Unduh
                                        </x-filament::button>
                                        <x-filament::button size="xs" color="danger"
                                            wire:click="hapus('{{ $backup['path'] }}')"
                                            wire:confirm="Yakin ingin menghapus backup ini?">
                                            Hapus
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>