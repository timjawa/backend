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
        if (! Schema::hasTable('ringkasan_historical_cuaca')) {
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

                $table->foreign('kecamatan_id')
                    ->references('id')
                    ->on('kecamatan')
                    ->onDelete('cascade');
                $table->unique(['kecamatan_id', 'tanggal'], 'uniq_ringkasan_kecamatan_tanggal');
                $table->index('tanggal', 'idx_ringkasan_tanggal');
            });
        }

        if (Schema::hasTable('cuaca_realtime')) {
            DB::statement('ALTER TABLE cuaca_realtime MODIFY kecepatan_angin DECIMAL(7,2) NULL');
        }

        if (Schema::hasTable('perkiraan_cuaca')) {
            DB::statement('ALTER TABLE perkiraan_cuaca MODIFY kecepatan_angin DECIMAL(7,2) NULL');
            DB::statement('ALTER TABLE perkiraan_cuaca MODIFY visibilitas INT NULL');
        }

        DB::unprepared('DROP EVENT IF EXISTS event_ringkas_historical_cuaca');
        DB::unprepared('DROP PROCEDURE IF EXISTS ringkas_historical_cuaca_lama');

        DB::unprepared('
            CREATE PROCEDURE ringkas_historical_cuaca_lama()
            BEGIN
              INSERT INTO ringkasan_historical_cuaca (
                id,
                kecamatan_id,
                tanggal,
                suhu_rata,
                kelembapan_rata,
                curah_hujan_rata,
                cloud_cover_rata,
                jumlah_data,
                created_at
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
        Schema::dropIfExists('ringkasan_historical_cuaca');
    }
};
