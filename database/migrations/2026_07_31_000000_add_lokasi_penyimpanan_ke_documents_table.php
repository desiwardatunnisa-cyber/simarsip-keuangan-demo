<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menandai fisik file dokumen ini tersimpan di server mana: 'lokal' (192.168.1.9,
     * disk "public") atau 'cadangan' (192.168.1.10, dipakai otomatis kalau storage
     * lokal bermasalah saat upload). Dipakai DocumentDownloadController & FileServeController
     * untuk tahu disk mana yang harus dibaca saat melayani lihat/download.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('lokasi_penyimpanan')->default('lokal')->after('path_file');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('lokasi_penyimpanan');
        });
    }
};
