<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampanye_donasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('jenis_bencana', 100);
            $table->uuid('kecamatan_id')->nullable();
            $table->uuid('laporan_bencana_id')->nullable();
            $table->decimal('target_donasi', 15, 2)->nullable();
            $table->decimal('total_terkumpul', 15, 2)->default(0);
            $table->decimal('total_disalurkan', 15, 2)->default(0);
            $table->string('gambar')->nullable();
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai')->nullable();
            $table->enum('status', ['draft', 'aktif', 'ditutup'])->default('draft');
            $table->uuid('dibuat_oleh');
            $table->timestamps();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->nullOnDelete();
            $table->foreign('laporan_bencana_id')->references('id')->on('laporan_bencana')->nullOnDelete();
            $table->foreign('dibuat_oleh')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['status', 'tanggal_mulai']);
            $table->index('jenis_bencana');
        });

        Schema::create('donasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kampanye_id');
            $table->uuid('user_id')->nullable();
            $table->string('nama_donatur')->nullable();
            $table->string('email_donatur')->nullable();
            $table->string('telepon_donatur', 30)->nullable();
            $table->decimal('nominal', 15, 2);
            $table->text('pesan')->nullable();
            $table->boolean('anonim')->default(false);
            $table->enum('status', ['menunggu', 'berhasil', 'gagal', 'kedaluwarsa'])->default('menunggu');
            $table->dateTime('tanggal_bayar')->nullable();
            $table->timestamps();

            $table->foreign('kampanye_id')->references('id')->on('kampanye_donasi')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index(['kampanye_id', 'status']);
        });

        Schema::create('pembayaran_donasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('donasi_id');
            $table->string('order_id', 100)->unique();
            $table->string('snap_token')->nullable();
            $table->text('redirect_url')->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('metode_bayar', 50)->nullable();
            $table->string('status_transaksi', 50)->default('pending');
            $table->string('fraud_status', 50)->nullable();
            $table->decimal('gross_amount', 15, 2);
            $table->dateTime('waktu_settlement')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->foreign('donasi_id')->references('id')->on('donasi')->cascadeOnDelete();
            $table->index(['status_transaksi', 'created_at']);
        });

        Schema::create('notifikasi_midtrans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_id', 100);
            $table->string('transaction_id', 100)->nullable();
            $table->string('status_transaksi', 50);
            $table->string('metode_bayar', 50)->nullable();
            $table->json('payload');
            $table->dateTime('diterima_pada');
            $table->dateTime('diproses_pada')->nullable();
            $table->enum('status_proses', ['diterima', 'diproses', 'gagal'])->default('diterima');
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
            $table->index('status_proses');
        });

        Schema::create('penyaluran_donasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kampanye_id');
            $table->string('judul');
            $table->text('deskripsi');
            $table->decimal('nominal', 15, 2);
            $table->string('penerima');
            $table->dateTime('tanggal_penyaluran');
            $table->string('bukti')->nullable();
            $table->enum('status', ['draft', 'publish'])->default('draft');
            $table->uuid('dibuat_oleh');
            $table->timestamps();

            $table->foreign('kampanye_id')->references('id')->on('kampanye_donasi')->cascadeOnDelete();
            $table->foreign('dibuat_oleh')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['kampanye_id', 'status']);
            $table->index('tanggal_penyaluran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyaluran_donasi');
        Schema::dropIfExists('notifikasi_midtrans');
        Schema::dropIfExists('pembayaran_donasi');
        Schema::dropIfExists('donasi');
        Schema::dropIfExists('kampanye_donasi');
    }
};
