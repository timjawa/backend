<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaporanKomentarMediaSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua ID laporan bencana yang sudah ada
        $laporanIds = DB::table('laporan_bencana')->pluck('id')->toArray();

        // Ambil semua ID user yang sudah ada
        $userIds = DB::table('users')->pluck('id')->toArray();

        if (empty($laporanIds)) {
            $this->command->warn('Tidak ada data di laporan_bencana. Seeder dilewati.');
            return;
        }

        if (empty($userIds)) {
            $this->command->warn('Tidak ada data di users. Seeder dilewati.');
            return;
        }

        // =============================================
        // DATA LAPORAN MEDIA
        // =============================================
        $mediaData = [];

        $fotoContoh = [
            'https://images.unsplash.com/photo-1547683905-f686c993aae5?w=800', // banjir
            'https://images.unsplash.com/photo-1504608524841-42584120d693?w=800', // tanah longsor
            'https://images.unsplash.com/photo-1527482797697-8795b05a13fe?w=800', // kebakaran
            'https://images.unsplash.com/photo-1603484477859-abe6a73f9366?w=800', // angin
            'https://images.unsplash.com/photo-1600880292089-90a7e086ee0c?w=800', // gempa
        ];

        foreach ($laporanIds as $laporanId) {
            // Setiap laporan dapat 1–3 foto
            $jumlahFoto = rand(1, 3);
            for ($i = 0; $i < $jumlahFoto; $i++) {
                $mediaData[] = [
                    'id'          => Str::uuid()->toString(),
                    'laporan_id'  => $laporanId,
                    'url'         => $fotoContoh[array_rand($fotoContoh)],
                    'tipe'        => 'foto',
                    'urutan'      => $i + 1,
                    'uploaded_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ];
            }

            // Kadang ada video juga (50% kemungkinan)
            if (rand(0, 1)) {
                $mediaData[] = [
                    'id'          => Str::uuid()->toString(),
                    'laporan_id'  => $laporanId,
                    'url'         => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // placeholder video
                    'tipe'        => 'video',
                    'urutan'      => $jumlahFoto + 1,
                    'uploaded_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ];
            }
        }

        DB::table('laporan_media')->insert($mediaData);
        $this->command->info('✅ Berhasil insert ' . count($mediaData) . ' data laporan_media.');

        // =============================================
        // DATA LAPORAN KOMENTAR
        // =============================================
        $komentarData = [];

        $komentarContoh = [
            'Semoga korban bencana segera mendapatkan bantuan yang diperlukan.',
            'Sudah dilaporkan ke RT setempat, semoga cepat ditangani.',
            'Saya juga menyaksikan kejadian ini, kondisinya sangat memprihatinkan.',
            'Tim BPBD sudah bergerak ke lokasi pukul 14.00 tadi.',
            'Warga sekitar sudah dievakuasi ke balai desa.',
            'Mohon bantuan logistik segera dikirimkan ke lokasi.',
            'Terima kasih sudah melaporkan, sangat membantu masyarakat.',
            'Kondisi jalan menuju lokasi cukup sulit, butuh alat khusus.',
            'Sudah ada posko darurat di masjid dekat lokasi.',
            'Semoga tidak ada korban jiwa, doa terbaik untuk warga terdampak.',
            'Saya warga sekitar, perlu bantuan selimut dan makanan.',
            'Kejadian ini terjadi sejak subuh, sudah banyak rumah terendam.',
            'Pipa air bersih juga terdampak, mohon segera diperbaiki.',
            'Anak-anak dan lansia sudah aman di tempat pengungsian.',
            'Tim medis dari Puskesmas sudah standby di lokasi.',
        ];

        foreach ($laporanIds as $laporanId) {
            // Setiap laporan dapat 2–5 komentar
            $jumlahKomentar = rand(2, 5);
            for ($i = 0; $i < $jumlahKomentar; $i++) {
                $komentarData[] = [
                    'id'         => Str::uuid()->toString(),
                    'laporan_id' => $laporanId,
                    'user_id'    => $userIds[array_rand($userIds)],
                    'isi'        => $komentarContoh[array_rand($komentarContoh)],
                    'dibuat_pada'=> now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                ];
            }
        }

        DB::table('laporan_komentar')->insert($komentarData);
        $this->command->info('✅ Berhasil insert ' . count($komentarData) . ' data laporan_komentar.');
    }
}
