<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // =============================================
        // SEED ROLES
        // =============================================
        $roles = [
            ['id' => 1, 'name' => 'admin', 'display_name' => 'Admin Utama', 'description' => 'Pengelola utama sistem dengan akses penuh', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'petugas_bpbd', 'display_name' => 'Petugas BPBD', 'description' => 'Petugas lapangan yang memverifikasi dan menindaklanjuti laporan', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'operator', 'display_name' => 'Operator', 'description' => 'Pengelola konten dan data laporan', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'viewer', 'display_name' => 'Viewer', 'description' => 'Hanya dapat melihat data tanpa hak kelola', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'masyarakat', 'display_name' => 'Masyarakat', 'description' => 'Pengguna umum masyarakat Jember', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('roles')->insert($roles);

        // =============================================
        // SEED DEFAULT ADMIN
        // =============================================
        DB::table('users')->insert([
            'name' => 'Admin Jember Siaga',
            'email' => 'admin@jembersiaga.go.id',
            'phone' => '08123456789',
            'role_id' => 1,
            'status' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // =============================================
        // SEED KATEGORI BENCANA
        // =============================================
        $categories = [
            ['name' => 'Banjir', 'slug' => 'banjir', 'icon' => '🌊', 'color' => '#3B82F6', 'description' => 'Bencana banjir akibat luapan air', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tanah Longsor', 'slug' => 'longsor', 'icon' => '⛰️', 'color' => '#8B5CF6', 'description' => 'Bencana tanah longsor', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pohon Tumbang', 'slug' => 'pohon-tumbang', 'icon' => '🌳', 'color' => '#10B981', 'description' => 'Kejadian pohon tumbang', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kebakaran', 'slug' => 'kebakaran', 'icon' => '🔥', 'color' => '#EF4444', 'description' => 'Bencana kebakaran', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Angin Puting Beliung', 'slug' => 'puting-beliung', 'icon' => '🌪️', 'color' => '#6366F1', 'description' => 'Bencana angin puting beliung', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gempa Bumi', 'slug' => 'gempa', 'icon' => '🌍', 'color' => '#F59E0B', 'description' => 'Bencana gempa bumi', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kekeringan', 'slug' => 'kekeringan', 'icon' => '☀️', 'color' => '#F97316', 'description' => 'Bencana kekeringan', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lainnya', 'slug' => 'lainnya', 'icon' => '⚠️', 'color' => '#64748B', 'description' => 'Bencana atau kejadian lainnya', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('disaster_categories')->insert($categories);

        // =============================================
        // SEED 31 KECAMATAN JEMBER
        // =============================================
        $subdistricts = [
            ['name' => 'Ajung', 'code' => 'AJG', 'latitude' => -8.2000, 'longitude' => 113.7200, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ambulu', 'code' => 'ABL', 'latitude' => -8.3500, 'longitude' => 113.6000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Arjasa', 'code' => 'ARS', 'latitude' => -8.1200, 'longitude' => 113.8200, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Balung', 'code' => 'BLG', 'latitude' => -8.3100, 'longitude' => 113.5600, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bangsalsari', 'code' => 'BGS', 'latitude' => -8.2400, 'longitude' => 113.5300, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gumukmas', 'code' => 'GMK', 'latitude' => -8.3700, 'longitude' => 113.4500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jelbuk', 'code' => 'JLB', 'latitude' => -8.0800, 'longitude' => 113.7500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jenggawah', 'code' => 'JGW', 'latitude' => -8.2800, 'longitude' => 113.6500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jombang', 'code' => 'JBG', 'latitude' => -8.1600, 'longitude' => 113.7000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kalisat', 'code' => 'KLS', 'latitude' => -8.1300, 'longitude' => 113.7800, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kaliwates', 'code' => 'KLW', 'latitude' => -8.1700, 'longitude' => 113.7100, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kencong', 'code' => 'KCG', 'latitude' => -8.3500, 'longitude' => 113.3700, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ledokombo', 'code' => 'LDK', 'latitude' => -8.0900, 'longitude' => 113.8500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mayang', 'code' => 'MYG', 'latitude' => -8.1100, 'longitude' => 113.6500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mumbulsari', 'code' => 'MBS', 'latitude' => -8.2600, 'longitude' => 113.7500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pakusari', 'code' => 'PKS', 'latitude' => -8.1400, 'longitude' => 113.7600, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Panti', 'code' => 'PNT', 'latitude' => -8.1000, 'longitude' => 113.6200, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Patrang', 'code' => 'PTR', 'latitude' => -8.1500, 'longitude' => 113.7000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Puger', 'code' => 'PGR', 'latitude' => -8.3800, 'longitude' => 113.4800, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rambipuji', 'code' => 'RBP', 'latitude' => -8.2200, 'longitude' => 113.6200, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Semboro', 'code' => 'SMB', 'latitude' => -8.2000, 'longitude' => 113.5000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Silo', 'code' => 'SLO', 'latitude' => -8.2400, 'longitude' => 113.8800, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sukorambi', 'code' => 'SKR', 'latitude' => -8.1300, 'longitude' => 113.6700, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sukowono', 'code' => 'SKW', 'latitude' => -8.1200, 'longitude' => 113.8000, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sumberbaru', 'code' => 'SBR', 'latitude' => -8.2800, 'longitude' => 113.4200, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sumberjambe', 'code' => 'SBJ', 'latitude' => -8.0700, 'longitude' => 113.8800, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sumbersari', 'code' => 'SBS', 'latitude' => -8.1800, 'longitude' => 113.7300, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tanggul', 'code' => 'TGL', 'latitude' => -8.1800, 'longitude' => 113.5300, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tempurejo', 'code' => 'TPR', 'latitude' => -8.3200, 'longitude' => 113.7500, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Umbulsari', 'code' => 'UBS', 'latitude' => -8.3200, 'longitude' => 113.4700, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Wuluhan', 'code' => 'WLH', 'latitude' => -8.3300, 'longitude' => 113.5900, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('subdistricts')->insert($subdistricts);
    }
}
