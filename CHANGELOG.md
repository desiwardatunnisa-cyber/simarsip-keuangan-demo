# CHANGELOG — Patch UI/UX SIMARSIP Keuangan (Kumulatif)

Patch ini berisi **8 file yang berubah**, dan sudah mengakumulasi seluruh
revisi. Cukup timpa file-file ini sekali — tidak perlu menerapkan patch
bertahap dari ZIP-ZIP sebelumnya. Tidak ada file routes, config, provider,
model, migration, seeder, atau logika bisnis yang disentuh.

## Cara pakai
Salin (overwrite) file-file di bawah ke lokasi yang sama persis di project
Anda, lalu jalankan `php artisan view:clear` (opsional). Tidak perlu
`npm run build`.

## Daftar file yang diubah

| # | File | Isi Perubahan |
|---|------|-------------------|
| 1 | `resources/views/vendor/filament-panels/components/sidebar/index.blade.php` | Sidebar **Dark Steel**; mini by default → **auto-expand saat hover**, collapse saat cursor keluar; tanpa hamburger di desktop. Warna: BG `#243447`, Hover item `#30475E`, Active menu `#2563EB`, Icon `#B7C5D6`, teks nav `#E2E8F0`. Label disembunyikan otomatis saat mini (hanya ikon + tooltip). |
| 2 | `resources/views/filament/brand-logo.blade.php` | Logo mini memakai **gambar `logo-rajawali.png` asli** (diperkecil ke 40px, tanpa teks SIMARSIP), tampil saat collapsed. **Catatan penting:** lihat bagian "Perlu keputusan Anda" di bawah — logo asli berbentuk emblem abstrak (bukan huruf), jadi tidak ada huruf "R" yang bisa di-crop dari gambar tersebut. |
| 3 | `resources/views/vendor/filament-panels/components/layout/index.blade.php` | Margin konten utama mengikuti lebar sidebar mini (80px) di desktop, karena sidebar bersifat overlay saat expand. |
| 4 | `resources/views/vendor/filament-panels/components/topbar/index.blade.php` | Header: Judul Halaman + ikon notifikasi (lonceng + badge) + Avatar + tombol **Logout** yang langsung terlihat (tidak perlu buka dropdown). |
| 5 | `resources/views/filament/custom-styles.blade.php` | Token warna (`:root`) diselaraskan dengan palet mockup (Border, Text, Success, Warning, Danger) + 4 variabel warna sidebar baru. |
| 6 | `app/Filament/Pages/Dashboard.php` | **(baru di revisi ini)** Urutan widget diubah: Donut Chart & Bar Chart sekarang **langsung di bawah card statistik** (sebelumnya ada "Riwayat Dokumen" menyela di antaranya). "Aktivitas Login Saya" dipindah jadi baris tersendiri di bawah, tidak lagi digabung satu baris dengan chart. Ini hanya perubahan **urutan array widget** pada Filament Page, bukan logic/data. |
| 7 | `app/Filament/Widgets/DashboardActivityChartSection.php` | **(baru di revisi ini)** Hanya perubahan komentar dokumentasi (docblock) untuk mencerminkan layout baru — tidak ada perubahan logic. |
| 8 | `resources/views/filament/widgets/dashboard-activity-chart-section.blade.php` | **(baru di revisi ini)** Layout diubah dari "kolom kanan 30% berisi Donut+Bar bertumpuk, di samping Aktivitas Login 70%" menjadi **Donut Chart & Bar Chart berdampingan 50/50 dalam satu baris penuh**, sesuai mockup. Aktivitas Login Saya dikeluarkan dari section ini (lihat poin 6). |

## Konfirmasi: "Aktivitas Login Saya" sudah real-time & sudah mencatat akses
Setelah saya cek `app/Filament/Widgets/MyLoginActivityWidget.php` (**tidak
saya ubah**, karena sudah benar): widget ini **sudah** menampilkan data
asli dari database:
- Jam **login** & **logout** asli per sesi, dari tabel `LoginSession`
  (`login_at`, `logout_at`), diformat `d M Y · H:i`.
- **Aktivitas/akses** yang dilakukan user (upload dokumen, edit, verifikasi,
  revisi, hapus, kelola user/kategori, backup database), dari tabel
  `AuditLog`, digabung dalam satu linimasa terurut waktu terbaru.

Jadi bagian ini **sudah sesuai permintaan Anda tanpa perlu diubah** — data
yang tampil bukan dummy/placeholder, tapi sungguhan dari database.

## ⚠️ Perlu keputusan Anda: logo mini "R"
Saya cek ulang file `public/images/logo-rajawali.png` yang ada di project —
logo ini berbentuk **emblem abstrak berwarna** (bukan tulisan/huruf), jadi
**tidak ada huruf "R" yang bisa dipotong (crop) dari gambar tersebut**.
Saat ini logo mini memakai **gambar utuh yang sama, diperkecil**. Silakan
pilih salah satu:
1. **Tetap pakai gambar utuh diperkecil** (kondisi saat ini) — tidak perlu
   tindakan apa pun.
2. **Buat lencana teks "R"** bergaya (font tebal, warna brand) sebagai
   pengganti — bukan dari gambar asli, murni elemen teks baru.
3. Anda kirim **file logo terpisah** yang khusus berisi huruf "R" saja
   (mis. monogram resmi perusahaan, kalau ada), saya pasang itu sebagai
   logo mini.

## Yang TIDAK diubah (dikonfirmasi)
- Tidak ada perubahan pada `routes/`, `config/`, `app/Providers/`,
  `app/Models/`, `app/Http/Middleware/`, `bootstrap/`, `composer.json/lock`,
  `package.json`, `vite.config.*`, `database/`, policy, listener, event,
  auth/login/logout logic, role/permission, workflow approval, atau
  Filament Panel Provider.
- `MyLoginActivityWidget.php` tidak diubah — datanya sudah benar (lihat di atas).
- File gambar `logo-rajawali.png` tidak diubah/di-crop.
- Tombol Upload/Upload Folder/Import Excel — sudah di Arsip Dokumen, tidak diubah.
- Submenu dengan chevron ("Pengajuan", dll.) — **tidak dikerjakan**, tidak
  ada di permintaan Anda (hanya tampak di gambar mockup referensi).
