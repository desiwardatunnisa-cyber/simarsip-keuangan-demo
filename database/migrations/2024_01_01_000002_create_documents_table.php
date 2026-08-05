<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('judul_dokumen');
            $table->string('nomor_referensi')->nullable(); // no. kwitansi/faktur, opsional
            $table->string('nama_file_asli');
            $table->string('nama_file_sistem'); // nama unik saat disimpan
            $table->string('path_file');
            $table->string('tipe_file', 10); // pdf, jpg, jpeg, png
            $table->unsignedBigInteger('ukuran_file'); // dalam bytes
            $table->text('keterangan')->nullable();
            $table->date('tanggal_dokumen')->nullable(); // tanggal transaksi pada dokumen
            $table->timestamps();

            $table->index(['judul_dokumen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
