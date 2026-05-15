<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =============================================
        // TABEL BERITA
        // =============================================
        Schema::create('berita', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dibuat_oleh');
            $table->string('judul');
            $table->string('slug')->nullable()->unique();
            $table->longText('konten');
            $table->text('ringkasan')->nullable();
            $table->string('foto_cover', 512)->nullable();
            $table->enum('kategori', ['umum', 'banjir', 'longsor', 'kebakaran', 'angin_kencang', 'gempa', 'cuaca'])->default('umum');
            $table->string('sumber')->nullable();
            $table->integer('views_count')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('dipublikasi_pada')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('cascade');
            $table->index('dibuat_oleh');
            $table->index(['kategori', 'status'], 'idx_berita_kategori');
            $table->index('dipublikasi_pada', 'idx_berita_pubdate');
            $table->index('views_count', 'idx_berita_views');
        });

        // =============================================
        // TABEL BERITA_TAGS
        // =============================================
        Schema::create('berita_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('berita_id');
            $table->string('tag', 50);

            $table->foreign('berita_id')->references('id')->on('berita')->onDelete('cascade');
            $table->unique(['berita_id', 'tag'], 'uniq_berita_tag');
        });

        // =============================================
        // TABEL FAQ
        // =============================================
        Schema::create('faq', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pertanyaan', 512);
            $table->text('jawaban');
            $table->string('kategori', 100)->default('umum');
            $table->smallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq');
        Schema::dropIfExists('berita_tags');
        Schema::dropIfExists('berita');
    }
};
