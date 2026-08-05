<?php
namespace App\Filament\Widgets;
use App\Filament\Concerns\HasDashboardYearFilter;
use App\Models\Document;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestDocumentsWidget extends BaseWidget
{
    use HasDashboardYearFilter;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.latest-documents-widget';

    /**
     * Judul "Riwayat Dokumen" + dropdown Tahun sudah ditulis manual di
     * blade custom widget ini — kosongkan heading bawaan Table supaya
     * tidak muncul dobel (Filament otomatis pakai nama class sebagai
     * fallback heading kalau tidak di-null-kan secara eksplisit).
     */
    protected function getTableHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return '';
    }

    /**
     * Dropdown Tahun di card ini adalah pemilik filter GLOBAL Dashboard.
     * Setiap kali berubah: simpan ke session + broadcast ke seluruh widget
     * (Statistik, Aktivitas Login, Bar Chart, Donut Chart) tanpa reload.
     */
    public function updatedSelectedYear(string $value): void
    {
        session(['dashboard_tahun_filter' => $value]);
        $this->dispatch('dashboard-tahun-changed', tahun: $value);
    }

    /**
     * Satu tabel besar di Dashboard, sama untuk semua role (datanya sudah
     * dibatasi lewat scope visibleTo()). Hanya menampilkan dokumen yang
     * SUDAH TERVERIFIKASI, mengikuti filter Tahun global. Murni tampilan —
     * tidak ada tombol Approval di sini; Verifikasi/Revisi dilakukan di
     * halaman Arsip Dokumen (tabel Menunggu Verifikasi).
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->visibleTo(auth()->user())
                    ->where('status', 'approved')
                    ->when($this->tahunTerpilih(), fn ($query, $tahun) => $query->whereYear('created_at', $tahun))
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rowNumber')
                    ->label('No.')
                    ->state(static fn ($rowLoop) => $rowLoop->iteration ?? '-'),

                Tables\Columns\TextColumn::make('judul_dokumen')->label('Nama Dokumen')->wrap(),
                Tables\Columns\TextColumn::make('category.nama_kategori')->label('Kategori')->badge(),

                Tables\Columns\TextColumn::make('user.departemen')
                    ->label('Divisi')
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '-')
                    ->visible(fn () => auth()->user()?->isAdminIT() ?? false)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Upload Oleh')
                    ->formatStateUsing(fn ($record) => view('filament.tables.columns.uploader', ['user' => $record->user])->render())
                    ->html(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'approved' => 'Terverifikasi',
                        'revisi' => 'Perlu Revisi',
                        default => 'Menunggu Verifikasi',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'revisi' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')->label('Tanggal Upload')->dateTime('d M Y H:i')->since(),
            ])
            ->recordUrl(fn (Document $record) => \App\Filament\Resources\DocumentResource::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Download')
                    ->url(fn (Document $record) => route('documents.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada data')
            ->emptyStateDescription('Silakan upload dokumen terlebih dahulu.')
            ->emptyStateIcon('heroicon-o-document-plus')
            ->emptyStateActions([
                Tables\Actions\Action::make('upload')
                    ->label('Upload Dokumen')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('primary')
                    ->url(fn () => \App\Filament\Resources\DocumentResource::getUrl('create')),
            ]);
    }
}
