<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupDatabase extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Backup Database';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.backup-database';

    // Backup database hanya dibutuhkan oleh Admin IT/Super Admin untuk keperluan
    // teknis sistem. Admin Bagian/Kabag tidak perlu dan tidak melihat menu ini.
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminIT() ?? false;
    }

    public array $daftarBackup = [];

    public array $daftarBackupFile = [];

    public string $ukuranPenyimpanan = '0 KB';

    public function mount(): void
    {
        $this->muatDaftarBackup();
        $this->muatDaftarBackupFile();
        $this->hitungUkuranPenyimpanan();
    }

    public function hitungUkuranPenyimpanan(): void
    {
        $totalBytes = collect(Storage::disk('public')->allFiles('documents'))
            ->sum(fn ($file) => Storage::disk('public')->size($file));

        $this->ukuranPenyimpanan = $totalBytes >= 1048576
            ? round($totalBytes / 1048576, 2) . ' MB'
            : round($totalBytes / 1024, 1) . ' KB';
    }

    public function muatDaftarBackup(): void
    {
        Storage::disk('local')->makeDirectory('backups');

        $this->daftarBackup = collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($file) => str_ends_with($file, '.sql'))
            ->sortByDesc(fn ($file) => Storage::disk('local')->lastModified($file))
            ->map(fn ($file) => [
                'nama' => basename($file),
                'path' => $file,
                'ukuran' => round(Storage::disk('local')->size($file) / 1024, 1) . ' KB',
                'tanggal' => date('d M Y H:i', Storage::disk('local')->lastModified($file)),
            ])
            ->values()
            ->toArray();
    }

    public function muatDaftarBackupFile(): void
    {
        Storage::disk('local')->makeDirectory('backups_file');

        $this->daftarBackupFile = collect(Storage::disk('local')->files('backups_file'))
            ->filter(fn ($file) => str_ends_with($file, '.zip'))
            ->sortByDesc(fn ($file) => Storage::disk('local')->lastModified($file))
            ->map(fn ($file) => [
                'nama' => basename($file),
                'path' => $file,
                'ukuran' => round(Storage::disk('local')->size($file) / 1024, 1) . ' KB',
                'tanggal' => date('d M Y H:i', Storage::disk('local')->lastModified($file)),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Path folder share di server cadangan (mis. \\192.168.1.10\simarsip_storage).
     * Diambil dari .env supaya gampang diganti tanpa ubah kode kalau IP/nama
     * share berubah nanti.
     */
    public static function pathServerCadangan(): string
    {
        return rtrim(env('BACKUP_NETWORK_PATH', '\\\\192.168.1.10\\simarsip_storage'), '\\/');
    }

    /**
     * Jalankan backup database (.sql) — selalu dibuat di server lokal dulu.
     * $tujuan menentukan apakah hasilnya (dump .sql + seluruh folder storage:
     * dokumen, avatar, dsb) cuma disimpan lokal ("lokal"), atau juga di-mirror
     * ke server cadangan lewat jaringan ("jaringan").
     */
    public function jalankanBackup(string $tujuan = 'lokal'): void
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $namaFile = 'backups/backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $pathLengkap = storage_path('app/' . $namaFile);

        $perintah = ['mysqldump', '-h', $host, '-u', $username];
        if (! empty($password)) {
            $perintah[] = '-p' . $password;
        }
        $perintah[] = $database;

        $process = new Process($perintah);
        $process->setTimeout(120);

        try {
            $process->mustRun(function ($type, $buffer) use ($pathLengkap) {
                file_put_contents($pathLengkap, $buffer, FILE_APPEND);
            });

            AuditLog::catat('created', 'Backup', null, 'Backup database dijalankan: ' . basename($namaFile));

            if ($tujuan === 'jaringan') {
                $this->salinKeServerCadangan();
            } else {
                Notification::make()
                    ->title('Backup ke Server Lokal berhasil')
                    ->body('File ' . basename($namaFile) . ' berhasil dibuat di server ini (192.168.1.9).')
                    ->icon('heroicon-o-circle-stack')
                    ->success()
                    ->send()
                    ->sendToDatabase(auth()->user());
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Backup gagal')
                ->body('Pastikan "mysqldump" tersedia di PATH sistem (biasanya ada di folder bin MySQL Laragon/XAMPP). Detail: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        $this->muatDaftarBackup();
    }

    /**
     * Mirror seluruh folder storage/ (dump SQL, dokumen, avatar) ke server
     * cadangan lewat jaringan, memakai ROBOCOPY — persis mekanisme yang
     * sudah terbukti berhasil dijalankan manual lewat Command Prompt.
     *
     * CATATAN PENTING: robocopy di sini dijalankan oleh proses PHP/Apache,
     * BUKAN oleh user Windows yang login secara interaktif. Kalau akun
     * service Apache belum punya akses ke folder share tersebut (net use /
     * kredensial tersimpan), proses ini akan gagal dengan error akses
     * ditolak — itu bukan bug kode, tapi perlu pengaturan izin jaringan
     * tambahan di Windows Server.
     */
    protected function salinKeServerCadangan(): void
    {
        $sumber = storage_path();
        $tujuan = self::pathServerCadangan() . '\\storage';

        // Flag robocopy dibuat AMAN untuk dijalankan otomatis dari web (tidak
        // seperti /R:1000000 di script .bat manual yang bisa menggantung
        // lama kalau network sedang bermasalah) — retry dibatasi 2x, jeda
        // singkat, supaya request web tidak menggantung terlalu lama.
        $process = new Process([
            'robocopy', $sumber, $tujuan, '/MIR', '/R:2', '/W:5',
        ]);
        $process->setTimeout(180);
        $process->run();

        // Robocopy punya kebiasaan unik: exit code 0-7 dianggap SUKSES
        // (bukan cuma 0), 8 ke atas baru benar-benar gagal.
        if ($process->getExitCode() !== null && $process->getExitCode() < 8) {
            AuditLog::catat('created', 'Backup', null, 'Backup di-mirror ke server cadangan (' . self::pathServerCadangan() . ')');

            Notification::make()
                ->title('Backup ke Server Cadangan berhasil')
                ->body('Seluruh data (database + dokumen + avatar) berhasil disalin ke ' . self::pathServerCadangan() . '.')
                ->icon('heroicon-o-server-stack')
                ->success()
                ->send()
                ->sendToDatabase(auth()->user());

            return;
        }

        Notification::make()
            ->title('Backup lokal berhasil, TAPI gagal disalin ke Server Cadangan')
            ->body(
                'File backup tetap aman di server ini. Kemungkinan penyebab: folder share "' . self::pathServerCadangan() . '" '
                . 'tidak bisa diakses oleh akun service Apache (beda dengan akun Windows Anda saat login). '
                . 'Cek koneksi jaringan & izin akses folder share di server 192.168.1.10. '
                . 'Kode keluar robocopy: ' . ($process->getExitCode() ?? 'tidak diketahui')
            )
            ->warning()
            ->persistent()
            ->send();
    }

    /**
     * Jalankan backup FILE DOKUMEN saja (bukan database) — mem-backup seluruh
     * folder "documents" (dokumen yang diupload staff) menjadi satu file .zip.
     *
     * Hanya bisa dijalankan oleh Super Admin (Admin IT), sama seperti seluruh
     * halaman ini (lihat canAccess()) — ditambah abort_unless di sini sebagai
     * lapisan keamanan tambahan.
     *
     * $tujuan menentukan apakah hasil .zip cuma disimpan lokal ("lokal"),
     * atau folder dokumen aslinya juga di-mirror ke server cadangan lewat
     * jaringan ("jaringan").
     */
    public function jalankanBackupFile(string $tujuan = 'lokal'): void
    {
        abort_unless(auth()->user()?->isAdminIT(), 403);

        Storage::disk('local')->makeDirectory('backups_file');

        $folderDokumen = Storage::disk('public')->path('documents');
        $namaZip = 'backup_file_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $pathZipLokal = storage_path('app/backups_file/' . $namaZip);

        try {
            if (! is_dir($folderDokumen)) {
                throw new \RuntimeException('Folder dokumen belum ada / masih kosong.');
            }

            $zip = new ZipArchive();
            if ($zip->open($pathZipLokal, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Gagal membuat file zip. Pastikan ekstensi PHP "zip" aktif.');
            }

            foreach (File::allFiles($folderDokumen) as $file) {
                $zip->addFile($file->getPathname(), 'documents/' . $file->getRelativePathname());
            }
            $zip->close();

            AuditLog::catat('created', 'Backup', null, 'Backup file dokumen dijalankan: ' . $namaZip);

            if ($tujuan === 'jaringan') {
                $this->salinFileKeServerCadangan();
            } else {
                Notification::make()
                    ->title('Backup File ke Server Lokal berhasil')
                    ->body('File ' . $namaZip . ' berhasil dibuat di server ini (192.168.1.9).')
                    ->icon('heroicon-o-folder-arrow-down')
                    ->success()
                    ->send()
                    ->sendToDatabase(auth()->user());
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Backup File gagal')
                ->body('Detail: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        $this->muatDaftarBackupFile();
    }

    /**
     * Mirror folder "documents" (file asli, bukan zip) ke server cadangan
     * lewat jaringan, memakai ROBOCOPY — mekanisme yang sama seperti backup
     * database ke server cadangan.
     */
    protected function salinFileKeServerCadangan(): void
    {
        $sumber = Storage::disk('public')->path('documents');
        $tujuan = self::pathServerCadangan() . '\\storage\\documents';

        $process = new Process([
            'robocopy', $sumber, $tujuan, '/MIR', '/R:2', '/W:5',
        ]);
        $process->setTimeout(180);
        $process->run();

        // Robocopy: exit code 0-7 dianggap SUKSES, 8 ke atas baru benar-benar gagal.
        if ($process->getExitCode() !== null && $process->getExitCode() < 8) {
            AuditLog::catat('created', 'Backup', null, 'Backup file dokumen di-mirror ke server cadangan (' . self::pathServerCadangan() . ')');

            Notification::make()
                ->title('Backup File ke Server Cadangan berhasil')
                ->body('Seluruh dokumen berhasil disalin ke ' . self::pathServerCadangan() . '.')
                ->icon('heroicon-o-server-stack')
                ->success()
                ->send()
                ->sendToDatabase(auth()->user());

            return;
        }

        Notification::make()
            ->title('Backup file lokal berhasil, TAPI gagal disalin ke Server Cadangan')
            ->body(
                'File zip tetap aman di server ini. Kemungkinan penyebab: folder share "' . self::pathServerCadangan() . '" '
                . 'tidak bisa diakses oleh akun service Apache (beda dengan akun Windows Anda saat login). '
                . 'Cek koneksi jaringan & izin akses folder share di server 192.168.1.10. '
                . 'Kode keluar robocopy: ' . ($process->getExitCode() ?? 'tidak diketahui')
            )
            ->warning()
            ->persistent()
            ->send();
    }

    public function unduh(string $path)
    {
        return Storage::disk('local')->download($path);
    }

    public function hapus(string $path): void
    {
        Storage::disk('local')->delete($path);
        $this->muatDaftarBackup();
        $this->muatDaftarBackupFile();

        Notification::make()
            ->title('Backup dihapus')
            ->success()
            ->send();
    }
}