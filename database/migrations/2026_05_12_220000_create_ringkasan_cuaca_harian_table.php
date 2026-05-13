<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ringkasan_cuaca_harian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kecamatan_id');
            $table->date('tanggal');
            $table->decimal('suhu_rata', 4, 1)->nullable();
            $table->decimal('curah_hujan', 6, 2)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->onDelete('cascade');
            $table->unique(['kecamatan_id', 'tanggal'], 'uniq_ringkasan_harian');
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ringkasan_cuaca_harian');
    }
};
