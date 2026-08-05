<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Category;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Arsip Dokumen';

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    /**
     * Nonaktifkan dropdown "Global Search" (quick-jump di navbar) khusus untuk
     * resource Dokumen. Pencarian dokumen sekarang hanya dilakukan lewat kotak
     * Search di dalam tabel (live filter tabel), bukan lewat dropdown saran
     * yang langsung berpindah halaman.
     */
    protected static bool $isGloballySearchable = false;

    public static function getGloballySearchableAttributes(): array
    {
        return ['judul_dokumen', 'nomor_referensi'];
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdminIT()) {
            return true;
        }

        // Staff hanya bisa edit dokumen miliknya sendiri, selama belum di-ACC
        // (atau sedang berstatus revisi, supaya bisa diperbaiki & dikirim ulang)
        return $record->user_id === $user->id && in_array($record->status, ['pending', 'revisi'], true);
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    /**
     * Cek apakah user yang login berwenang meng-ACC dokumen ini
     * (Kabag: semua departemen. Admin Bagian: departemen yang sama saja)
     */
    public static function canApprove(Document $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isKabag()) {
            return true;
        }

        if ($user->isAdminBagian()) {
            return $record->user?->departemen === $user->departemen;
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Dokumen')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('judul_dokumen')
                        ->label('Judul Dokumen')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(2),

                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'nama_kategori')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('nama_kategori')->required(),
                        ]),

                    Forms\Components\DatePicker::make('tanggal_dokumen')
                        ->label('Tanggal Dokumen')
                        ->native(false),

                    Forms\Components\TextInput::make('nomor_referensi')
                        ->label('No. Referensi (Faktur/Kwitansi)')
                        ->maxLength(100)
                        ->nullable()
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Nomor referensi ini sudah dipakai oleh dokumen lain.',
                        ]),

                    Forms\Components\Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->columnSpan(2)
                        ->rows(2),
                ]),

            Forms\Components\Section::make('Berkas Dokumen')
                ->schema([
                    Forms\Components\FileUpload::make('path_file')
                        ->label('Upload File (PDF/JPG/PNG/Excel/Word)')
                        ->required()
                        ->disk('public')
                        ->directory('documents')
                        // Failover: coba simpan ke storage lokal dulu, kalau gagal
                        // (folder/drive lokal penuh/rusak/permission) otomatis simpan
                        // ke server cadangan lewat jaringan. Lihat FileFailoverStorage.
                        ->saveUploadedFileUsing(
                            fn (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file) =>
                                \App\Support\FileFailoverStorage::simpan($file, 'documents')
                        )
                        // Saat file diganti/dihapus lewat form, pastikan file lama
                        // dihapus dari disk yang benar (lokal ATAU cadangan).
                        ->deleteUploadedFileUsing(
                            fn (string $file) => \App\Support\FileFailoverStorage::hapus($file)
                        )
                        // Kalau file yang SEDANG tersimpan tidak bisa diakses sekarang
                        // (server lokal ATAU cadangan tempat file itu berada lagi
                        // mati/eror), kunci field ganti-file ini saja — field lain di
                        // form (judul, kategori, keterangan, dst) tetap bisa diedit &
                        // disimpan seperti biasa.
                        ->disabled(
                            fn ($record) => $record?->exists
                                && ! \App\Support\FileFailoverStorage::cariDisk((string) $record->path_file, $record->lokasi_penyimpanan)
                        )
                        ->helperText(
                            fn ($record) => ($record?->exists
                                && ! \App\Support\FileFailoverStorage::cariDisk((string) $record->path_file, $record->lokasi_penyimpanan))
                                ? 'File saat ini tidak bisa dimuat/diganti — server tempat file ini tersimpan sedang tidak bisa diakses. Field lain di form ini tetap bisa disimpan; coba ganti file lagi setelah server pulih.'
                                : null
                        )
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->maxSize(20480)
                        ->storeFileNamesIn('nama_file_asli')
                        ->downloadable()
                        ->openable()
                        ->previewable(true),
                ]),

            Forms\Components\Hidden::make('user_id')
                ->default(fn () => Auth::id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Dokumen Terverifikasi')
            ->description(function ($livewire) {
                $total = static::getEloquentQuery()->where('status', 'approved')->count();
                $filtered = $livewire->getFilteredTableQuery()->count();

                return "Menampilkan {$filtered} dari {$total} dokumen.";
            })
            ->searchPlaceholder('Cari judul, kategori, no. referensi, atau nama pengunggah...')
            ->searchDebounce('300ms')
            ->searchOnBlur(false)
            ->query(fn () => static::getEloquentQuery()->where('status', 'approved'))
            ->columns([
                Tables\Columns\TextColumn::make('judul_dokumen')
                    ->label('Judul Dokumen')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.nama_kategori')
                    ->label('Kategori')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn ($record) => match (($record->category_id ?? 0) % 5) {
                        0 => 'info',
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('nomor_referensi')
                    ->label('No. Referensi')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tipe_file')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pdf' => 'danger',
                        'xls', 'xlsx' => 'success',
                        'jpg', 'jpeg', 'png' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('ukuran_file')
                    ->label('Ukuran')
                    ->formatStateUsing(fn ($state) => $state >= 1048576
                        ? round($state / 1048576, 2) . ' MB'
                        : round(($state ?? 0) / 1024, 1) . ' KB'),

                Tables\Columns\TextColumn::make('tanggal_dokumen')
                    ->label('Tgl Dokumen')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diupload Oleh')
                    ->formatStateUsing(fn ($record) => view('filament.tables.columns.uploader', ['user' => $record->user])->render())
                    ->html()
                    ->searchable()
                    ->toggleable(),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'nama_kategori'),

                Tables\Filters\Filter::make('tanggal_dokumen')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->native(false),
                        Forms\Components\DatePicker::make('sampai')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari'], fn ($q, $date) => $q->whereDate('tanggal_dokumen', '>=', $date))
                            ->when($data['sampai'], fn ($q, $date) => $q->whereDate('tanggal_dokumen', '<=', $date));
                    }),
            ])
            ->recordUrl(fn (Document $record) => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-magnifying-glass')
            ->emptyStateHeading(fn ($livewire) => $livewire->hasTableSearch()
                ? 'Tidak ada dokumen yang sesuai dengan kata kunci pencarian'
                : 'Belum ada dokumen terverifikasi')
            ->emptyStateDescription(fn ($livewire) => $livewire->hasTableSearch()
                ? 'Coba gunakan kata kunci lain, atau reset pencarian untuk menampilkan seluruh dokumen.'
                : null)
            ->emptyStateActions([
                Tables\Actions\Action::make('resetPencarian')
                    ->label('Reset Pencarian')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn ($livewire) => $livewire->hasTableSearch())
                    ->action(function ($livewire) {
                        $livewire->resetTableSearch();
                        $livewire->resetTableFiltersForm();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Document $record) => route('documents.download', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (Document $record) => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus yang Dipilih')
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()
            ->whereIn('status', ['pending', 'revisi'])
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()->whereIn('status', ['pending', 'revisi'])->count() > 0
            ? 'warning'
            : null;
    }
} 