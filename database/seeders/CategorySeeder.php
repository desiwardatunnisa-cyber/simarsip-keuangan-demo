<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            'Bukti Kas Masuk',
            'Bukti Kas Keluar',
            'Faktur',
            'Kwitansi',
            'Nota',
            'Slip Gaji',
            'Lainnya',
        ];

        foreach ($kategori as $nama) {
            Category::firstOrCreate(['nama_kategori' => $nama]);
        }
    }
}
