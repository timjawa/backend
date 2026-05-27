<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peta_marker', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('tipe_marker', 20)->default('titik');
            $table->json('path_data')->nullable();
            $table->string('label')->nullable();
            $table->string('kategori', 100);
            $table->enum('tingkat_bahaya', ['rendah', 'sedang', 'tinggi', 'kritis'])->default('sedang');
            $table->integer('radius')->nullable();
            $table->uuid('dibuat_oleh')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('set null');
            $table->index('kategori');
            $table->index('tingkat_bahaya');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peta_marker');
    }
};
