<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'judul_dokumen',
        'nomor_referensi',
        'nama_file_asli',
        'path_file',
        'lokasi_penyimpanan',
        'tipe_file',
        'ukuran_file',
        'keterangan',
        'tanggal_dokumen',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'tanggal_dokumen' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Setiap kali path_file diisi/diubah, otomatis hitung tipe & ukuran file
        static::saving(function (Document $document) {
            if ($document->isDirty('path_file') && $document->path_file) {
                $extension = pathinfo($document->path_file, PATHINFO_EXTENSION);
                $document->tipe_file = strtolower($extension);
                $document->nama_file_sistem = basename($document->path_file);

                // File bisa ada di disk "public" (lokal) atau "cadangan" (failover),
                // tergantung lokasi_penyimpanan yang diisi FileFailoverStorage saat upload.
                $disk = \App\Support\FileFailoverStorage::cariDisk($document->path_file, $document->lokasi_penyimpanan);

                if ($disk) {
                    $document->ukuran_file = Storage::disk($disk)->size($document->path_file);
                }
            }
        });

        // Hapus file fisik di storage setiap kali dokumen dihapus dari database
        static::deleting(function (Document $document) {
            \App\Support\FileFailoverStorage::hapus($document->path_file, $document->lokasi_penyimpanan);
        });

        // Kirim notifikasi ke admin bagian terkait & semua kabag saat staff upload dokumen baru
        static::created(function (Document $document) {
            $uploader = $document->user;

            if (! $uploader) {
                return;
            }

            // Konfirmasi ke uploader sendiri (muncul di Notification Center miliknya)
            \Filament\Notifications\Notification::make()
                ->title('Dokumen berhasil diupload')
                ->body("\"{$document->judul_dokumen}\" sedang menunggu verifikasi.")
                ->icon('heroicon-o-document-arrow-up')
                ->success()
                ->sendToDatabase($uploader);

            if (! $uploader->isStaff()) {
                return;
            }

            $approvers = User::where('role', 'admin')
                ->where(function ($q) use ($uploader) {
                    $q->where('bagian', 'kabag')
                        ->orWhere(function ($q2) use ($uploader) {
                            $q2->where('bagian', 'admin_bagian')
                                ->where('departemen', $uploader->departemen);
                        });
                })
                ->get();

            foreach ($approvers as $approver) {
                \Filament\Notifications\Notification::make()
                    ->title('Dokumen Baru Menunggu Persetujuan')
                    ->body("{$uploader->name} mengupload \"{$document->judul_dokumen}\"")
                    ->icon('heroicon-o-document-check')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('lihat')
                            ->label('Lihat & Verifikasi')
                            ->url(\App\Filament\Resources\DocumentResource::getUrl('view', ['record' => $document]))
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($approver);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdminIT()) {
            return $query; // Super Admin IT: lihat semua dokumen
        }

        if ($user->isKabag()) {
            return $query; // Kabag Utama: mengawasi SEMUA departemen, tanpa filter
        }

        if ($user->isAdminBagian()) {
            // Admin Bagian: hanya dokumen dari staff di departemen yang sama
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('departemen', $user->departemen);
            });
        }

        // Staff biasa: hanya dokumen miliknya sendiri
        return $query->where('user_id', $user->id);
    }

    // Helper untuk menampilkan ukuran file dalam format mudah dibaca (KB/MB)
    public function getUkuranFileFormattedAttribute(): string
    {
        $bytes = $this->ukuran_file ?? 0;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1024, 1) . ' KB';
    }
}