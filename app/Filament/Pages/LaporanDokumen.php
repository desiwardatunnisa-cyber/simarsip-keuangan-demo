<?php
namespace App\Filament\Pages;
use App\Models\Document;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
class LaporanDokumen extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Dokumen';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.laporan-dokumen';

    /**
     * Tambahkan breadcrumb "Laporan Dokumen > List" di bawah judul halaman,
     * konsisten dengan pola breadcrumb di halaman Arsip Dokumen.
     */
    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => 'Laporan Dokumen',
            'List',
        ];
    }

    /**
     * Tombol export dipindah ke header actions standar Filament (posisi &
     * gaya sama seperti tombol "Upload Dokumen" di Arsip Dokumen, dsb),
     * bukan lagi dropdown custom yang menempel di atas tabel.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('exportExcel')
                    ->label('Export Excel (.xlsx)')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn () => $this->exportExcel()),

                Actions\Action::make('exportPdf')
                    ->label('Export PDF / Print')
                    ->icon('heroicon-o-printer')
                    ->action(fn () => $this->exportPdf()),
            ])
                ->label('Export Laporan')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Data Laporan Dokumen')
            ->query(
                // Sama seperti Arsip Dokumen: Admin IT & Kabag lihat semua,
                // Admin Bagian hanya departemennya sendiri, Staff hanya uploadnya sendiri
                Document::query()->visibleTo(auth()->user())
            )
            ->columns([
                Tables\Columns\TextColumn::make('judul_dokumen')->label('Judul Dokumen')->searchable(),
                Tables\Columns\TextColumn::make('category.nama_kategori')->label('Kategori')->badge(),
                Tables\Columns\TextColumn::make('nomor_referensi')->label('No. Referensi'),
                Tables\Columns\TextColumn::make('tanggal_dokumen')->label('Tgl Dokumen')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diupload Oleh')
                    ->formatStateUsing(fn ($record) => view('filament.tables.columns.uploader', ['user' => $record->user])->render())
                    ->html(),
                Tables\Columns\TextColumn::make('created_at')->label('Waktu Upload')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'nama_kategori'),
                Tables\Filters\Filter::make('periode')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->native(false),
                        Forms\Components\DatePicker::make('sampai')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['dari'], fn ($q, $date) => $q->whereDate('tanggal_dokumen', '>=', $date))
                            ->when($data['sampai'], fn ($q, $date) => $q->whereDate('tanggal_dokumen', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Ambil data laporan sesuai filter yang sedang aktif di tabel.
     * Dipakai bersama oleh semua jenis export (CSV, Excel, PDF/Print).
     */
    protected function ambilDataLaporan()
    {
        return $this->getFilteredTableQuery()->with(['category', 'user'])->get();
    }

    // Export laporan ke file Excel (.xlsx) yang rapi & profesional pakai PhpSpreadsheet
    public function exportExcel(): StreamedResponse
    {
        $data = $this->ambilDataLaporan();

        if ($data->isEmpty()) {
            Notification::make()->title('Tidak ada data untuk diexport')->warning()->send();
            abort(response()->noContent());
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Dokumen');

        // Judul laporan
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'LAPORAN ARSIP DOKUMEN KEUANGAN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'PT. PG Rajawali I - Unit Krebet Baru | Dicetak: ' . now()->format('d F Y H:i') . ' WIB');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('666666');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header tabel (baris 4)
        $headers = ['No', 'Judul Dokumen', 'Kategori', 'No. Referensi', 'Tanggal Dokumen', 'Diupload Oleh', 'Waktu Upload'];
        $kolom = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($headers as $i => $judulKolom) {
            $sheet->setCellValue($kolom[$i] . '4', $judulKolom);
        }
        $sheet->getStyle('A4:G4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('00346F');
        $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(22);

        // Isi data
        $baris = 5;
        foreach ($data as $i => $doc) {
            $sheet->setCellValue('A' . $baris, $i + 1);
            $sheet->setCellValue('B' . $baris, $doc->judul_dokumen);
            $sheet->setCellValue('C' . $baris, $doc->category->nama_kategori ?? '-');
            $sheet->setCellValue('D' . $baris, $doc->nomor_referensi ?? '-');
            $sheet->setCellValue('E' . $baris, optional($doc->tanggal_dokumen)->format('d-m-Y') ?? '-');
            $sheet->setCellValue('F' . $baris, $doc->user->name ?? '-');
            $sheet->setCellValue('G' . $baris, $doc->created_at->format('d-m-Y H:i'));
            $sheet->getStyle('A' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $baris++;
        }
        $barisTerakhir = $baris - 1;

        // Border seluruh tabel
        $sheet->getStyle('A4:G' . $barisTerakhir)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Zebra striping tipis biar gampang dibaca per baris
        for ($r = 5; $r <= $barisTerakhir; $r++) {
            if ($r % 2 === 0) {
                $sheet->getStyle('A' . $r . ':G' . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F5');
            }
        }

        // Auto width tiap kolom
        foreach ($kolom as $k) {
            $sheet->getColumnDimension($k)->setAutoSize(true);
        }

        $namaFile = 'laporan-dokumen-' . now()->format('Y-m-d_H-i') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        Notification::make()->title('Export Excel dimulai, file akan terdownload otomatis')->success()->send();

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // Buka tab baru berisi versi cetak (bisa langsung Print, atau "Save as PDF" lewat dialog print browser)
    public function exportPdf(): void
    {
        $data = $this->ambilDataLaporan();

        if ($data->isEmpty()) {
            Notification::make()->title('Tidak ada data untuk diexport')->warning()->send();
            return;
        }

        $html = view('filament.pages.cetak-laporan', [
            'data' => $data,
            'dicetakOleh' => auth()->user()->name,
        ])->render();

        Notification::make()->title('Membuka tampilan cetak / PDF di tab baru...')->success()->send();

        $this->dispatch('buka-cetak-laporan', html: $html);
    }
}