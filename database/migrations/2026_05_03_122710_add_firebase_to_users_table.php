<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
    Schema::table('users', function (Blueprint $table) {
        $table->string('firebase_uid')->unique()->nullable();
        $table->string('full_name')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan ini agar saat rollback, kolomnya dihapus kembali
            $table->dropColumn(['firebase_uid', 'full_name']);
        });
    }
};
