<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =============================================
        // TABEL LAPORAN_BENCANA
        // =============================================
        Schema::create('laporan_bencana', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('kecamatan_id')->nullable();
            $table->string('jenis_bencana', 100);
            $table->text('deskripsi')->nullable();
            $table->string('alamat_lengkap')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('status', ['baru', 'diverifikasi', 'ditolak', 'selesai'])->default('baru');
            $table->boolean('is_draft')->default(false);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('set null');
            $table->index('user_id', 'idx_laporan_user');
            $table->index('status', 'idx_laporan_status');
            $table->index('kecamatan_id', 'idx_laporan_kecamatan');
            $table->index('is_draft', 'idx_laporan_draft');
        });

        // Trigger: update user_levels saat laporan diverifikasi
        DB::unprepared("
            CREATE TRIGGER after_update_laporan_verified AFTER UPDATE ON laporan_bencana FOR EACH ROW
            BEGIN
              IF NEW.status = 'diverifikasi' AND OLD.status != 'diverifikasi' THEN
                INSERT INTO user_levels (user_id, laporan_terverifikasi)
                VALUES (NEW.user_id, 1)
                ON DUPLICATE KEY UPDATE
                  laporan_terverifikasi = laporan_terverifikasi + 1,
                  level = CASE
                    WHEN laporan_terverifikasi + 1 >= 50 THEN 'pahlawan_siaga'
                    WHEN laporan_terverifikasi + 1 >= 20 THEN 'relawan'
                    WHEN laporan_terverifikasi + 1 >= 10 THEN 'pelapor_aktif'
                    WHEN laporan_terverifikasi + 1 >= 3  THEN 'kontributor'
                    ELSE 'pemula'
                  END;
              END IF;
            END
        ");

        // =============================================
        // TABEL LAPORAN_MEDIA (Foto/Video bukti)
        // =============================================
        Schema::create('laporan_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('laporan_id');
            $table->string('url', 512);
            $table->enum('tipe', ['foto', 'video'])->default('foto');
            $table->tinyInteger('urutan')->default(0);
            $table->timestamp('uploaded_at')->nullable()->useCurrent();

            $table->foreign('laporan_id')->references('id')->on('laporan_bencana')->onDelete('cascade');
            $table->index('laporan_id');
        });

        // =============================================
        // TABEL LAPORAN_KOMENTAR
        // =============================================
        Schema::create('laporan_komentar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('laporan_id');
            $table->uuid('user_id');
            $table->text('isi');
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->foreign('laporan_id')->references('id')->on('laporan_bencana')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('laporan_id', 'idx_komentar_laporan');
        });

        // =============================================
        // TABEL LAPORAN_SEARCH_LOG
        // =============================================
        Schema::create('laporan_search_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('keyword');
            $table->integer('result_count')->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('user_id');
            $table->index('keyword', 'idx_search_log_keyword');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_update_laporan_verified');
        Schema::dropIfExists('laporan_search_log');
        Schema::dropIfExists('laporan_komentar');
        Schema::dropIfExists('laporan_media');
        Schema::dropIfExists('laporan_bencana');
    }
};
