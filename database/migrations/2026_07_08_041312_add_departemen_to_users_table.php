<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('departemen')->nullable()->after('bagian');
        });

        // Migrasi data lama: pisahkan bagian (jabatan) dari departemen
        DB::table('users')->where('bagian', 'staff_akuntan')->update([
            'bagian' => 'staff',
            'departemen' => 'akuntan',
        ]);

        DB::table('users')->where('bagian', 'staff_keuangan')->update([
            'bagian' => 'staff',
            'departemen' => 'keuangan',
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('bagian', 'staff')->where('departemen', 'akuntan')->update([
            'bagian' => 'staff_akuntan',
        ]);

        DB::table('users')->where('bagian', 'staff')->where('departemen', 'keuangan')->update([
            'bagian' => 'staff_keuangan',
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('departemen');
        });
    }
};