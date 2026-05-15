<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // AMBIL DATA REFERENSI DARI DB
        // ============================================================
        $laporanIds    = DB::table('laporan_bencana')->pluck('id')->toArray();
        $userIds       = DB::table('users')->pluck('id')->toArray();
        $kecamatanRows = DB::table('kecamatan')->select('id', 'nama')->get();
        $adminId       = DB::table('users')->where('role', 'super_admin')->value('id')
                         ?? DB::table('users')->where('role', 'admin_bpbd')->value('id')
                         ?? $userIds[0] ?? null;

        if (empty($laporanIds)) {
            $this->command->warn('⚠ Tidak ada data di laporan_bencana. Seeder dilewati.');
            return;
        }
        if (empty($userIds)) {
            $this->command->warn('⚠ Tidak ada data di users. Seeder dilewati.');
            return;
        }
        if ($kecamatanRows->isEmpty()) {
            $this->command->warn('⚠ Tidak ada data kecamatan. Harap jalankan DatabaseSeeder dulu.');
            return;
        }

        $kecamatanList = $kecamatanRows->keyBy('nama');
        $kecId         = fn(string $nama) => optional($kecamatanList->get($nama))->id
                                             ?? $kecamatanRows->first()->id;
        $now           = now();

        // ============================================================
        // 1. LAPORAN MEDIA
        // ============================================================
        $this->command->info('🖼️  Menyemai laporan_media...');

        $mediaData  = [];
        $fotoContoh = [
            'https://images.unsplash.com/photo-1547683905-f686c993aae5?w=800', // banjir
            'https://images.unsplash.com/photo-1504608524841-42584120d693?w=800', // tanah longsor
            'https://images.unsplash.com/photo-1527482797697-8795b05a13fe?w=800', // kebakaran
            'https://images.unsplash.com/photo-1603484477859-abe6a73f9366?w=800', // angin
            'https://images.unsplash.com/photo-1600880292089-90a7e086ee0c?w=800', // gempa
        ];

        foreach ($laporanIds as $laporanId) {
            $jumlahFoto = rand(1, 3);
            for ($i = 0; $i < $jumlahFoto; $i++) {
                $mediaData[] = [
                    'id'          => Str::uuid()->toString(),
                    'laporan_id'  => $laporanId,
                    'url'         => $fotoContoh[array_rand($fotoContoh)],
                    'tipe'        => 'foto',
                    'urutan'      => $i + 1,
                    'uploaded_at' => $now->copy()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ];
            }
            // 50% kemungkinan ada video
            if (rand(0, 1)) {
                $mediaData[] = [
                    'id'          => Str::uuid()->toString(),
                    'laporan_id'  => $laporanId,
                    'url'         => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'tipe'        => 'video',
                    'urutan'      => $jumlahFoto + 1,
                    'uploaded_at' => $now->copy()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ];
            }
        }

        DB::table('laporan_media')->insert($mediaData);
        $this->command->info('✅ Berhasil insert ' . count($mediaData) . ' data laporan_media.');

        // ============================================================
        // 2. LAPORAN KOMENTAR
        // ============================================================
        $this->command->info('💬 Menyemai laporan_komentar...');

        $komentarData   = [];
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
            $jumlahKomentar = rand(2, 5);
            for ($i = 0; $i < $jumlahKomentar; $i++) {
                $komentarData[] = [
                    'id'          => Str::uuid()->toString(),
                    'laporan_id'  => $laporanId,
                    'user_id'     => $userIds[array_rand($userIds)],
                    'isi'         => $komentarContoh[array_rand($komentarContoh)],
                    'dibuat_pada' => $now->copy()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                ];
            }
        }

        DB::table('laporan_komentar')->insert($komentarData);
        $this->command->info('✅ Berhasil insert ' . count($komentarData) . ' data laporan_komentar.');

        // ============================================================
        // 3. POS PENGUNGSIAN
        // ============================================================
        $this->command->info('🏕️  Menyemai pos_pengungsian...');

        $posPengungsian = [
            [
                'nama' => 'Gedung Olahraga Kaliwates', 'kecamatan' => 'Kaliwates',
                'alamat' => 'Jl. Nusantara No.1, Kaliwates, Jember',
                'latitude' => -8.1680, 'longitude' => 113.6920,
                'kapasitas' => 500, 'terisi' => 0, 'status' => 'standby',
                'fasilitas' => ['toilet', 'dapur umum', 'area bermain anak', 'poliklinik', 'listrik', 'air bersih'],
                'penanggung_jawab' => 'Camat Kaliwates', 'telepon' => '0331-487001',
            ],
            [
                'nama' => 'Balai Desa Rambipuji', 'kecamatan' => 'Rambipuji',
                'alamat' => 'Jl. Raya Rambipuji No.10, Rambipuji, Jember',
                'latitude' => -8.2510, 'longitude' => 113.6010,
                'kapasitas' => 200, 'terisi' => 0, 'status' => 'standby',
                'fasilitas' => ['toilet', 'dapur umum', 'matras', 'air bersih'],
                'penanggung_jawab' => 'Camat Rambipuji', 'telepon' => '0331-332210',
            ],
            [
                'nama' => 'Aula SMAN 1 Ambulu', 'kecamatan' => 'Ambulu',
                'alamat' => 'Jl. Raya Ambulu No.5, Ambulu, Jember',
                'latitude' => -8.3480, 'longitude' => 113.6030,
                'kapasitas' => 350, 'terisi' => 120, 'status' => 'aktif',
                'fasilitas' => ['toilet', 'dapur umum', 'listrik', 'air bersih', 'selimut'],
                'penanggung_jawab' => 'Kepala BPBD Jember', 'telepon' => '081235478440',
            ],
            [
                'nama' => 'Masjid Agung Baitul Amin', 'kecamatan' => 'Sumbersari',
                'alamat' => 'Jl. Slamet Riyadi, Sumbersari, Jember',
                'latitude' => -8.1640, 'longitude' => 113.7080,
                'kapasitas' => 800, 'terisi' => 0, 'status' => 'standby',
                'fasilitas' => ['toilet', 'air bersih', 'listrik', 'tempat wudhu'],
                'penanggung_jawab' => 'Takmir Masjid Agung', 'telepon' => '0331-487100',
            ],
            [
                'nama' => 'Gedung Serbaguna Kecamatan Wuluhan', 'kecamatan' => 'Wuluhan',
                'alamat' => 'Jl. Raya Wuluhan, Wuluhan, Jember',
                'latitude' => -8.3020, 'longitude' => 113.5990,
                'kapasitas' => 300, 'terisi' => 250, 'status' => 'aktif',
                'fasilitas' => ['toilet', 'dapur umum', 'matras', 'air bersih', 'poliklinik'],
                'penanggung_jawab' => 'Camat Wuluhan', 'telepon' => '0331-885001',
            ],
            [
                'nama' => 'SDN Puger 01 (Ruang Kelas Darurat)', 'kecamatan' => 'Puger',
                'alamat' => 'Jl. Merdeka No.3, Puger, Jember',
                'latitude' => -8.3820, 'longitude' => 113.4480,
                'kapasitas' => 150, 'terisi' => 150, 'status' => 'penuh',
                'fasilitas' => ['toilet', 'air bersih', 'listrik'],
                'penanggung_jawab' => 'Kepala Sekolah SDN Puger 01', 'telepon' => '081234567890',
            ],
            [
                'nama' => 'Balai Latihan Kerja (BLK) Patrang', 'kecamatan' => 'Patrang',
                'alamat' => 'Jl. Brawijaya No.123, Patrang, Jember',
                'latitude' => -8.1390, 'longitude' => 113.7020,
                'kapasitas' => 400, 'terisi' => 0, 'status' => 'standby',
                'fasilitas' => ['toilet', 'dapur umum', 'listrik', 'air bersih', 'wifi', 'selimut'],
                'penanggung_jawab' => 'Dinas Tenaga Kerja Jember', 'telepon' => '0331-485001',
            ],
            [
                'nama' => 'Balai Desa Tempurejo', 'kecamatan' => 'Tempurejo',
                'alamat' => 'Jl. Tempur No.1, Tempurejo, Jember',
                'latitude' => -8.3050, 'longitude' => 113.8020,
                'kapasitas' => 180, 'terisi' => 60, 'status' => 'aktif',
                'fasilitas' => ['toilet', 'dapur umum', 'matras', 'air bersih'],
                'penanggung_jawab' => 'Camat Tempurejo', 'telepon' => '081298765432',
            ],
            [
                'nama' => 'GOR Panti Jember', 'kecamatan' => 'Panti',
                'alamat' => 'Jl. Panti Raya No.7, Panti, Jember',
                'latitude' => -8.1230, 'longitude' => 113.6010,
                'kapasitas' => 250, 'terisi' => 0, 'status' => 'standby',
                'fasilitas' => ['toilet', 'dapur umum', 'air bersih', 'listrik'],
                'penanggung_jawab' => 'Camat Panti', 'telepon' => '082156789012',
            ],
            [
                'nama' => 'Gedung DPRD Kabupaten Jember (Cadangan)', 'kecamatan' => 'Kaliwates',
                'alamat' => 'Jl. Sumatra No.1, Kaliwates, Jember',
                'latitude' => -8.1700, 'longitude' => 113.6890,
                'kapasitas' => 1000, 'terisi' => 0, 'status' => 'standby',
                'fasilitas' => ['toilet', 'dapur umum', 'listrik', 'air bersih', 'ac', 'poliklinik', 'wifi'],
                'penanggung_jawab' => 'Sekretariat DPRD Jember', 'telepon' => '0331-483001',
            ],
        ];

        foreach ($posPengungsian as $pos) {
            $kecNama   = $pos['kecamatan'];
            $fasilitas = $pos['fasilitas'];
            unset($pos['kecamatan'], $pos['fasilitas']);
            DB::table('pos_pengungsian')->insert(array_merge(
                [
                    'id'           => Str::uuid()->toString(),
                    'kecamatan_id' => $kecId($kecNama),
                    'fasilitas'    => json_encode($fasilitas),
                    'is_active'    => true,
                    'updated_at'   => $now,
                ],
                $pos
            ));
        }
        $this->command->info('✅ Berhasil insert ' . count($posPengungsian) . ' data pos_pengungsian.');

        // ============================================================
        // 4. PERINGATAN DINI
        // ============================================================
        $this->command->info('⚠️  Menyemai peringatan_dini...');

        $peringatanList = [
            [
                'kecamatan' => 'Panti', 'tingkat_urgensi' => 'kritis',
                'is_active' => true,
                'deskripsi' => 'Intensitas hujan sangat tinggi diprediksi mengguyur Kecamatan Panti dan sekitarnya. Warga di sekitar lereng Gunung Argopuro diminta waspada terhadap potensi longsor dan banjir bandang. Hindari aktivitas di area sungai.',
            ],
            [
                'kecamatan' => 'Tempurejo', 'tingkat_urgensi' => 'tinggi',
                'is_active' => true,
                'deskripsi' => 'Debit Sungai Bedadung di wilayah Tempurejo terus meningkat. Tinggi muka air mencapai siaga 2. Warga di bantaran sungai diminta bersiap untuk evakuasi mandiri.',
            ],
            [
                'kecamatan' => 'Ambulu', 'tingkat_urgensi' => 'tinggi',
                'is_active' => true,
                'deskripsi' => 'Potensi banjir rob di kawasan pesisir Ambulu akibat gelombang tinggi dari Samudra Hindia. Nelayan dilarang melaut dan warga pesisir diminta menjauhi pantai.',
            ],
            [
                'kecamatan' => 'Wuluhan', 'tingkat_urgensi' => 'sedang',
                'is_active' => true,
                'deskripsi' => 'Curah hujan sedang terpantau di wilayah Wuluhan. Warga di area rawan banjir diminta memantau kondisi saluran air di sekitar rumah dan bersiap mengamankan barang berharga.',
            ],
            [
                'kecamatan' => 'Puger', 'tingkat_urgensi' => 'sedang',
                'is_active' => true,
                'deskripsi' => 'Gelombang laut di perairan Puger mencapai 2–3 meter. Aktivitas penangkapan ikan di laut lepas agar ditunda sementara hingga kondisi membaik.',
            ],
            [
                'kecamatan' => 'Kaliwates', 'tingkat_urgensi' => 'rendah',
                'is_active' => true,
                'deskripsi' => 'Peringatan potensi genangan air di beberapa titik Kecamatan Kaliwates akibat saluran drainase tersumbat. Mohon perhatian warga untuk tidak membuang sampah ke saluran air.',
            ],
            [
                'kecamatan' => 'Silo', 'tingkat_urgensi' => 'tinggi',
                'is_active' => true,
                'deskripsi' => 'Potensi tanah longsor di jalur perkebunan Kecamatan Silo. Pengguna jalan diminta berhati-hati dan menghindari parkir di tepi tebing saat hujan deras.',
            ],
            [
                'kecamatan' => 'Rambipuji', 'tingkat_urgensi' => 'sedang',
                'is_active' => true,
                'deskripsi' => 'Angin kencang diprediksi melanda Rambipuji pada sore dan malam hari. Waspadai pohon tumbang dan atap yang tidak terpasang kuat.',
            ],
        ];

        foreach ($peringatanList as $p) {
            $kecNama = $p['kecamatan'];
            unset($p['kecamatan']);
            DB::table('peringatan_dini')->insert(array_merge(
                [
                    'id'           => Str::uuid()->toString(),
                    'kecamatan_id' => $kecId($kecNama),
                    'dibuat_oleh'  => $adminId,
                    'created_at'   => $now->copy()->subHours(rand(1, 5)),
                ],
                $p
            ));
        }
        $this->command->info('✅ Berhasil insert ' . count($peringatanList) . ' data peringatan_dini.');

        // ============================================================
        // 5. BERITA + TAGS
        // ============================================================
        $this->command->info('📰 Menyemai berita...');

        $superAdminId = DB::table('users')->where('role', 'super_admin')->value('id')
                        ?? $adminId;

        $beritaList = [
            // ---- BANJIR ----
            [
                'judul'     => 'Banjir Luapi Pemukiman Warga Mumbulsari, Pemkab Jember Desak Pengerukan Sungai',
                'slug'      => 'banjir-luapi-pemukiman-warga-mumbulsari',
                'kategori'  => 'banjir',
                'sumber'    => 'https://radarjember.jawapos.com',
                'foto_cover'=> 'banjir-mumbulsari.jpg',
                'ringkasan' => 'Luapan sungai merendam rumah warga di Mumbulsari; Pemkab Jember minta normalisasi sungai.',
                'views_count'=> 1240,
                'tags'      => ['banjir', 'mumbulsari', 'normalisasi sungai'],
                'konten'    => 'JEMBER - Hujan deras yang mengguyur wilayah Kabupaten Jember sejak siang hingga sore hari menyebabkan debit air sungai di Kecamatan Mumbulsari meningkat drastis. Ketidakmampuan sungai menampung volume air mengakibatkan banjir luapan yang merendam puluhan rumah warga. Ketinggian genangan air bervariasi antara 30 hingga 60 sentimeter, melumpuhkan aktivitas ekonomi warga setempat selama beberapa jam. Tim reaksi cepat dari BPBD segera dikerahkan untuk membantu evakuasi warga kelompok rentan menuju titik pengungsian sementara di balai desa terdekat. Pemerintah Kabupaten Jember menilai kejadian ini bukan murni faktor alam, melainkan adanya masalah struktural pada infrastruktur sungai akibat tingginya sedimentasi dan tumpukan sampah. Pemkab Jember mengeluarkan desakan keras kepada Pemerintah Provinsi Jawa Timur untuk segera melakukan normalisasi dan pengerukan sungai di wilayah Mumbulsari.',
            ],
            [
                'judul'     => 'Banjir Bandang Terjang Desa Sumberlesung Ledokombo, 45 Keluarga Mengungsi',
                'slug'      => 'banjir-bandang-terjang-sumberlesung-ledokombo',
                'kategori'  => 'banjir',
                'sumber'    => 'https://times.id',
                'foto_cover'=> 'banjir-ledokombo.jpg',
                'ringkasan' => 'Banjir bandang menerjang Desa Sumberlesung Ledokombo, 45 keluarga terpaksa mengungsi.',
                'views_count'=> 876,
                'tags'      => ['banjir bandang', 'ledokombo', 'pengungsian'],
                'konten'    => 'JEMBER - Banjir bandang menerjang Desa Sumberlesung, Kecamatan Ledokombo pada Selasa malam setelah hujan deras berlangsung lebih dari empat jam. Luapan anak Sungai Bedadung yang membawa material lumpur dan kayu gelondongan merusak sejumlah areal persawahan dan merendam rumah warga di bantaran sungai. Sebanyak 45 keluarga atau sekitar 180 jiwa terpaksa meninggalkan rumah mereka dan mengungsi ke balai desa setempat. Tim BPBD Jember bersama Tagana dan PMI langsung bergerak mendistribusikan makanan siap saji, selimut, dan kebutuhan pokok lainnya untuk para pengungsi.',
            ],
            [
                'judul'     => 'Perbaikan Tanggul Sungai Bedadung Rampung, BPBD Jember Tingkatkan Kesiapsiagaan',
                'slug'      => 'perbaikan-tanggul-sungai-bedadung-rampung',
                'kategori'  => 'banjir',
                'sumber'    => 'https://jemberpost.com',
                'foto_cover'=> 'tanggul-bedadung.jpg',
                'ringkasan' => 'Perbaikan tanggul Sungai Bedadung rampung, BPBD tingkatkan kesiapsiagaan menjelang musim hujan.',
                'views_count'=> 543,
                'tags'      => ['tanggul', 'sungai bedadung', 'kesiapsiagaan'],
                'konten'    => 'JEMBER - Pekerjaan perbaikan dan pemerkuatan tanggul di sepanjang aliran Sungai Bedadung wilayah selatan Jember akhirnya dinyatakan rampung oleh Dinas PU Pengairan Kabupaten Jember. Perbaikan yang menelan anggaran lebih dari Rp2,4 miliar ini difokuskan pada titik-titik kritis yang mengalami keretakan dan erosi akibat derasnya aliran sungai di musim hujan lalu. BPBD Jember menyambut positif rampungnya perbaikan ini dan sekaligus mengumumkan peningkatan status kesiapsiagaan menghadapi musim hujan yang diprediksi datang lebih awal tahun ini.',
            ],
            // ---- LONGSOR ----
            [
                'judul'     => 'Waspada! Jalur Gumitir Jember-Banyuwangi Kembali Diterjang Longsor dan Pohon Tumbang',
                'slug'      => 'jalur-gumitir-jember-banyuwangi-longsor-pohon-tumbang',
                'kategori'  => 'longsor',
                'sumber'    => 'https://radarjember.jawapos.com',
                'foto_cover'=> 'longsor-gumitir.jpg',
                'ringkasan' => 'Longsor dan pohon tumbang kembali terjadi di jalur Gumitir kawasan Watu Gudang.',
                'views_count'=> 2105,
                'tags'      => ['longsor', 'jalur gumitir', 'pohon tumbang'],
                'konten'    => 'JEMBER - Konektivitas transportasi antara Kabupaten Jember dan Banyuwangi kembali terganggu setelah jalur lintas selatan di Gunung Gumitir diterjang longsor dan pohon tumbang. Material tanah bercampur bebatuan serta batang pohon yang melintang di jalan mengakibatkan kemacetan panjang dari kedua arah. Tim BPBD dan kepolisian segera turun ke lokasi mengatur lalu lintas dan membersihkan material secara manual. Sistem buka-tutup jalur diberlakukan untu mengurai kepadatan kendaraan, namun para pengguna jalan tetap diimbau waspada di titik-titik rawan longsor.',
            ],
            [
                'judul'     => 'Longsor di Lereng Argopuro Tutup Jalan Desa Harjomulyo Silo, 3 Rumah Rusak',
                'slug'      => 'longsor-lereng-argopuro-silo-3-rumah-rusak',
                'kategori'  => 'longsor',
                'sumber'    => 'https://detik.com',
                'foto_cover'=> 'longsor-silo.jpg',
                'ringkasan' => 'Longsor di lereng Argopuro menutup jalan desa dan merusak 3 rumah warga di Kecamatan Silo.',
                'views_count'=> 987,
                'tags'      => ['longsor', 'silo', 'argopuro'],
                'konten'    => 'JEMBER - Hujan deras yang berlangsung sejak dini hari memicu longsor di lereng Gunung Argopuro, tepatnya di kawasan Desa Harjomulyo, Kecamatan Silo. Material tanah seluas ratusan meter persegi meluncur dan menutup akses jalan desa sepanjang hampir 50 meter. Tiga unit rumah warga yang berada di tepi tebing mengalami kerusakan berat pada bagian tembok dan atap. Warga yang berdiam di sekitar titik longsor telah dievakuasi sementara ke rumah tetangga yang lebih aman oleh petugas desa dan relawan.',
            ],
            // ---- KEBAKARAN ----
            [
                'judul'     => 'Kebakaran Lahan Gambut Landa Kawasan Perkebunan Kencong, Asap Ganggu Permukiman',
                'slug'      => 'kebakaran-lahan-gambut-kencong',
                'kategori'  => 'kebakaran',
                'sumber'    => 'https://radarjember.jawapos.com',
                'foto_cover'=> 'kebakaran-kencong.jpg',
                'ringkasan' => 'Kebakaran lahan gambut di Kencong menyebabkan asap tebal yang mengganggu permukiman warga.',
                'views_count'=> 1430,
                'tags'      => ['kebakaran lahan', 'kencong', 'asap'],
                'konten'    => 'JEMBER - Lahan gambut seluas kurang lebih 12 hektare di kawasan perkebunan Kecamatan Kencong terbakar dan mengeluarkan asap tebal yang mengganggu permukiman warga sekitar. Menurut keterangan petugas Dinas Lingkungan Hidup, titik api pertama kali terdeteksi dari citra satelit pada siang hari sebelum akhirnya dilaporkan oleh warga setempat. Tim pemadam kebakaran dibantu anggota TNI dan Polri berjibaku memadamkan api menggunakan water tender dan membuat sekat bakar agar api tidak meluas ke area perumahan penduduk.',
            ],
            [
                'judul'     => 'Rumah Warga Kaliwates Ludes Terbakar, Diduga Akibat Korsleting Listrik',
                'slug'      => 'rumah-warga-kaliwates-terbakar-korsleting-listrik',
                'kategori'  => 'kebakaran',
                'sumber'    => 'https://jemberpost.com',
                'foto_cover'=> 'kebakaran-kaliwates.jpg',
                'ringkasan' => 'Satu unit rumah di Kaliwates ludes terbakar akibat korsleting listrik, kerugian capai Rp150 juta.',
                'views_count'=> 720,
                'tags'      => ['kebakaran', 'kaliwates', 'korsleting'],
                'konten'    => 'JEMBER - Satu unit rumah semi permanen di Jalan Nusantara, Kelurahan Mangli, Kecamatan Kaliwates ludes dilalap si jago merah pada Kamis dini hari. Api diduga berasal dari korsleting instalasi listrik di ruang tengah yang menjalar ke seluruh bagian rumah dalam waktu singkat. Pemilik rumah berhasil menyelamatkan diri meski hanya dengan pakaian yang melekat di badan. Tiga unit armada PMK dikerahkan dan berhasil memadamkan api dalam waktu sekitar 45 menit. Kerugian ditaksir mencapai Rp150 juta.',
            ],
            // ---- ANGIN KENCANG ----
            [
                'judul'     => 'Jember Dikepung Cuaca Ekstrem: Hujan Angin Hantam 4 Kecamatan, 13 Rumah Rusak',
                'slug'      => 'jember-cuaca-ekstrem-hujan-angin-4-kecamatan',
                'kategori'  => 'angin_kencang',
                'sumber'    => 'https://radarjember.jawapos.com',
                'foto_cover'=> 'cuaca-ekstrem-jember.jpg',
                'ringkasan' => 'Hujan angin di 4 kecamatan Jember mengakibatkan kerusakan pada 13 rumah warga.',
                'views_count'=> 3201,
                'tags'      => ['cuaca ekstrem', 'angin kencang', 'jember'],
                'konten'    => 'JEMBER - Kabupaten Jember kembali berduka akibat terjangan cuaca ekstrem yang melanda secara mendadak pada sore hari. Fenomena hujan deras disertai angin kencang dilaporkan menghantam setidaknya empat kecamatan, yakni Kaliwates, Sumbersari, Patrang, dan Ajung. Berdasarkan laporan kaji cepat BPBD, tercatat 13 unit rumah warga mengalami kerusakan ringan hingga sedang. Kerusakan paling dominan terjadi pada atap rumah dimana genteng dan asbes beterbangan terbawa angin. Beruntung tidak ada korban jiwa dalam kejadian ini.',
            ],
            [
                'judul'     => 'Puting Beliung Hantam Desa Tempurejo, Belasan Pohon Tumbang Tutup Jalan',
                'slug'      => 'puting-beliung-hantam-desa-tempurejo-pohon-tumbang',
                'kategori'  => 'angin_kencang',
                'sumber'    => 'https://times.id',
                'foto_cover'=> 'puting-beliung-tempurejo.jpg',
                'ringkasan' => 'Angin puting beliung melanda Desa Tempurejo, belasan pohon tumbang menutup jalan utama.',
                'views_count'=> 1654,
                'tags'      => ['puting beliung', 'tempurejo', 'pohon tumbang'],
                'konten'    => 'JEMBER - Angin puting beliung yang terjadi secara tiba-tiba di wilayah Desa Tempurejo, Kecamatan Tempurejo menumbangkan belasan pohon besar yang menutup ruas jalan utama penghubung kecamatan. Selain pohon tumbang, sejumlah atap rumah warga dan bangunan fasilitas umum juga mengalami kerusakan akibat tiupan angin yang berlangsung sekitar 15 menit namun dengan intensitas sangat kuat. Petugas BPBD dibantu Dinas PU dan masyarakat segera melakukan pembersihan agar arus lalu lintas dapat kembali normal.',
            ],
            // ---- GEMPA ----
            [
                'judul'     => 'Gempa M4.2 Guncang Jember, Warga Berhamburan Keluar Rumah',
                'slug'      => 'gempa-m42-guncang-jember-warga-berhamburan',
                'kategori'  => 'gempa',
                'sumber'    => 'https://bmkg.go.id',
                'foto_cover'=> 'gempa-jember.jpg',
                'ringkasan' => 'Gempa berkekuatan M4.2 mengguncang Jember, warga berhamburan panik keluar rumah.',
                'views_count'=> 4560,
                'tags'      => ['gempa', 'jember', 'BMKG'],
                'konten'    => 'JEMBER - Gempa bumi berkekuatan Magnitudo 4.2 mengguncang wilayah Kabupaten Jember dan sekitarnya pada pukul 09.45 WIB. Berdasarkan data BMKG, pusat gempa berada di 10 km barat daya Jember pada kedalaman 12 kilometer. Guncangan dirasakan cukup kuat oleh warga, terutama mereka yang berada di lantai atas gedung bertingkat. Warga berhamburan keluar rumah dan bangunan karena panik. Tidak ada laporan korban jiwa maupun kerusakan bangunan berarti dari kejadian ini, namun BPBD tetap melakukan pendataan di lapangan.',
            ],
            [
                'judul'     => 'BPBD Jember Gelar Simulasi Evakuasi Gempa dan Tsunami di Pesisir Selatan',
                'slug'      => 'bpbd-jember-simulasi-evakuasi-gempa-tsunami-pesisir',
                'kategori'  => 'gempa',
                'sumber'    => 'https://jemberpost.com',
                'foto_cover'=> 'simulasi-tsunami-jember.jpg',
                'ringkasan' => 'BPBD Jember gelar simulasi evakuasi gempa dan tsunami di pesisir selatan untuk tingkatkan kesiapsiagaan.',
                'views_count'=> 892,
                'tags'      => ['simulasi', 'tsunami', 'kesiapsiagaan'],
                'konten'    => 'JEMBER - Badan Penanggulangan Bencana Daerah (BPBD) Kabupaten Jember menggelar simulasi evakuasi gempa dan tsunami berskala besar di kawasan pesisir selatan, melibatkan ribuan warga dari tiga kecamatan pesisir yaitu Puger, Wuluhan, dan Ambulu. Simulasi ini bertujuan meningkatkan kesadaran dan kesiapsiagaan masyarakat terhadap ancaman gempa-tsunami yang diprediksi dapat terjadi akibat aktivitas zona subduksi Selatan Jawa. Peserta diajarkan mengenali tanda-tanda alam, jalur evakuasi yang benar, dan titik kumpul yang telah ditentukan.',
            ],
            // ---- CUACA ----
            [
                'judul'     => 'BMKG Keluarkan Peringatan Cuaca Ekstrem untuk Jember, Potensi Hujan Lebat Sepekan',
                'slug'      => 'bmkg-peringatan-cuaca-ekstrem-jember-hujan-lebat',
                'kategori'  => 'cuaca',
                'sumber'    => 'https://bmkg.go.id',
                'foto_cover'=> 'cuaca-ekstrem-bmkg.jpg',
                'ringkasan' => 'BMKG mengeluarkan peringatan cuaca ekstrem untuk Kabupaten Jember dengan potensi hujan lebat sepekan ke depan.',
                'views_count'=> 2874,
                'tags'      => ['cuaca ekstrem', 'BMKG', 'hujan lebat'],
                'konten'    => 'JEMBER - Badan Meteorologi, Klimatologi, dan Geofisika (BMKG) Stasiun Juanda secara resmi mengeluarkan peringatan dini cuaca ekstrem untuk wilayah Kabupaten Jember dan sekitarnya. Dalam rilisnya, BMKG memperingatkan adanya potensi hujan lebat disertai angin kencang dan petir yang diprediksi akan berlangsung selama tujuh hari ke depan akibat masuknya massa udara basah dari Samudra Hindia. Masyarakat diimbau untuk meningkatkan kewaspadaan, terutama warga yang tinggal di area rawan banjir, longsor, dan bantaran sungai.',
            ],
            [
                'judul'     => 'Kemarau Panjang Ancam Sumber Air Bersih di 7 Kecamatan Jember, Warga Mulai Kesulitan',
                'slug'      => 'kemarau-panjang-ancam-sumber-air-bersih-7-kecamatan-jember',
                'kategori'  => 'cuaca',
                'sumber'    => 'https://surabaya.kompas.com',
                'foto_cover'=> 'kemarau-jember.jpg',
                'ringkasan' => 'Kemarau panjang mengancam ketersediaan air bersih di 7 kecamatan Jember, warga mulai kesulitan.',
                'views_count'=> 1987,
                'tags'      => ['kemarau', 'air bersih', 'jember'],
                'konten'    => 'JEMBER - Musim kemarau panjang yang melanda Kabupaten Jember mulai berdampak serius terhadap ketersediaan sumber air bersih di tujuh kecamatan yang masuk kategori rawan kekeringan. Warga di Kecamatan Patrang, Arjasa, Jelbuk, Kalisat, Ledokombo, Sumberjambe, dan Silo mulai mengeluhkan surutnya debit sumur gali dan mata air yang biasa mereka andalkan. Pemerintah daerah telah mengerahkan armada tangki air dan berkoordinasi dengan Perumdam untuk distribusi air bersih kepada warga terdampak.',
            ],
            // ---- UMUM ----
            [
                'judul'     => 'Jember Siaga Darurat Kekeringan hingga Agustus 2026, Ada 7 Daerah Rawan',
                'slug'      => 'jember-siaga-darurat-kekeringan-agustus-2026',
                'kategori'  => 'umum',
                'sumber'    => 'https://surabaya.kompas.com',
                'foto_cover'=> 'kekeringan-jember.jpg',
                'ringkasan' => 'BPBD Kabupaten Jember menetapkan status siaga darurat bencana kekeringan hingga Agustus 2026.',
                'views_count'=> 5023,
                'tags'      => ['kekeringan', 'jember', 'siaga darurat'],
                'konten'    => 'JEMBER - Badan Penanggulangan Bencana Daerah (BPBD) Kabupaten Jember secara resmi menetapkan status siaga darurat bencana kekeringan yang diperkirakan berlangsung cukup lama. Penetapan status ini didasarkan pada rilis data meteorologi yang menunjukkan penurunan signifikan curah hujan di Jember, diprediksi mencapai puncaknya pada April hingga Agustus 2026. Terdapat tujuh kecamatan yang masuk daftar prioritas karena tingkat kerawanan krisis air bersih sangat tinggi. Saat ini pemerintah menyiagakan sekitar 10 unit armada tangki air bersih yang mendistribusikan ribuan liter air setiap harinya ke titik-titik terdampak.',
            ],
            [
                'judul'     => 'Waspada! Jember Masuk Zona Merah Kekeringan 2026, BPBD Petakan Antisipasi Megathrust',
                'slug'      => 'jember-zona-merah-kekeringan-2026-antisipasi-megathrust',
                'kategori'  => 'umum',
                'sumber'    => 'https://radarjember.jawapos.com',
                'foto_cover'=> 'zona-merah-jember.jpg',
                'ringkasan' => 'BPBD Jember memetakan zona merah kekeringan 2026 dan menyiapkan langkah antisipasi Megathrust.',
                'views_count'=> 3678,
                'tags'      => ['kekeringan', 'zona merah', 'megathrust'],
                'konten'    => 'JEMBER - Kondisi cuaca ekstrem memaksa otoritas terkait mengeluarkan peringatan dini yang lebih intens. Sebagian besar wilayah Kabupaten Jember telah masuk kategori Zona Merah kekeringan yang menandakan indeks ketersediaan air tanah telah mencapai level terendah dalam beberapa tahun terakhir. Selain kekeringan, pemerintah juga fokus pada mitigasi potensi gempa bumi besar atau Megathrust di zona subduksi selatan Jawa. Pemerintah daerah mulai memasang dan mengkalibrasi ulang perangkat Early Warning System (EWS) di sepanjang pesisir pantai Selatan Jember.',
            ],
        ];

        $jumlahBerita = 0;
        $jumlahTags   = 0;

        foreach ($beritaList as $data) {
            $beritaId = Str::uuid()->toString();
            $tags     = $data['tags'];
            $viewsCount = $data['views_count'];
            unset($data['tags'], $data['views_count']);

            DB::table('berita')->insert(array_merge(
                [
                    'id'               => $beritaId,
                    'dibuat_oleh'      => $superAdminId,
                    'views_count'      => $viewsCount,
                    'status'           => 'published',
                    'dibuat_pada'      => $now->copy()->subDays(rand(1, 60)),
                    'dipublikasi_pada' => $now->copy()->subDays(rand(1, 60)),
                    'updated_at'       => $now,
                ],
                $data
            ));

            foreach ($tags as $tag) {
                DB::table('berita_tags')->insert([
                    'id'        => Str::uuid()->toString(),
                    'berita_id' => $beritaId,
                    'tag'       => $tag,
                ]);
                $jumlahTags++;
            }
            $jumlahBerita++;
        }
        $this->command->info("✅ Berhasil insert {$jumlahBerita} data berita dan {$jumlahTags} tags.");

        // ============================================================
        $this->command->newLine();
        $this->command->info('🎉 SampleDataSeeder selesai!');
    }
}
