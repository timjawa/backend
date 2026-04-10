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
        // TABEL ROLES (Hak Akses Pengguna)
        // Admin Utama, Petugas BPBD, Operator, Viewer, Masyarakat
        // =============================================
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. admin, petugas_bpbd, operator, viewer, masyarakat
            $table->string('display_name');   // e.g. Admin Utama, Petugas BPBD
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // =============================================
        // TABEL USERS (Pengguna Sistem)
        // Masyarakat + Admin + Petugas
        // =============================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->index('role_id');
            $table->index('status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
