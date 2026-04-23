<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // =============================================
        // SEED DEFAULT USERS
        // =============================================
        $superAdminId = Str::uuid()->toString();
        $adminBmkgId = Str::uuid()->toString();

        DB::table('users')->insert([
            [
                'id' => $superAdminId,
                'name' => 'Super Admin Jember Siaga',
                'email' => 'admin@jembersiaga.go.id',
                'role' => 'super_admin',
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'id' => $adminBmkgId,
                'name' => 'Admin BMKG Jember',
                'email' => 'bmkg@jembersiaga.go.id',
                'role' => 'admin_bmkg',
                'is_active' => true,
                'created_at' => now(),
            ],
        ]);

        // Auth lokal untuk admin
        DB::table('user_auth')->insert([
            [
                'id' => Str::uuid()->toString(),
                'user_id' => $superAdminId,
                'provider' => 'local',
                'provider_id' => null,
                'password' => Hash::make('password'),
                'created_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'user_id' => $adminBmkgId,
                'provider' => 'local',
                'provider_id' => null,
                'password' => Hash::make('password'),
                'created_at' => now(),
            ],
        ]);

        // =============================================
        // SEED 31 KECAMATAN JEMBER
        // =============================================
        $kecamatan = [
            ['nama' => 'Ajung',        'latitude' => -8.2000000, 'longitude' => 113.7200000, 'kode_wilayah' => '35.09.01', 'level_rawan' => 'rendah'],
            ['nama' => 'Ambulu',       'latitude' => -8.3500000, 'longitude' => 113.6000000, 'kode_wilayah' => '35.09.02', 'level_rawan' => 'sedang'],
            ['nama' => 'Arjasa',       'latitude' => -8.1200000, 'longitude' => 113.8200000, 'kode_wilayah' => '35.09.03', 'level_rawan' => 'rendah'],
            ['nama' => 'Balung',       'latitude' => -8.3100000, 'longitude' => 113.5600000, 'kode_wilayah' => '35.09.04', 'level_rawan' => 'sedang'],
            ['nama' => 'Bangsalsari',  'latitude' => -8.2400000, 'longitude' => 113.5300000, 'kode_wilayah' => '35.09.05', 'level_rawan' => 'sedang'],
            ['nama' => 'Gumukmas',     'latitude' => -8.3700000, 'longitude' => 113.4500000, 'kode_wilayah' => '35.09.06', 'level_rawan' => 'tinggi'],
            ['nama' => 'Jelbuk',       'latitude' => -8.0800000, 'longitude' => 113.7500000, 'kode_wilayah' => '35.09.07', 'level_rawan' => 'rendah'],
            ['nama' => 'Jenggawah',    'latitude' => -8.2800000, 'longitude' => 113.6500000, 'kode_wilayah' => '35.09.08', 'level_rawan' => 'sedang'],
            ['nama' => 'Jombang',      'latitude' => -8.1600000, 'longitude' => 113.7000000, 'kode_wilayah' => '35.09.09', 'level_rawan' => 'rendah'],
            ['nama' => 'Kalisat',      'latitude' => -8.1300000, 'longitude' => 113.7800000, 'kode_wilayah' => '35.09.10', 'level_rawan' => 'rendah'],
            ['nama' => 'Kaliwates',    'latitude' => -8.1700000, 'longitude' => 113.7100000, 'kode_wilayah' => '35.09.11', 'level_rawan' => 'sedang'],
            ['nama' => 'Kencong',      'latitude' => -8.3500000, 'longitude' => 113.3700000, 'kode_wilayah' => '35.09.12', 'level_rawan' => 'tinggi'],
            ['nama' => 'Ledokombo',    'latitude' => -8.0900000, 'longitude' => 113.8500000, 'kode_wilayah' => '35.09.13', 'level_rawan' => 'rendah'],
            ['nama' => 'Mayang',       'latitude' => -8.1100000, 'longitude' => 113.6500000, 'kode_wilayah' => '35.09.14', 'level_rawan' => 'rendah'],
            ['nama' => 'Mumbulsari',   'latitude' => -8.2600000, 'longitude' => 113.7500000, 'kode_wilayah' => '35.09.15', 'level_rawan' => 'sedang'],
            ['nama' => 'Pakusari',     'latitude' => -8.1400000, 'longitude' => 113.7600000, 'kode_wilayah' => '35.09.16', 'level_rawan' => 'rendah'],
            ['nama' => 'Panti',        'latitude' => -8.1000000, 'longitude' => 113.6200000, 'kode_wilayah' => '35.09.17', 'level_rawan' => 'sedang'],
            ['nama' => 'Patrang',      'latitude' => -8.1500000, 'longitude' => 113.7000000, 'kode_wilayah' => '35.09.18', 'level_rawan' => 'rendah'],
            ['nama' => 'Puger',        'latitude' => -8.3800000, 'longitude' => 113.4800000, 'kode_wilayah' => '35.09.19', 'level_rawan' => 'tinggi'],
            ['nama' => 'Rambipuji',    'latitude' => -8.2200000, 'longitude' => 113.6200000, 'kode_wilayah' => '35.09.20', 'level_rawan' => 'sedang'],
            ['nama' => 'Semboro',      'latitude' => -8.2000000, 'longitude' => 113.5000000, 'kode_wilayah' => '35.09.21', 'level_rawan' => 'rendah'],
            ['nama' => 'Silo',         'latitude' => -8.2400000, 'longitude' => 113.8800000, 'kode_wilayah' => '35.09.22', 'level_rawan' => 'sedang'],
            ['nama' => 'Sukorambi',    'latitude' => -8.1300000, 'longitude' => 113.6700000, 'kode_wilayah' => '35.09.23', 'level_rawan' => 'rendah'],
            ['nama' => 'Sukowono',     'latitude' => -8.1200000, 'longitude' => 113.8000000, 'kode_wilayah' => '35.09.24', 'level_rawan' => 'rendah'],
            ['nama' => 'Sumberbaru',   'latitude' => -8.2800000, 'longitude' => 113.4200000, 'kode_wilayah' => '35.09.25', 'level_rawan' => 'sedang'],
            ['nama' => 'Sumberjambe',  'latitude' => -8.0700000, 'longitude' => 113.8800000, 'kode_wilayah' => '35.09.26', 'level_rawan' => 'rendah'],
            ['nama' => 'Sumbersari',   'latitude' => -8.1800000, 'longitude' => 113.7300000, 'kode_wilayah' => '35.09.27', 'level_rawan' => 'sedang'],
            ['nama' => 'Tanggul',      'latitude' => -8.1800000, 'longitude' => 113.5300000, 'kode_wilayah' => '35.09.28', 'level_rawan' => 'sedang'],
            ['nama' => 'Tempurejo',    'latitude' => -8.3200000, 'longitude' => 113.7500000, 'kode_wilayah' => '35.09.29', 'level_rawan' => 'tinggi'],
            ['nama' => 'Umbulsari',    'latitude' => -8.3200000, 'longitude' => 113.4700000, 'kode_wilayah' => '35.09.30', 'level_rawan' => 'sedang'],
            ['nama' => 'Wuluhan',      'latitude' => -8.3300000, 'longitude' => 113.5900000, 'kode_wilayah' => '35.09.31', 'level_rawan' => 'sedang'],
        ];

        foreach ($kecamatan as $kec) {
            DB::table('kecamatan')->insert(array_merge(
                ['id' => Str::uuid()->toString()],
                $kec
            ));
        }

        // =============================================
        // SEED KONTAK DARURAT
        // =============================================
        $kontakDarurat = [
            ['nama' => 'BPBD Kabupaten Jember',         'nomor' => '0331-487500', 'kategori' => 'bpbd',     'keterangan' => 'Badan Penanggulangan Bencana Daerah'],
            ['nama' => 'Polres Jember',                  'nomor' => '0331-486110', 'kategori' => 'polisi',   'keterangan' => 'Kepolisian Resort Jember'],
            ['nama' => 'Damkar Jember',                  'nomor' => '0331-421113', 'kategori' => 'pemadam',  'keterangan' => 'Pemadam Kebakaran Kab. Jember'],
            ['nama' => 'RSUD dr. Soebandi',              'nomor' => '0331-487441', 'kategori' => 'ambulans', 'keterangan' => 'IGD Rumah Sakit Umum Daerah Jember'],
            ['nama' => 'SAR Jember',                     'nomor' => '0331-335577', 'kategori' => 'sar',      'keterangan' => 'Search and Rescue Jember'],
            ['nama' => 'PLN Jember',                     'nomor' => '123',         'kategori' => 'pln',      'keterangan' => 'Gangguan Listrik'],
            ['nama' => 'Call Center Bencana Nasional',   'nomor' => '117',         'kategori' => 'lainnya',  'keterangan' => 'Hotline Bencana Nasional BNPB'],
        ];

        foreach ($kontakDarurat as $kontak) {
            DB::table('kontak_darurat')->insert(array_merge(
                ['id' => Str::uuid()->toString(), 'is_active' => true],
                $kontak
            ));
        }

        // =============================================
        // SEED FAQ
        // =============================================
        $faqs = [
            ['pertanyaan' => 'Apa itu Jember Siaga?', 'jawaban' => 'Jember Siaga adalah platform pusat informasi dan koordinasi penanggulangan bencana Kabupaten Jember yang menyediakan data cuaca, peringatan dini, serta fitur pelaporan bencana oleh masyarakat.', 'kategori' => 'umum', 'urutan' => 1],
            ['pertanyaan' => 'Bagaimana cara melaporkan bencana?', 'jawaban' => 'Anda dapat melaporkan bencana melalui menu "Lapor Bencana" di aplikasi. Isi formulir dengan lokasi, jenis bencana, deskripsi, dan lampirkan foto/video sebagai bukti. Laporan akan diverifikasi oleh petugas BPBD.', 'kategori' => 'laporan', 'urutan' => 2],
            ['pertanyaan' => 'Dari mana sumber data cuaca?', 'jawaban' => 'Data cuaca bersumber dari BMKG (Badan Meteorologi, Klimatologi, dan Geofisika) dan diperbarui secara berkala untuk memberikan informasi prakiraan cuaca yang akurat.', 'kategori' => 'cuaca', 'urutan' => 3],
            ['pertanyaan' => 'Apa arti level peringatan dini?', 'jawaban' => 'Level peringatan terdiri dari: Rendah (kondisi normal), Sedang (waspada), Tinggi (siaga - potensi bencana), dan Kritis (bahaya - bencana sedang/akan terjadi). Ikuti arahan BPBD sesuai level peringatan.', 'kategori' => 'peringatan', 'urutan' => 4],
            ['pertanyaan' => 'Bagaimana sistem poin dan level bekerja?', 'jawaban' => 'Setiap laporan yang diverifikasi memberikan poin. Level naik otomatis: Pemula (0-2), Kontributor (3-9), Pelapor Aktif (10-19), Relawan (20-49), Pahlawan Siaga (50+). Level lebih tinggi mendapat badge khusus.', 'kategori' => 'gamifikasi', 'urutan' => 5],
        ];

        foreach ($faqs as $faq) {
            DB::table('faq')->insert(array_merge(
                ['id' => Str::uuid()->toString(), 'is_active' => true, 'dibuat_pada' => now(), 'updated_at' => now()],
                $faq
            ));
        }

        // =============================================
        // SEED PANDUAN BENCANA (contoh banjir)
        // =============================================
        $panduanBanjir = [
            ['judul' => 'Persiapan Sebelum Banjir', 'fase' => 'sebelum', 'urutan' => 1, 'konten' => 'Siapkan tas darurat berisi dokumen penting, obat-obatan, pakaian ganti, makanan kering, senter, dan power bank. Kenali rute evakuasi terdekat dan lokasi posko pengungsian di kecamatan Anda.'],
            ['judul' => 'Saat Terjadi Banjir',      'fase' => 'saat',     'urutan' => 2, 'konten' => 'Segera menuju tempat yang lebih tinggi. Jangan berjalan atau berkendara melewati genangan air deras. Matikan aliran listrik jika air mulai masuk rumah. Hubungi nomor darurat BPBD Jember.'],
            ['judul' => 'Setelah Banjir Surut',     'fase' => 'setelah',  'urutan' => 3, 'konten' => 'Periksa kondisi rumah sebelum kembali. Bersihkan lumpur dan sampah. Waspada terhadap hewan berbahaya. Periksa instalasi listrik sebelum menyalakan kembali. Laporkan kerusakan ke BPBD.'],
            ['judul' => 'Cara Melapor Bencana',     'fase' => 'cara_lapor', 'urutan' => 4, 'konten' => 'Buka aplikasi Jember Siaga → Pilih menu Lapor Bencana → Pilih jenis bencana → Isi lokasi dan deskripsi → Lampirkan foto/video → Kirim laporan. Tim BPBD akan memverifikasi dan menindaklanjuti.'],
        ];

        foreach ($panduanBanjir as $panduan) {
            DB::table('panduan_bencana')->insert(array_merge(
                ['id' => Str::uuid()->toString(), 'jenis_bencana' => 'banjir', 'is_active' => true, 'dibuat_pada' => now(), 'updated_at' => now()],
                $panduan
            ));
        }
    }
}
