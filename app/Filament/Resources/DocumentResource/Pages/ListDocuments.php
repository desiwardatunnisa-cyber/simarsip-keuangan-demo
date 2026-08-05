<?php
namespace App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\DocumentResource\Pages\CreateDocument;
use App\Filament\Widgets\MenungguVerifikasiWidget;
use App\Models\Category;
use App\Models\Document;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            MenungguVerifikasiWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Upload Dokumen'),
            Actions\Action::make('uploadFolder')
                ->label('Upload Folder')
                ->icon('heroicon-o-folder-arrow-down')
                ->color('gray')
                ->modalHeading('Upload Folder / Banyak File Sekaligus')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori (berlaku untuk semua file)')
                        ->options(Category::pluck('nama_kategori', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\DatePicker::make('tanggal_dokumen')
                        ->label('Tanggal Dokumen (opsional, berlaku untuk semua)')
                        ->native(false),
                    Forms\Components\FileUpload::make('files')
                        ->label('Pilih Folder atau Banyak File')
                        ->multiple()
                        ->required()
                        ->disk('public')
                        ->directory('documents')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(20480)
                        ->maxFiles(200)
                        ->storeFileNamesIn('files_names')
                        ->extraInputAttributes(['webkitdirectory' => true])
                        ->helperText('Klik lalu pilih FOLDER (di Chrome/Edge akan otomatis ambil semua isi folder), atau pilih beberapa file sekaligus (Ctrl+klik). Judul dokumen otomatis mengikuti nama file asli, tanpa tambahan apa pun.'),
                ])
                ->action(function (array $data): void {
                    $namaAsliMap = $data['files_names'] ?? [];
                    $namaAsliList = array_values($namaAsliMap);
                    $berhasil = 0;
                    $i = 0;
                    $isAdmin = auth()->user()?->isAdmin() ?? false;
                    foreach ($data['files'] as $key => $path) {
                        $namaAsli = $namaAsliMap[$key] ?? ($namaAsliList[$i] ?? basename($path));
                        $judul = pathinfo($namaAsli, PATHINFO_FILENAME);
                        $document = Document::create(array_merge([
                            'user_id' => auth()->id(),
                            'category_id' => $data['category_id'],
                            'judul_dokumen' => $judul,
                            'nama_file_asli' => $namaAsli,
                            'path_file' => $path,
                            'tanggal_dokumen' => $data['tanggal_dokumen'] ?? null,
                        ], $isAdmin ? [
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ] : []));
                        CreateDocument::renameFileSesuaiId($document);
                        $berhasil++;
                        $i++;
                    }
                    Notification::make()
                        ->title("$berhasil dokumen berhasil diupload dari folder")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('importExcel')
                ->label('Import Dokumen dari Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->modalHeading('Import Dokumen dari Excel')
                ->modalWidth('lg')
                ->form([
                    Forms\Components\Placeholder::make('download_template')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<a href="' . asset('templates/template-import-dokumen.xlsx') . '" download style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;">
                                📥 Download Template Format Excel
                            </a>'
                        )),
                    Forms\Components\FileUpload::make('excel_file')
                        ->label('File Excel (kode, judul, kategori, nama file, nominal)')
                        ->required()
                        ->disk('local')
                        ->directory('imports/excel')
                        ->visibility('private')
                        ->helperText('Upload file .xlsx yang berisi data dokumen.'),
                    Forms\Components\FileUpload::make('physical_files')
                        ->label('File Dokumen Fisik (PDF/Word/dll)')
                        ->required()
                        ->multiple()
                        ->disk('local')
                        ->directory('imports/files')
                        ->visibility('private')
                        ->storeFileNamesIn('physical_files_names')
                        ->maxSize(20480)
                        ->maxFiles(500)
                        ->extraInputAttributes(['webkitdirectory' => true])
                        ->helperText('Klik lalu pilih FOLDER (di Chrome/Edge akan otomatis ambil semua isi folder), atau pilih beberapa file sekaligus (Ctrl+klik). Nama file harus sesuai kolom "nama file" di Excel.'),
                ])
                ->action(function (array $data): void {
                    $excelPath = Storage::disk('local')->path($data['excel_file']);

                    // Petakan file yang diupload: nama_asli (tanpa ekstensi, huruf kecil) => [path lengkap, nama asli]
                    $namaAsliMap = $data['physical_files_names'] ?? [];
                    $namaAsliList = array_values($namaAsliMap);
                    $physicalFiles = collect();
                    $i = 0;
                    foreach ($data['physical_files'] as $key => $storedPath) {
                        $namaAsli = $namaAsliMap[$key] ?? ($namaAsliList[$i] ?? basename($storedPath));
                        $physicalFiles[strtolower(pathinfo($namaAsli, PATHINFO_FILENAME))] = [
                            'full_path' => Storage::disk('local')->path($storedPath),
                            'nama_asli' => $namaAsli,
                        ];
                        $i++;
                    }

                    $spreadsheet = IOFactory::load($excelPath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $allRows = $sheet->toArray(null, true, true, true);

                    // Cari baris pertama yang tidak kosong sebagai header
                    $headerRowNumber = null;
                    foreach ($allRows as $rowNumber => $rowValues) {
                        $hasContent = count(array_filter($rowValues, fn ($v) => trim((string) $v) !== '')) > 0;
                        if ($hasContent) {
                            $headerRowNumber = $rowNumber;
                            break;
                        }
                    }

                    if ($headerRowNumber === null) {
                        Notification::make()->title('File Excel tampak kosong')->danger()->send();
                        return;
                    }

                    $headerRow = $allRows[$headerRowNumber];
                    $header = array_map(fn ($v) => strtolower(trim((string) $v)), $headerRow);
                    $colLetterByName = array_flip($header);
                    $rows = array_filter($allRows, fn ($rowNumber) => $rowNumber > $headerRowNumber, ARRAY_FILTER_USE_KEY);

                    $getCell = function (array $row, string $name) use ($colLetterByName) {
                        $letter = $colLetterByName[$name] ?? null;
                        return $letter ? trim((string) ($row[$letter] ?? '')) : '';
                    };

                    $imported = 0;
                    $skippedList = [];
                    $isAdmin = auth()->user()?->isAdmin() ?? false;

                    foreach ($rows as $row) {
                        $kode = $getCell($row, 'kode');
                        $judul = $getCell($row, 'judul');
                        $kategori = $getCell($row, 'kategori');
                        $namaFile = $getCell($row, 'nama file');
                        $nominal = $getCell($row, 'nominal');

                        if ($judul === '' && $namaFile !== '') {
                            $judul = pathinfo($namaFile, PATHINFO_FILENAME);
                        }

                        // Khusus kategori Kasbon: judul dibentuk dari kode_nominal (angka polos, tanpa kata kasbon karena sudah ada di kolom Kategori)
                        if (stripos($kategori, 'kasbon') !== false && $kode !== '' && $nominal !== '') {
                            $nominalBersih = preg_replace('/[^0-9]/', '', $nominal);
                            $judul = $kode.'_'.$nominalBersih;
                        }

                        if ($judul === '' || $namaFile === '') {
                            $skippedList[] = $namaFile ?: '(baris kosong)';
                            continue;
                        }

                        // Lewati kalau nomor referensi ini sudah ada di database (cegah duplikat)
                        if ($kode !== '' && \App\Models\Document::where('nomor_referensi', $kode)->exists()) {
                            $skippedList[] = "{$namaFile} (kode {$kode} sudah pernah diimport sebelumnya)";
                            continue;
                        }

                        $key = strtolower($namaFile);
                        $match = $physicalFiles->get($key)
                            ?? $physicalFiles->first(fn ($f, $k) => str_contains($k, $key) || str_contains($key, $k));

                        if (! $match) {
                            $skippedList[] = $namaFile.' (file fisik tidak ditemukan)';
                            continue;
                        }

                        $category = Category::firstOrCreate(['nama_kategori' => $kategori ?: 'Tanpa Kategori']);

                        $storedPath = 'documents/'.$match['nama_asli'];
                        Storage::disk('public')->put($storedPath, file_get_contents($match['full_path']));

                        $document = Document::create(array_merge([
                            'user_id' => auth()->id(),
                            'category_id' => $category->id,
                            'judul_dokumen' => $judul,
                            'nomor_referensi' => $kode,
                            'nama_file_asli' => $match['nama_asli'],
                            'path_file' => $storedPath,
                            'tanggal_dokumen' => now(),
                        ], $isAdmin ? [
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ] : []));

                        CreateDocument::renameFileSesuaiId($document);

                        $imported++;
                    }

                    // Bersihkan file sementara hasil upload
                    Storage::disk('local')->delete($data['excel_file']);
                    foreach ($data['physical_files'] as $storedPath) {
                        Storage::disk('local')->delete($storedPath);
                    }

                    Notification::make()
                        ->title("Import selesai: {$imported} dokumen berhasil diimport")
                        ->body(count($skippedList) ? 'Dilewati ('.count($skippedList).'): '.implode(', ', $skippedList) : null)
                        ->success()
                        ->send();
                }),
        ];
    }
}