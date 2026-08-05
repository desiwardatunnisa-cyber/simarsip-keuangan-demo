<?php
namespace App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Concerns\HasBackButton;
use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ViewDocument extends ViewRecord
{
    use HasBackButton;

    protected static string $resource = DocumentResource::class;
    protected static string $view = 'filament.pages.view-document';

    // Batas baris & kolom yang ditampilkan supaya preview tetap ringan untuk file besar.
    protected int $maxPreviewRows = 100;
    protected int $maxPreviewCols = 15;

    // Sheet spreadsheet yang sedang ditampilkan pada preview (untuk file dengan banyak sheet).
    public int $previewSheetIndex = 0;

    public function selectPreviewSheet(int $index): void
    {
        $this->previewSheetIndex = $index;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->backButtonAction(),
            Actions\EditAction::make()
                ->visible(fn () => DocumentResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => DocumentResource::canDelete($this->record)),
        ];
    }

    public function getUrlPreview(): string
    {
        return Storage::disk('public')->url($this->record->path_file);
    }

    /**
     * Baca isi file Excel/CSV/ODS (.xlsx/.xls/.csv/.ods) dan kembalikan sebagai array
     * baris/kolom untuk ditampilkan sebagai tabel HTML di halaman preview. Mendukung
     * banyak sheet — sheet yang dibaca mengikuti $previewSheetIndex (untuk tab sheet).
     *
     * Return: ['sheetName' => string, 'sheetNames' => array<string>, 'sheetIndex' => int,
     *          'rows' => array<array<string>>, 'truncatedRows' => bool, 'truncatedCols' => bool,
     *          'totalRows' => int] | null (kalau gagal dibaca)
     */
    public function getExcelPreview(): ?array
    {
        $tipe = strtolower($this->record->tipe_file);

        if (! in_array($tipe, ['xlsx', 'xls', 'csv', 'ods'])) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->record->path_file)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($this->record->path_file);

        try {
            $reader = IOFactory::createReaderForFile($fullPath);
            $reader->setReadDataOnly(true);

            $sheetNames = [];
            $sheetIndex = 0;

            if (method_exists($reader, 'listWorksheetNames')) {
                $sheetNames = $reader->listWorksheetNames($fullPath) ?: [];
            }

            if (! empty($sheetNames) && method_exists($reader, 'setLoadSheetsOnly')) {
                // Jaga-jaga kalau index yang tersimpan sudah tidak valid (misalnya
                // file diganti dengan lebih sedikit sheet).
                $sheetIndex = min(max($this->previewSheetIndex, 0), count($sheetNames) - 1);
                $reader->setLoadSheetsOnly($sheetNames[$sheetIndex]);
            }

            $spreadsheet = $reader->load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestDataRow();
            $highestColIndex = $sheet->getHighestDataColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColIndex);

            $truncatedRows = $highestRow > $this->maxPreviewRows;
            $truncatedCols = $highestColIndex > $this->maxPreviewCols;

            $rowLimit = min($highestRow, $this->maxPreviewRows);
            $colLimit = min($highestColIndex, $this->maxPreviewCols);

            $rows = [];
            for ($r = 1; $r <= $rowLimit; $r++) {
                $rowData = [];
                for ($c = 1; $c <= $colLimit; $c++) {
                    // getCellByColumnAndRow() sudah dihapus di PhpSpreadsheet
                    // v2+. Setara dengan getCell([$kolom, $baris]) — urutan
                    // indeks (kolom dulu, baru baris) sama persis seperti
                    // sebelumnya, cuma beda nama method.
                    $cell = $sheet->getCell([$c, $r]);
                    $rowData[] = (string) $cell->getFormattedValue();
                }
                $rows[] = $rowData;
            }

            return [
                'sheetName' => $sheet->getTitle(),
                'sheetNames' => $sheetNames,
                'sheetIndex' => $sheetIndex,
                'rows' => $rows,
                'truncatedRows' => $truncatedRows,
                'truncatedCols' => $truncatedCols,
                'totalRows' => $highestRow,
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}