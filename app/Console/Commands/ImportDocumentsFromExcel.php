<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportDocumentsFromExcel extends Command
{
    /**
     * Contoh pemakaian:
     * php artisan documents:import storage/app/import/data.xlsx storage/app/import/files --user_id=1
     */
    protected $signature = 'documents:import
                            {excel : Path ke file excel (misal: storage/app/import/data.xlsx)}
                            {folder : Path folder yang berisi file fisik dokumen (dicari rekursif)}
                            {--user_id=1 : ID user pemilik dokumen di kolom user_id}';

    protected $description = 'Import dokumen dari file Excel (kode, judul, kategori, nama file) beserta file fisiknya ke Simarsip';

    public function handle(): int
    {
        $excelPath  = base_path($this->argument('excel'));
        $folderPath = rtrim(base_path($this->argument('folder')), '/');
        $userId     = $this->option('user_id');

        if (!file_exists($excelPath)) {
            $this->error("File Excel tidak ditemukan: {$excelPath}");
            return self::FAILURE;
        }

        if (!is_dir($folderPath)) {
            $this->error("Folder file fisik tidak ditemukan: {$folderPath}");
            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $allRows = $sheet->toArray(null, true, true, true); // keyed by nomor baris asli (1, 2, 3, ...)

        // Cari baris pertama yang TIDAK kosong untuk dijadikan header
        // (supaya aman kalau ada baris kosong di atas sebelum header)
        $headerRowNumber = null;
        foreach ($allRows as $rowNumber => $rowValues) {
            $hasContent = count(array_filter($rowValues, fn($v) => trim((string) $v) !== '')) > 0;
            if ($hasContent) {
                $headerRowNumber = $rowNumber;
                break;
            }
        }

        if ($headerRowNumber === null) {
            $this->error('File Excel tampak kosong, tidak ada baris berisi data.');
            return self::FAILURE;
        }

        $headerRow = $allRows[$headerRowNumber];
        $header = array_map(fn($v) => strtolower(trim((string) $v)), $headerRow);
        $colLetterByName = array_flip($header);

        // Ambil semua baris SETELAH baris header sebagai data
        $rows = array_filter($allRows, fn($rowNumber) => $rowNumber > $headerRowNumber, ARRAY_FILTER_USE_KEY);

        // DEBUG: tampilkan nama kolom yang terbaca dari file Excel
        $this->info('Kolom yang terbaca dari Excel:');
        foreach ($header as $letter => $name) {
            $this->line("  [{$letter}] => \"{$name}\"");
        }
        $this->newLine();

        $getCell = function (array $row, string $name) use ($colLetterByName) {
            $letter = $colLetterByName[$name] ?? null;
            return $letter ? trim((string) ($row[$letter] ?? '')) : '';
        };

        // Index semua file fisik dalam folder (tanpa ekstensi, huruf kecil) untuk pencocokan cepat
        $physicalFiles = collect(File::allFiles($folderPath))
            ->keyBy(fn($f) => strtolower(pathinfo($f->getFilename(), PATHINFO_FILENAME)));

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $kode     = $getCell($row, 'kode');
            $judul    = $getCell($row, 'judul');
            $kategori = $getCell($row, 'kategori');
            $namaFile = $getCell($row, 'nama file');
            $nominal  = $getCell($row, 'nominal');

            // Kalau judul kosong, pakai nama file sebagai judul (tanpa ekstensi kalau ada)
            if ($judul === '' && $namaFile !== '') {
                $judul = pathinfo($namaFile, PATHINFO_FILENAME);
            }

            // Khusus kategori Kasbon: judul dibentuk dari kode_nominal (angka polos, tanpa kata kasbon karena sudah ada di kolom Kategori)
            if (stripos($kategori, 'kasbon') !== false && $kode !== '' && $nominal !== '') {
                $nominalBersih = preg_replace('/[^0-9]/', '', $nominal);
                $judul = $kode . '_' . $nominalBersih;
            }

            if ($judul === '' || $namaFile === '') {
                $skipped++;
                continue;
            }

            // Lewati kalau nomor referensi ini sudah ada di database (cegah duplikat)
            if ($kode !== '' && Document::where('nomor_referensi', $kode)->exists()) {
                $this->warn("Lewati (kode {$kode} sudah pernah diimport): {$namaFile}");
                $skipped++;
                continue;
            }

            // Cari file fisik: exact match dulu, kalau tidak ada coba partial match
            $key = strtolower($namaFile);
            $match = $physicalFiles->get($key)
                ?? $physicalFiles->first(fn($f, $k) => str_contains($k, $key) || str_contains($key, $k));

            if (!$match) {
                $this->warn("Lewati (file fisik tidak ditemukan): {$namaFile}");
                $skipped++;
                continue;
            }

            // Cari kategori, buat baru kalau belum ada
            $category = Category::firstOrCreate(['nama_kategori' => $kategori ?: 'Tanpa Kategori']);

            // Salin file fisik ke storage publik Simarsip
            $storedPath = 'documents/' . $match->getFilename();
            Storage::disk('public')->put($storedPath, file_get_contents($match->getPathname()));

            Document::create([
                'user_id'          => $userId,
                'category_id'      => $category->id,
                'judul_dokumen'    => $judul,
                'nomor_referensi'  => $kode,
                'nama_file_asli'   => $match->getFilename(),
                'path_file'        => $storedPath, // tipe_file & ukuran_file otomatis terisi lewat event booted() di Model
                'tanggal_dokumen'  => now(),
            ]);

            $imported++;
            $this->line("✔ Diimport: {$judul}");
        }

        $this->newLine();
        $this->info("Selesai! Berhasil import: {$imported} dokumen. Dilewati: {$skipped} baris.");

        return self::SUCCESS;
    }
}