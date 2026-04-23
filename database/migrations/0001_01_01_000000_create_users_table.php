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
        // TABEL USERS
        // =============================================
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('role', ['masyarakat', 'admin_bmkg', 'super_admin'])->default('masyarakat');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        // =============================================
        // TABEL USER_AUTH (Login lokal & OAuth Google)
        // =============================================
        Schema::create('user_auth', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->enum('provider', ['local', 'google']);
            $table->string('provider_id')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['provider', 'provider_id'], 'uniq_provider');
            $table->index('user_id');
        });

        // =============================================
        // TABEL USER_LEVELS (Gamifikasi level pelapor)
        // =============================================
        Schema::create('user_levels', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->enum('level', ['pemula', 'kontributor', 'pelapor_aktif', 'relawan', 'pahlawan_siaga'])->default('pemula');
            $table->integer('laporan_terverifikasi')->default(0);
            $table->integer('badge_count')->default(0);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // =============================================
        // TABEL USER_POINTS
        // =============================================
        Schema::create('user_points', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->integer('total_points')->default(0);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // =============================================
        // TABEL USER_MEDICAL_INFO (Info medis darurat)
        // =============================================
        Schema::create('user_medical_info', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->text('alergi')->nullable();
            $table->text('kondisi_khusus')->nullable();
            $table->text('obat_rutin')->nullable();
            $table->string('kontak_darurat_nama')->nullable();
            $table->string('kontak_darurat_nomor', 30)->nullable();
            $table->string('kontak_darurat_relasi', 100)->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // =============================================
        // TABEL USER_DEVICE_TOKENS (Push notification)
        // =============================================
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('token', 512);
            $table->enum('platform', ['android', 'ios', 'web']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('last_used_at')->nullable()->useCurrentOnUpdate()->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('token', 'uniq_token');
            $table->index(['user_id', 'is_active'], 'idx_device_token_user');
        });

        // =============================================
        // Laravel system tables
        // =============================================
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_device_tokens');
        Schema::dropIfExists('user_medical_info');
        Schema::dropIfExists('user_points');
        Schema::dropIfExists('user_levels');
        Schema::dropIfExists('user_auth');
        Schema::dropIfExists('users');
    }
};
