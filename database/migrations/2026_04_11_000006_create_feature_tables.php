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
        // TABEL KONTAK DARURAT KELUARGA (SOS)
        // Kontak keluarga terdaftar untuk SOS
        // =============================================
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('phone', 20);
            $table->string('relationship', 50)->nullable(); // Hubungan: ayah, ibu, suami, dll
            $table->boolean('is_primary')->default(false);    // Kontak utama
            $table->timestamps();

            $table->index('user_id');
        });

        // =============================================
        // TABEL SOS ALERT (Tombol Darurat)
        // Riwayat penggunaan tombol SOS darurat
        // =============================================
        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('address')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['active', 'responded', 'resolved', 'cancelled'])->default('active');

            // Siapa yang merespon
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('response_notes')->nullable();

            // Notifikasi terkirim
            $table->boolean('notified_bpbd')->default(false);
            $table->boolean('notified_family')->default(false);
            $table->boolean('called_center')->default(false);

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });

        // =============================================
        // TABEL LAPORAN RESMI (PDF ke Kapolres)
        // Generate laporan resmi BPBD
        // =============================================
        Schema::create('official_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();      // Nomor surat resmi
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->longText('content');
            $table->string('recipient');                      // Tujuan: Kapolres, dll
            $table->string('pdf_path')->nullable();           // Path file PDF
            $table->enum('status', ['draft', 'finalized', 'sent'])->default('draft');
            $table->timestamp('sent_at')->nullable();

            // Relasi ke laporan-laporan bencana yang terkait
            $table->json('related_report_ids')->nullable();   // Array ID disaster_reports

            $table->timestamps();

            $table->index('status');
        });

        // =============================================
        // TABEL CHATBOT CONVERSATIONS (Log Chatbot AI)
        // Riwayat percakapan chatbot
        // =============================================
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id')->index();
            $table->timestamps();
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant']);
            $table->text('message');
            $table->json('metadata')->nullable();    // Data tambahan (intent, confidence, dll)
            $table->timestamps();

            $table->index('conversation_id');
        });

        // =============================================
        // TABEL NOTIFICATIONS (Notifikasi Sistem)
        // =============================================
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type', 50);               // report_update, sos_alert, announcement, weather_warning
            $table->string('reference_type')->nullable(); // Model class
            $table->unsignedBigInteger('reference_id')->nullable(); // ID terkait
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index('type');
        });

        // =============================================
        // TABEL ACTIVITY LOG (Audit Trail)
        // Log aktivitas admin & petugas
        // =============================================
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');                // create, update, delete, verify, etc.
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('official_reports');
        Schema::dropIfExists('sos_alerts');
        Schema::dropIfExists('emergency_contacts');
    }
};
