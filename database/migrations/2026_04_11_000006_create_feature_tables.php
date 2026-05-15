<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TABEL PERINGATAN_DINI
        Schema::create('peringatan_dini', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kecamatan_id');
            $table->uuid('dibuat_oleh');
            $table->text('deskripsi')->nullable();
            $table->enum('tingkat_urgensi', ['rendah', 'sedang', 'tinggi', 'kritis'])->default('rendah');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('cascade');
            $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('cascade');
            $table->index('kecamatan_id');
            $table->index('dibuat_oleh');
        });

        // TABEL KONTAK_DARURAT
        Schema::create('kontak_darurat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('nomor', 30);
            $table->enum('kategori', ['polisi', 'pemadam', 'ambulans', 'bpbd', 'sar', 'pln', 'lainnya'])->default('lainnya');
            $table->string('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // TABEL POINT_TRANSACTIONS
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->integer('points');
            $table->enum('type', ['laporan', 'validasi', 'bonus', 'penalti']);
            $table->uuid('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });

        // Trigger: auto-update user_points saat insert point_transactions
        DB::unprepared('
            CREATE TRIGGER after_insert_point AFTER INSERT ON point_transactions FOR EACH ROW
            BEGIN
              INSERT INTO user_points (user_id, total_points)
              VALUES (NEW.user_id, NEW.points)
              ON DUPLICATE KEY UPDATE
                total_points = total_points + NEW.points;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_insert_point');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('kontak_darurat');
        Schema::dropIfExists('peringatan_dini');
    }
};
