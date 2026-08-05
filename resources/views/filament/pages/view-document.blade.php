<x-filament-panels::page>
    {{-- Satu state gagalMuat dipakai bersama oleh preview gambar & tombol Download,
         supaya keduanya konsisten dinonaktifkan bareng saat file tidak bisa diakses. --}}
    <div x-data="{ gagalMuat: false }">
    <div class="fi-section rounded-xl bg-white dark:bg-gray-900 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Judul Dokumen</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $record->judul_dokumen }}</p>
            </div>
            <div>
                <p class="text-gray-500">Kategori</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $record->category->nama_kategori ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-500">No. Referensi</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $record->nomor_referensi ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Tanggal Dokumen</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ optional($record->tanggal_dokumen)->format('d M Y') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Diupload Oleh</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $record->user->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Waktu Upload</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $record->created_at->format('d M Y H:i') }}</p>
            </div>
            <div>
                <p class="text-gray-500">Nama File di Sistem</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $record->nama_file_sistem ?? basename($record->path_file) }}</p>
            </div>
            <div>
                <p class="text-gray-500">Ukuran File</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $record->ukuran_file_formatted }}</p>
            </div>
        </div>

        @if ($record->keterangan)
            <div class="mt-4">
                <p class="text-gray-500 text-sm">Keterangan</p>
                <p class="text-gray-800 dark:text-white">{{ $record->keterangan }}</p>
            </div>
        @endif

        @if (session('download_error'))
            <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                {{ session('download_error') }}
            </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('documents.download', $record) }}"
               x-bind:class="gagalMuat && 'opacity-40 pointer-events-none cursor-not-allowed'"
               x-bind:aria-disabled="gagalMuat"
               @click.prevent="if (gagalMuat) { $event.preventDefault(); return; } window.location = @js(route('documents.download', $record))"
               class="fi-btn inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                Download File
            </a>
            <p x-show="gagalMuat" x-cloak class="text-xs text-red-500 mt-2">
                Download dinonaktifkan sementara — file tidak bisa diakses (lihat keterangan di bawah).
            </p>
        </div>
    </div>

    <div class="fi-section rounded-xl bg-white dark:bg-gray-900 p-6">
        <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4">Preview Berkas</h2>

        @php
            $tipeGambar = ['jpg', 'jpeg', 'png', 'webp'];
            $tipeExcel = ['xls', 'xlsx', 'csv', 'ods'];
            $tipeDokumenOffice = ['doc', 'docx'];
            $excelPreview = in_array(strtolower($record->tipe_file), $tipeExcel) ? $this->getExcelPreview() : null;
        @endphp

        @if (in_array(strtolower($record->tipe_file), $tipeGambar))
            <div>
                <img
                    x-show="!gagalMuat"
                    src="{{ $this->getUrlPreview() }}"
                    alt="Preview"
                    class="w-full max-w-2xl rounded-lg border border-gray-200 cursor-zoom-in hover:opacity-90 transition"
                    onclick="document.getElementById('img-lightbox-{{ $record->id }}').classList.remove('hidden')"
                    x-on:error="gagalMuat = true"
                >
                <p x-show="!gagalMuat" class="text-xs text-gray-400 mt-2">Klik gambar untuk memperbesar (zoom).</p>

                {{-- Kalau gambar gagal dimuat (biasanya karena server tempat file ini
                     tersimpan sedang tidak bisa diakses), tampilkan keterangan yang
                     jelas & TIDAK bisa diklik — bukan ikon broken-image browser yang
                     terkesan berantakan. Server mana yang disebut mengikuti kolom
                     lokasi_penyimpanan dokumen ini (lokal/cadangan). --}}
                <div x-show="gagalMuat" x-cloak
                     class="text-center py-10 border border-dashed border-red-300 rounded-lg bg-red-50 dark:bg-red-900/10">
                    <p class="text-red-600 dark:text-red-400 font-bold text-sm tracking-wide">GAGAL MEMUAT PREVIEW</p>
                    <p class="text-red-500 dark:text-red-300 text-xs mt-1">
                        @if ($record->lokasi_penyimpanan === 'cadangan')
                            Server Cadangan (192.168.1.10) sedang tidak bisa diakses.
                        @else
                            Server Lokal (192.168.1.9) sedang tidak bisa diakses.
                        @endif
                        Silakan coba lagi nanti, atau download file untuk melihat isinya.
                    </p>
                </div>
            </div>

            {{-- Lightbox: zoom, fullscreen, download, close --}}
            <div id="img-lightbox-{{ $record->id }}" class="hidden fixed inset-0 z-[9999] bg-black/80 flex items-center justify-center p-4">
                <div class="absolute top-4 right-4 flex gap-2">
                    <a href="{{ route('documents.download', $record) }}" title="Download"
                       class="bg-white/90 hover:bg-white text-gray-800 rounded-lg p-2">
                        ⬇
                    </a>
                    <button type="button" title="Fullscreen"
                        onclick="const el = document.getElementById('img-lightbox-img-{{ $record->id }}'); if (el.requestFullscreen) el.requestFullscreen();"
                        class="bg-white/90 hover:bg-white text-gray-800 rounded-lg p-2">
                        ⛶
                    </button>
                    <button type="button" title="Tutup"
                        onclick="document.getElementById('img-lightbox-{{ $record->id }}').classList.add('hidden')"
                        class="bg-white/90 hover:bg-white text-gray-800 rounded-lg p-2">
                        ✕
                    </button>
                </div>
                <img id="img-lightbox-img-{{ $record->id }}" src="{{ $this->getUrlPreview() }}" alt="Preview"
                     class="max-w-full max-h-full object-contain rounded-lg">
            </div>
        @elseif (strtolower($record->tipe_file) === 'pdf')
            <iframe src="{{ $this->getUrlPreview() }}" class="w-full rounded-lg border border-gray-200" style="height: 75vh;"></iframe>
        @elseif (in_array(strtolower($record->tipe_file), $tipeExcel))
            @if ($excelPreview)
                @if (count($excelPreview['sheetNames'] ?? []) > 1)
                    <div class="flex flex-wrap gap-1.5 mb-3 border-b border-gray-200 pb-2">
                        @foreach ($excelPreview['sheetNames'] as $idx => $name)
                            <button
                                type="button"
                                wire:click="selectPreviewSheet({{ $idx }})"
                                class="{{ $idx === $excelPreview['sheetIndex']
                                    ? 'bg-sky-600 text-white border-sky-600'
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }} text-xs font-semibold px-3 py-1.5 rounded-lg border transition"
                            >
                                {{ $name }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="mb-2 flex flex-wrap items-center justify-between gap-1 text-xs text-gray-500">
                    <span>Sheet: <strong>{{ $excelPreview['sheetName'] }}</strong> &middot; Menampilkan {{ count($excelPreview['rows']) }} dari {{ $excelPreview['totalRows'] }} baris</span>
                    @if ($excelPreview['truncatedRows'] || $excelPreview['truncatedCols'])
                        <span class="text-amber-600">Preview dipotong (file lengkap ada di download)</span>
                    @endif
                </div>
                <div class="overflow-auto border border-gray-200 rounded-lg" style="max-height: 70vh;" wire:loading.class="opacity-50">
                    <table class="min-w-full text-xs border-collapse">
                        <tbody>
                            @foreach ($excelPreview['rows'] as $i => $row)
                                <tr class="{{ $i === 0 ? 'bg-gray-100 font-semibold' : ($i % 2 === 0 ? 'bg-white' : 'bg-gray-50') }}">
                                    @foreach ($row as $cell)
                                        <td class="border border-gray-200 px-2 py-1 whitespace-nowrap text-gray-700">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-500 border border-dashed border-gray-300 rounded-lg">
                    <p>File tidak dapat dibaca untuk preview (mungkin rusak atau formatnya tidak didukung). Silakan download file.</p>
                </div>
            @endif
        @elseif (in_array(strtolower($record->tipe_file), $tipeDokumenOffice))
            {{--
                PENTING: Livewire cuma boleh 1 elemen HTML root per komponen.
                Makanya di sini HANYA ada 1 <div> (tidak ada <script> terpisah
                sebagai sibling) — logic fetch + convert docx/doc ke HTML
                dikerjakan lewat Alpine.js (x-data/x-init), bukan <script>
                mentah, supaya tetap jadi bagian dari elemen yang sama dan
                juga lebih andal saat Livewire me-render ulang bagian ini.
                Library mammoth.js dimuat sekali secara global lewat
                custom-styles.blade.php (render hook HEAD_END), bukan di
                sini, supaya tidak menambah elemen <script> di komponen ini.
            --}}
            <div
                x-data="{
                    loading: true,
                    failed: false,
                    html: '',
                    init() {
                        fetch(@js($this->getUrlPreview()))
                            .then((res) => {
                                if (! res.ok) throw new Error('Gagal mengambil file');
                                return res.arrayBuffer();
                            })
                            .then((buf) => (window.mammoth ? mammoth.convertToHtml({ arrayBuffer: buf }) : Promise.reject('mammoth belum siap')))
                            .then((result) => {
                                if (! result.value || ! result.value.trim()) {
                                    this.failed = true;
                                } else {
                                    this.html = result.value;
                                }
                                this.loading = false;
                            })
                            .catch(() => {
                                this.failed = true;
                                this.loading = false;
                            });
                    }
                }"
                class="border border-gray-200 rounded-lg p-4 overflow-auto bg-white"
                style="max-height: 75vh;"
            >
                <p x-show="loading" class="text-gray-400 text-sm">Memuat preview dokumen…</p>

                <div x-show="!loading && !failed" x-html="html" class="prose prose-sm max-w-none"></div>

                <div x-show="!loading && failed" class="text-center py-10 text-gray-500" x-cloak>
                    <p>
                        File <strong>.{{ $record->tipe_file }}</strong> tidak dapat dipreview di browser
                        @if (strtolower($record->tipe_file) === 'doc')
                            (format Word lama tidak didukung untuk preview)
                        @endif
                        . Silakan download file.
                    </p>
                </div>
            </div>
        @else
            <div class="text-center py-10 text-gray-500 border border-dashed border-gray-300 rounded-lg">
                <p>Preview tidak tersedia. Silakan download file.</p>
            </div>
        @endif
    </div>
    </div>
</x-filament-panels::page>