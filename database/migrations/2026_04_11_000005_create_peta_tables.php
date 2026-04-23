<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peta_bencana_layer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_layer', 100);
            $table->enum('tipe', ['titik_banjir', 'jalur_evakuasi', 'pos_pengungsian', 'pos_pemantauan', 'zona_rawan']);
            $table->json('config')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->smallInteger('urutan')->default(0);
        });

        Schema::create('pos_pengungsian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->uuid('kecamatan_id')->nullable();
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('kapasitas')->default(0);
            $table->integer('terisi')->default(0);
            $table->json('fasilitas')->nullable();
            $table->enum('status', ['standby', 'aktif', 'penuh', 'tutup'])->default('standby');
            $table->string('penanggung_jawab')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('set null');
            $table->index('kecamatan_id');
            $table->index(['status', 'is_active'], 'idx_pengungsian_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_pengungsian');
        Schema::dropIfExists('peta_bencana_layer');
    }
};
