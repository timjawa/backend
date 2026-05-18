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
        // TABEL CUACA_REALTIME
        // =============================================
        Schema::create('cuaca_realtime', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kecamatan_id');
            $table->decimal('suhu', 4, 1)->nullable();
            $table->decimal('feels_like', 4, 1)->nullable();
            $table->tinyInteger('kelembapan')->nullable();
            $table->decimal('curah_hujan', 5, 2)->nullable();
            $table->tinyInteger('cloud_cover')->nullable();
            $table->decimal('kecepatan_angin', 7, 2)->nullable();
            $table->smallInteger('arah_angin')->nullable();
            $table->integer('weather_code')->nullable();
            $table->string('deskripsi', 100)->nullable();
            $table->decimal('uv_index', 4, 2)->nullable();
            $table->integer('visibilitas')->nullable();
            $table->smallInteger('tekanan_udara')->nullable();
            $table->timestamp('fetched_at')->nullable()->useCurrent();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('cascade');
            $table->index('kecamatan_id');
        });

        // =============================================
        // TABEL HISTORICAL_CUACA (arsip data cuaca)
        // Harus dibuat sebelum perkiraan_cuaca karena trigger
        // =============================================
        Schema::create('historical_cuaca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kecamatan_id');
            $table->dateTime('waktu');
            $table->tinyInteger('suhu')->nullable();
            $table->tinyInteger('kelembapan')->nullable();
            $table->decimal('curah_hujan', 5, 2)->nullable();
            $table->tinyInteger('cloud_cover')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('cascade');
            $table->index('kecamatan_id');
        });

        // =============================================
        // TABEL RINGKASAN_HISTORICAL_CUACA
        // Rata-rata harian data historical yang sudah lewat
        // =============================================
        Schema::create('ringkasan_historical_cuaca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kecamatan_id');
            $table->date('tanggal');
            $table->decimal('suhu_rata', 4, 1)->nullable();
            $table->decimal('kelembapan_rata', 5, 2)->nullable();
            $table->decimal('curah_hujan_rata', 5, 2)->nullable();
            $table->decimal('cloud_cover_rata', 5, 2)->nullable();
            $table->integer('jumlah_data')->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('cascade');
            $table->unique(['kecamatan_id', 'tanggal'], 'uniq_ringkasan_kecamatan_tanggal');
            $table->index('tanggal', 'idx_ringkasan_tanggal');
        });

        // =============================================
        // TABEL PERKIRAAN_CUACA (prakiraan per jam)
        // =============================================
        Schema::create('perkiraan_cuaca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kecamatan_id');
            $table->dateTime('waktu_lokal');
            $table->tinyInteger('suhu')->nullable();
            $table->tinyInteger('kelembapan')->nullable();
            $table->decimal('curah_hujan', 5, 2)->nullable();
            $table->tinyInteger('cloud_cover')->nullable();
            $table->integer('weather_code')->nullable();
            $table->string('deskripsi_cuaca', 100)->nullable();
            $table->decimal('kecepatan_angin', 7, 2)->nullable();
            $table->string('arah_angin', 5)->nullable();
            $table->tinyInteger('uv_index')->nullable();
            $table->integer('visibilitas')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('cascade');
            $table->unique(['kecamatan_id', 'waktu_lokal'], 'uniq_cuaca');
            $table->index(['kecamatan_id', 'waktu_lokal'], 'idx_perkiraan_waktu');
        });

        // Trigger: auto-archive ke historical_cuaca saat insert perkiraan
        DB::unprepared('
            CREATE TRIGGER after_insert_cuaca AFTER INSERT ON perkiraan_cuaca FOR EACH ROW
            BEGIN
              INSERT INTO historical_cuaca (
                id, kecamatan_id, waktu,
                suhu, kelembapan, curah_hujan, cloud_cover
              ) VALUES (
                UUID(),
                NEW.kecamatan_id,
                NEW.waktu_lokal,
                NEW.suhu,
                NEW.kelembapan,
                NEW.curah_hujan,
                NEW.cloud_cover
              );
            END
        ');

        DB::unprepared('
            CREATE PROCEDURE ringkas_historical_cuaca_lama()
            BEGIN
              INSERT INTO ringkasan_historical_cuaca (
                id, kecamatan_id, tanggal, suhu_rata, kelembapan_rata,
                curah_hujan_rata, cloud_cover_rata, jumlah_data, created_at
              )
              SELECT
                UUID(),
                kecamatan_id,
                DATE(waktu) AS tanggal,
                ROUND(AVG(suhu), 1),
                ROUND(AVG(kelembapan), 2),
                ROUND(AVG(curah_hujan), 2),
                ROUND(AVG(cloud_cover), 2),
                COUNT(*),
                NOW()
              FROM historical_cuaca
              WHERE DATE(waktu) < CURDATE()
              GROUP BY kecamatan_id, DATE(waktu)
              HAVING COUNT(*) > 1
              ON DUPLICATE KEY UPDATE
                suhu_rata = VALUES(suhu_rata),
                kelembapan_rata = VALUES(kelembapan_rata),
                curah_hujan_rata = VALUES(curah_hujan_rata),
                cloud_cover_rata = VALUES(cloud_cover_rata),
                jumlah_data = VALUES(jumlah_data),
                created_at = NOW();

              DELETE h
              FROM historical_cuaca h
              JOIN ringkasan_historical_cuaca r
                ON r.kecamatan_id = h.kecamatan_id
               AND r.tanggal = DATE(h.waktu)
              WHERE DATE(h.waktu) < CURDATE();
            END
        ');

        DB::unprepared("
            CREATE EVENT event_ringkas_historical_cuaca
            ON SCHEDULE EVERY 1 DAY
            STARTS TIMESTAMP(CURRENT_DATE + INTERVAL 1 DAY, '00:10:00')
            DO CALL ringkas_historical_cuaca_lama()
        ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP EVENT IF EXISTS event_ringkas_historical_cuaca');
        DB::unprepared('DROP PROCEDURE IF EXISTS ringkas_historical_cuaca_lama');
        DB::unprepared('DROP TRIGGER IF EXISTS after_insert_cuaca');
        Schema::dropIfExists('perkiraan_cuaca');
        Schema::dropIfExists('ringkasan_historical_cuaca');
        Schema::dropIfExists('historical_cuaca');
        Schema::dropIfExists('cuaca_realtime');
    }
};
