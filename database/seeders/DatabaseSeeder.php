<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\UserAuth;

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
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@jembersiaga.go.id'],
            [
                'name'      => 'Super Admin',
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        UserAuth::firstOrCreate(
            ['user_id' => $superAdmin->id, 'provider' => 'local'],
            ['password' => 'password'] // 'hashed' cast di model yang akan hash otomatis
        );

        $adminBmkg = User::firstOrCreate(
            ['email' => 'admin@jembersiaga.go.id'],
            [
                'name'      => 'Admin BPBD',
                'role'      => 'admin_bpbd',
                'is_active' => true,
            ]
        );

        UserAuth::firstOrCreate(
            ['user_id' => $adminBmkg->id, 'provider' => 'local'],
            ['password' => 'password'] // 'hashed' cast di model yang akan hash otomatis
        );

        $masyarakat = User::firstOrCreate(
            ['email' => 'user@jembersiaga.go.id'],
            [
                'name'      => 'Warga Jember',
                'role'      => 'masyarakat',
                'is_active' => true,
            ]
        );

        UserAuth::firstOrCreate(
            ['user_id' => $masyarakat->id, 'provider' => 'local'],
            ['password' => 'password'] // 'hashed' cast di model yang akan hash otomatis
        );

        // =============================================
        // SEED 31 KECAMATAN JEMBER
        // =============================================
        $kecamatan = [
            ['nama' => 'Ajung', 'latitude' => -8.2300, 'longitude' => 113.6500, 'elevasi' => 60, 'kode_wilayah' => '35.09.22.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Ambulu', 'latitude' => -8.3500, 'longitude' => 113.6000, 'elevasi' => 12, 'kode_wilayah' => '35.09.07.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Arjasa', 'latitude' => -8.1000, 'longitude' => 113.7000, 'elevasi' => 200, 'kode_wilayah' => '35.09.23.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Balung', 'latitude' => -8.2800, 'longitude' => 113.5500, 'elevasi' => 40, 'kode_wilayah' => '35.09.05.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Bangsalsari', 'latitude' => -8.2000, 'longitude' => 113.5000, 'elevasi' => 85, 'kode_wilayah' => '35.09.16.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Gumukmas', 'latitude' => -8.3000, 'longitude' => 113.4500, 'elevasi' => 10, 'kode_wilayah' => '35.09.04.2001', 'level_rawan' => 'tinggi'],
            ['nama' => 'Jelbuk', 'latitude' => -8.0800, 'longitude' => 113.6500, 'elevasi' => 325, 'kode_wilayah' => '35.09.24.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Jenggawah', 'latitude' => -8.2500, 'longitude' => 113.7000, 'elevasi' => 95, 'kode_wilayah' => '35.09.12.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Jombang', 'latitude' => -8.3200, 'longitude' => 113.5000, 'elevasi' => 35, 'kode_wilayah' => '35.09.01.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Kalisat', 'latitude' => -8.1000, 'longitude' => 113.8000, 'elevasi' => 250, 'kode_wilayah' => '35.09.18.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Kaliwates', 'latitude' => -8.1721, 'longitude' => 113.6873, 'elevasi' => 82, 'kode_wilayah' => '35.09.19.1001', 'level_rawan' => 'sedang'],
            ['nama' => 'Kencong', 'latitude' => -8.3000, 'longitude' => 113.4000, 'elevasi' => 10, 'kode_wilayah' => '35.09.02.2001', 'level_rawan' => 'tinggi'],
            ['nama' => 'Ledokombo', 'latitude' => -8.0500, 'longitude' => 113.8500, 'elevasi' => 425, 'kode_wilayah' => '35.09.25.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Mayang', 'latitude' => -8.1500, 'longitude' => 113.8500, 'elevasi' => 200, 'kode_wilayah' => '35.09.26.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Mumbulsari', 'latitude' => -8.2000, 'longitude' => 113.8000, 'elevasi' => 150, 'kode_wilayah' => '35.09.13.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Pakusari', 'latitude' => -8.1500, 'longitude' => 113.7500, 'elevasi' => 150, 'kode_wilayah' => '35.09.27.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Panti', 'latitude' => -8.1200, 'longitude' => 113.6000, 'elevasi' => 125, 'kode_wilayah' => '35.09.28.2001', 'level_rawan' => 'tinggi'],
            ['nama' => 'Patrang', 'latitude' => -8.1400, 'longitude' => 113.7000, 'elevasi' => 120, 'kode_wilayah' => '35.09.20.1001', 'level_rawan' => 'rendah'],
            ['nama' => 'Puger', 'latitude' => -8.3800, 'longitude' => 113.4500, 'elevasi' => 6, 'kode_wilayah' => '35.09.06.2001', 'level_rawan' => 'tinggi'],
            ['nama' => 'Rambipuji', 'latitude' => -8.2500, 'longitude' => 113.6000, 'elevasi' => 65, 'kode_wilayah' => '35.09.03.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Semboro', 'latitude' => -8.2000, 'longitude' => 113.4500, 'elevasi' => 45, 'kode_wilayah' => '35.09.14.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Silo', 'latitude' => -8.1000, 'longitude' => 113.9000, 'elevasi' => 425, 'kode_wilayah' => '35.09.29.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Sukorambi', 'latitude' => -8.1500, 'longitude' => 113.6000, 'elevasi' => 200, 'kode_wilayah' => '35.09.30.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Sukowono', 'latitude' => -8.0500, 'longitude' => 113.7500, 'elevasi' => 325, 'kode_wilayah' => '35.09.31.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Sumberbaru', 'latitude' => -8.2500, 'longitude' => 113.4000, 'elevasi' => 275, 'kode_wilayah' => '35.09.11.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Sumberjambe', 'latitude' => -8.0000, 'longitude' => 113.8000, 'elevasi' => 550, 'kode_wilayah' => '35.09.10.2001', 'level_rawan' => 'rendah'],
            ['nama' => 'Sumbersari', 'latitude' => -8.1650, 'longitude' => 113.7060, 'elevasi' => 90, 'kode_wilayah' => '35.09.21.1001', 'level_rawan' => 'rendah'],
            ['nama' => 'Tanggul', 'latitude' => -8.2000, 'longitude' => 113.4500, 'elevasi' => 80, 'kode_wilayah' => '35.09.10.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Tempurejo', 'latitude' => -8.3000, 'longitude' => 113.8000, 'elevasi' => 25, 'kode_wilayah' => '35.09.08.2001', 'level_rawan' => 'tinggi'],
            ['nama' => 'Umbulsari', 'latitude' => -8.2500, 'longitude' => 113.5000, 'elevasi' => 40, 'kode_wilayah' => '35.09.15.2001', 'level_rawan' => 'sedang'],
            ['nama' => 'Wuluhan', 'latitude' => -8.3000, 'longitude' => 113.6000, 'elevasi' => 15, 'kode_wilayah' => '35.09.09.2001', 'level_rawan' => 'sedang'],
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
            [
                'nama' => 'Layanan Darurat Terpadu Jember', 
                'nomor' => '112', 
                'kategori' => 'lainnya', 
                'keterangan' => 'Nomor darurat tunggal bebas pulsa (Ambulans, Polisi, Damkar)'
            ],
            [
                'nama' => 'Call Center Bencana Nasional', 
                'nomor' => '117', 
                'kategori' => 'lainnya', 
                'keterangan' => 'Hotline Bencana Nasional BNPB'
            ],
            [
                'nama' => 'PLN Jember', 
                'nomor' => '123', 
                'kategori' => 'pln', 
                'keterangan' => 'Gangguan Listrik dan Kabel Putus'
            ],
            [
                'nama' => 'BPBD Kabupaten Jember', 
                'nomor' => '0331-487500', 
                'kategori' => 'bpbd', 
                'keterangan' => 'Pusat Pengendalian Operasi Penanggulangan Bencana'
            ],
            [
                'nama' => 'Pusdalops BPBD Jember (WhatsApp)', 
                'nomor' => '081235478440', 
                'kategori' => 'bpbd', 
                'keterangan' => 'Layanan laporan kejadian bencana via WhatsApp'
            ],
            [
                'nama' => 'Polres Jember', 
                'nomor' => '0331-486110', 
                'kategori' => 'polisi', 
                'keterangan' => 'Kepolisian Resort Jember'
            ],
            [
                'nama' => 'Damkar Jember', 
                'nomor' => '0331-421113', 
                'kategori' => 'pemadam', 
                'keterangan' => 'Pemadam Kebakaran Kabupaten Jember'
            ],
            [
                'nama' => 'SAR Jember', 
                'nomor' => '0331-335577', 
                'kategori' => 'sar', 
                'keterangan' => 'Search and Rescue Jember'
            ],
            [
                'nama' => 'Kodim 0824 Jember', 
                'nomor' => '0331-483501', 
                'kategori' => 'lainnya', 
                'keterangan' => 'Komando Distrik Militer Jember'
            ],
            [
                'nama' => 'RSUD dr. Soebandi', 
                'nomor' => '0331-487441', 
                'kategori' => 'ambulans', 
                'keterangan' => 'IGD Rumah Sakit Umum Daerah (Pusat Kota)'
            ],
            [
                'nama' => 'RSUD Kalisat', 
                'nomor' => '0331-331244', 
                'kategori' => 'ambulans', 
                'keterangan' => 'IGD Rumah Sakit Umum Daerah (Wilayah Timur)'
            ],
            [
                'nama' => 'RSUD Balung', 
                'nomor' => '0331-621595', 
                'kategori' => 'ambulans', 
                'keterangan' => 'IGD Rumah Sakit Umum Daerah (Wilayah Selatan)'
            ],
            [
                'nama' => 'PMI Jember (Unit Donor Darah)', 
                'nomor' => '0331-337022', 
                'kategori' => 'ambulans', 
                'keterangan' => 'Layanan Ambulans dan Stok Darah PMI'
            ],
            [
                'nama' => 'RS Baladhika Husada (RS DK)', 
                'nomor' => '0331-484674', 
                'kategori' => 'ambulans', 
                'keterangan' => 'IGD Rumah Sakit Tentara Jember'
            ],
        ];

        foreach ($kontakDarurat as $kontak) {
            DB::table('kontak_darurat')->insert(array_merge(
                [
                    'id' => (string) \Illuminate\Support\Str::uuid(), 
                    'is_active' => true
                ],
                $kontak
            ));
        }

        // =============================================
        // SEED FAQ 
        // =============================================
        $faqs = [
            [
                'pertanyaan' => 'Apakah saya harus membuat akun untuk menggunakan aplikasi Jesi?',
                'jawaban' => 'Ya, pengguna perlu login terlebih dahulu untuk dapat menggunakan fitur-fitur yang tersedia pada aplikasi Jember Siaga. Dengan melakukan login, pengguna dapat mengakses layanan seperti mengirim laporan bencana, melihat riwayat pengaduan, serta menerima informasi yang lebih personal dari aplikasi.',
                'kategori' => 'akun',
                'urutan' => 1
            ],
            [
                'pertanyaan' => 'Bagaimana cara melaporkan bencana melalui aplikasi JESI?',
                'jawaban' => 'Untuk melaporkan bencana melalui aplikasi JESI, kamu bisa membuka aplikasi JESI kemudian memilih menu Pengaduan Bencana/Lapor. Setelah itu, isi informasi yang diminta seperti lokasi, jenis bencana, dan deskripsi kejadian, lalu kirim laporan agar dapat ditindaklanjuti oleh petugas. 📱💡',
                'kategori' => 'laporan',
                'urutan' => 2
            ],
            [
                'pertanyaan' => 'Bagaimana cara mengetahui prediksi cuaca di aplikasi?',
                'jawaban' => 'Untuk mengetahui kondisi cuaca, pengguna dapat membuka menu Prediksi Cuaca pada aplikasi. Informasi cuaca akan ditampilkan berdasarkan data terbaru sehingga pengguna dapat mengetahui perkiraan cuaca di wilayahnya.',
                'kategori' => 'cuaca',
                'urutan' => 3
            ],
            [
                'pertanyaan' => 'Bagaimana cara mengetahui status laporan yang sudah dikirim?',
                'jawaban' => 'Setelah laporan dikirim, pengguna dapat melihat perkembangan atau status laporan melalui menu Riwayat Pengaduan pada aplikasi. Pada menu tersebut akan ditampilkan informasi mengenai proses verifikasi atau penanganan laporan.',
                'kategori' => 'laporan',
                'urutan' => 4
            ],
            [
                'pertanyaan' => 'Bagaimana cara menghubungi call center melalui aplikasi?',
                'jawaban' => 'Untuk menghubungi call center, pengguna dapat membuka aplikasi Jember Siaga lalu memilih menu Call Center. Pada menu tersebut akan ditampilkan nomor layanan yang dapat dihubungi sehingga pengguna dapat langsung melakukan panggilan untuk mendapatkan bantuan atau informasi terkait bencana.',
                'kategori' => 'layanan',
                'urutan' => 5
            ],
            [
                'pertanyaan' => 'Apakah aplikasi dapat menampilkan peringatan banjir?',
                'jawaban' => 'Ya, aplikasi Jesi dapat menampilkan informasi peringatan dini jika terdapat potensi banjir atau informasi bencana di wilayah sekitar. Informasi ini membantu pengguna agar dapat lebih siap menghadapi kemungkinan terjadinya bencana.',
                'kategori' => 'peringatan',
                'urutan' => 6
            ],
            [
                'pertanyaan' => 'Bagaimana cara mengirim foto saat melakukan pengaduan bencana?',
                'jawaban' => 'Saat mengisi formulir pengaduan bencana, pengguna dapat memilih opsi unggah foto. Pengguna bisa mengambil foto langsung melalui kamera atau memilih gambar dari galeri ponsel sebagai bukti kondisi di lokasi kejadian.',
                'kategori' => 'laporan',
                'urutan' => 7
            ],
        ];

        foreach ($faqs as $faq) {
            DB::table('faq')->insert(array_merge(
                [
                    'id' => (string) \Illuminate\Support\Str::uuid(), 
                    'is_active' => true, 
                    'dibuat_pada' => now(), 
                    'updated_at' => now()
                ],
                $faq
            ));
        }

        // =============================================
        // SEED BERITA (LOOPING)
        // =============================================
        $beritaList = [
            [
                'judul' => 'Jember Siaga Darurat Kekeringan hingga Agustus 2026, Ada 7 Daerah Rawan',
                'slug' => Str::slug('Jember Siaga Darurat Kekeringan hingga Agustus 2026'),
                'konten' => "JEMBER - Badan Penanggulangan Bencana Daerah (BPBD) Kabupaten Jember secara resmi telah menetapkan status siaga darurat bencana kekeringan yang diperkirakan akan berlangsung cukup lama. Penetapan status ini didasarkan pada rilis data meteorologi yang menunjukkan adanya penurunan signifikan curah hujan di wilayah Jawa Timur, khususnya Kabupaten Jember, yang diprediksi akan mencapai puncaknya pada periode April hingga Agustus 2026.
                            Berdasarkan hasil pemetaan teknis di lapangan, terdapat sedikitnya tujuh kecamatan yang kini masuk dalam daftar prioritas karena tingkat kerawanan krisis air bersih yang sangat tinggi. Wilayah-wilayah tersebut meliputi Kecamatan Patrang, Arjasa, Jelbuk, Kalisat, Ledokombo, Sumberjambe, dan Silo. Sebagian besar dari wilayah ini berada di area dataran tinggi atau perbukitan yang sangat bergantung pada kestabilan sumber mata air alami dan sumur gali warga.
                            Kepala BPBD Jember menjelaskan bahwa langkah penetapan status siaga ini bukan untuk memicu kepanikan, melainkan sebagai payung hukum dalam mempercepat proses birokrasi penyaluran bantuan logistik dan operasional armada tangki air. Saat ini, pemerintah telah menyiagakan sekitar 10 unit armada tangki air bersih yang mampu mendistribusikan ribuan liter air setiap harinya ke titik-titik terdampak. Koordinasi dengan Perumdam Tirta Pandalungan juga terus diperkuat untuk memastikan suplai air bersih di wilayah perkotaan tetap terjaga meski debit sumber mengecil.
                            Selain dampak langsung pada kebutuhan rumah tangga, pemerintah juga mewaspadai dampak kekeringan terhadap sektor pertanian yang menjadi tulang punggung ekonomi Jember. Banyak lahan persawahan di wilayah timur yang mulai mengalami retak-retak akibat tidak adanya pasokan air irigasi. Masyarakat diimbau untuk bijak dalam menggunakan air, menampung air hujan yang tersisa, serta segera memberikan informasi kepada petugas melalui layanan Jember Siaga jika kondisi krisis air di lingkungannya sudah tidak bisa ditangani secara mandiri.",
                'ringkasan' => 'BPBD Kabupaten Jember menetapkan status siaga darurat bencana kekeringan hingga Agustus 2026.',
                'foto_cover' => 'kekeringan-jember.jpg',
                'kategori' => 'umum',
                'sumber' => 'https://surabaya.kompas.com',
                'tags' => ['kekeringan', 'jember', 'siaga darurat']
            ],
            [
                'judul' => 'Waspada! Jember Masuk Zona Merah Kekeringan 2026, BPBD Petakan Antisipasi Megathrust',
                'slug' => Str::slug('Jember Masuk Zona Merah Kekeringan 2026 Antisipasi Megathrust'),
                'konten' => "JEMBER - Kondisi cuaca ekstrem yang melanda Kabupaten Jember telah memaksa otoritas terkait untuk mengeluarkan peringatan dini yang lebih intens. Sebagian besar wilayah di Kabupaten Jember kini telah secara resmi masuk dalam kategori Zona Merah kekeringan. Kondisi ini menandakan bahwa indeks ketersediaan air tanah telah mencapai level terendah dalam beberapa tahun terakhir, yang berpotensi memicu kegagalan panen massal dan krisis air minum jika tidak ditangani dengan strategi yang komprehensif.
                            Namun, kekeringan bukanlah satu-satunya fokus pemerintah saat ini. Dalam pertemuan koordinasi lintas sektoral yang dilakukan di kantor BPBD, ditekankan bahwa mitigasi bencana kekeringan harus berjalan paralel dengan kesiapsiagaan menghadapi potensi gempa bumi besar atau Megathrust di zona subduksi selatan Jawa. Mengingat Jember memiliki garis pantai selatan yang panjang, risiko tsunami akibat pergerakan lempeng tektonik menjadi ancaman nyata yang harus diantisipasi sejak dini.
                            Oleh karena itu, pemerintah daerah mulai memasang dan mengkalibrasi ulang sejumlah perangkat Early Warning System (EWS) di sepanjang pesisir pantai Selatan Jember. Selain teknologi, penguatan kapasitas sumber daya manusia di tingkat desa menjadi kunci utama. Pembentukan Desa Tangguh Bencana (Destana) kini difokuskan pada simulasi evakuasi mandiri dan pemetaan jalur aman bagi warga pesisir.
                            BPBD Jember juga bekerja sama dengan akademisi dari universitas setempat untuk melakukan kajian risiko bencana multiancaman. Warga diminta untuk tetap tenang namun waspada, selalu memantau perkembangan informasi melalui aplikasi resmi, dan tidak mudah termakan oleh isu-isu yang tidak dapat dipertanggungjawabkan kebenarannya terkait gempa maupun kekeringan yang sedang terjadi.",
                'ringkasan' => 'BPBD Jember memetakan zona merah kekeringan 2026 dan menyiapkan langkah antisipasi Megathrust.',
                'foto_cover' => 'zona-merah-jember.jpg',
                'kategori' => 'umum',
                'sumber' => 'https://radarjember.jawapos.com',
                'tags' => ['kekeringan', 'zona merah', 'megathrust']
            ],
            [
                'judul' => 'Banjir Luapi Pemukiman Warga Mumbulsari, Pemkab Jember Desak Pengerukan Sungai',
                'slug' => Str::slug('Banjir Luapi Pemukiman Warga Mumbulsari Jember'),
                'konten' => "JEMBER - Hujan deras yang mengguyur wilayah Kabupaten Jember sejak siang hingga sore hari menyebabkan debit air sungai di Kecamatan Mumbulsari meningkat drastis. Ketidakmampuan sungai dalam menampung volume air yang meluap mengakibatkan banjir luapan yang merendam puluhan rumah warga di kawasan pemukiman padat penduduk. Ketinggian genangan air bervariasi antara 30 hingga 60 sentimeter, yang menyebabkan lumpuhnya aktivitas ekonomi warga setempat selama beberapa jam.
                            Banyak warga yang terkejut dengan kecepatan naiknya air, sehingga mereka hanya sempat mengamankan barang-barang berharga dan dokumen penting ke bagian rumah yang lebih tinggi. Tim reaksi cepat dari BPBD segera dikerahkan ke lokasi untuk membantu proses evakuasi warga kelompok rentan, seperti lansia dan balita, menuju titik pengungsian sementara di balai desa terdekat.
                            Pemerintah Kabupaten Jember menilai bahwa kejadian ini bukan murni faktor alam, melainkan adanya masalah struktural pada infrastruktur sungai. Tingginya sedimentasi dan tumpukan sampah di dasar sungai menjadi penyebab utama air tidak dapat mengalir dengan lancar menuju hilir. Menanggapi situasi ini, Pemkab Jember mengeluarkan desakan keras kepada Pemerintah Provinsi Jawa Timur untuk segera melakukan normalisasi dan pengerukan sungai di wilayah Mumbulsari.
                            Normalisasi sungai dianggap sebagai solusi jangka panjang yang paling mendesak karena jika hanya mengandalkan bantuan logistik pascabencana, kerugian materiil warga akan terus berulang setiap musim hujan tiba. Sementara itu, petugas kebersihan dan relawan mulai bahu-membahu membersihkan sisa-sisa lumpur dan sampah yang tertinggal di dalam rumah warga setelah air mulai surut pada malam harinya. Pengendara yang melintas di wilayah Mumbulsari juga diminta berhati-hati karena jalanan masih licin akibat sisa material banjir.",
                'ringkasan' => 'Luapan sungai merendam rumah warga di Mumbulsari; Pemkab Jember minta normalisasi sungai.',
                'foto_cover' => 'banjir-mumbulsari.jpg',
                'kategori' => 'banjir',
                'sumber' => 'https://radarjember.jawapos.com',
                'tags' => ['banjir', 'mumbulsari', 'jember']
            ],
            [
                'judul' => 'Jember Dikepung Cuaca Ekstrem: Hujan Angin Hantam 4 Kecamatan, 13 Rumah Rusak',
                'slug' => Str::slug('Jember Dikepung Cuaca Ekstrem Hujan Angin 4 Kecamatan'),
                'konten' => "JEMBER - Kabupaten Jember kembali berduka akibat terjangan cuaca ekstrem yang melanda secara mendadak pada sore hari tadi. Fenomena hujan deras yang disertai angin kencang atau downburst dilaporkan menghantam setidaknya empat kecamatan di wilayah perkotaan dan sekitarnya, yakni Kecamatan Kaliwates, Sumbersari, Patrang, dan Ajung. Kecepatan angin yang sangat tinggi menyebabkan puluhan pohon bertumbangan dan beberapa papan reklame mengalami kerusakan serius.
                            Berdasarkan laporan kaji cepat yang dirilis oleh pusdalops BPBD Jember, tercatat sedikitnya 13 unit rumah warga mengalami kerusakan dengan tingkat ringan hingga sedang. Kerusakan yang paling dominan terjadi pada bagian atap rumah, di mana genteng dan asbes beterbangan terbawa angin. Beberapa rumah lainnya juga mengalami kerusakan setelah tertimpa batang pohon besar yang roboh. Selain kerugian pada hunian, jaringan listrik di beberapa ruas jalan protokol sempat terputus akibat tertimpa dahan pohon, yang menyebabkan pemadaman listrik total di beberapa wilayah terdampak.
                            Relawan bersama petugas PLN telah berada di lapangan sejak sore hari untuk melakukan proses evakuasi pohon tumbang menggunakan gergaji mesin dan melakukan perbaikan jaringan kabel listrik yang putus. Beruntung, dalam rangkaian peristiwa cuaca ekstrem ini tidak dilaporkan adanya korban jiwa maupun luka berat, namun kerugian materiil yang diderita oleh masyarakat terdampak diperkirakan mencapai angka puluhan juta rupiah.
                            Pihak BMKG terus mengeluarkan imbauan agar masyarakat tetap waspada terhadap potensi cuaca ekstrem susulan yang masih mungkin terjadi hingga beberapa hari ke depan. Fenomena peralihan musim atau pancaroba seringkali memicu pembentukan awan Cumulonimbus yang sangat aktif, yang berujung pada hujan angin dan petir. Warga diminta untuk tidak berteduh di bawah pohon besar, baliho, atau bangunan yang konstruksinya sudah rapuh saat hujan deras disertai angin mulai terjadi.",
                'ringkasan' => 'Hujan angin di 4 kecamatan Jember mengakibatkan kerusakan pada 13 rumah warga.',
                'foto_cover' => 'cuaca-ekstrem-jember.jpg',
                'kategori' => 'angin_kencang',
                'sumber' => 'https://radarjember.jawapos.com',
                'tags' => ['cuaca ekstrem', 'angin kencang', 'jember']
            ],
            [
                'judul' => 'Waspada! Jalur Gumitir Jember-Banyuwangi Kembali Diterjang Longsor dan Pohon Tumbang',
                'slug' => Str::slug('Jalur Gumitir Jember Banyuwangi Terjang Longsor Pohon Tumbang'),
                'konten' => "JEMBER - Konektivitas transportasi antara Kabupaten Jember dan Kabupaten Banyuwangi kembali terganggu setelah jalur lintas selatan di Gunung Gumitir diterjang longsor dan pohon tumbang. Kejadian ini dipicu oleh tingginya intensitas hujan yang mengguyur area pegunungan sejak malam hari, yang menyebabkan struktur tanah pada lereng curam di kawasan Watu Gudang menjadi labil dan akhirnya longsor menutup sebagian badan jalan nasional.
                            Material tanah bercampur bebatuan serta batang pohon yang melintang di jalan mengakibatkan kemacetan panjang yang mencapai beberapa kilometer dari kedua arah. Banyak pengendara, baik bus antarkota maupun truk pengangkut logistik, terpaksa berhenti total karena jalur tersebut merupakan akses satu-satunya yang paling efisien menuju Pelabuhan Ketapang, Banyuwangi. Situasi ini tentu menghambat arus distribusi barang dan mobilisasi masyarakat antar kabupaten.
                            Menanggapi laporan tersebut, tim dari BPBD Jember bersama jajaran kepolisian sektor setempat segera turun ke lokasi untuk mengatur lalu lintas dan melakukan pembersihan material secara manual. Petugas juga berkoordinasi dengan dinas pekerjaan umum untuk mengerahkan alat berat guna mempercepat pembersihan sisa-sisa tanah yang masih menempel di aspal agar jalan tidak licin.
                            Hingga saat ini, sistem buka-tutup jalur telah diberlakukan untuk mengurai kepadatan kendaraan, namun para pengguna jalan tetap diimbau untuk waspada tinggi saat melintas, terutama pada titik-titik rawan longsor yang belum sempat dipasang dinding penahan tanah. Pihak kepolisian mengingatkan para pengendara agar menjaga jarak aman dan tidak menyalip di tikungan tajam Gumitir saat kondisi cuaca sedang hujan kabut, mengingat risiko kecelakaan dan longsor susulan masih sangat tinggi di wilayah pegunungan tersebut.",
                'ringkasan' => 'Longsor dan pohon tumbang kembali terjadi di jalur Gumitir kawasan Watu Gudang.',
                'foto_cover' => 'longsor-gumitir.jpg',
                'kategori' => 'longsor',
                'sumber' => 'https://radarjember.jawapos.com',
                'tags' => ['longsor', 'jalur gumitir', 'pohon tumbang']
            ],
        ];

        foreach ($beritaList as $data) {
            $beritaId = (string) Str::uuid();
            $tags = $data['tags'];
            unset($data['tags']);

            // Proses Copy Gambar
            $sourcePath = database_path('seeders/images/' . $data['foto_cover']);
            $destinationPath = storage_path('app/public/uploads/berita/' . $data['foto_cover']);
            if (File::exists($sourcePath)) {
                if (!File::isDirectory(dirname($destinationPath))) { File::makeDirectory(dirname($destinationPath), 0755, true); }
                File::copy($sourcePath, $destinationPath);
            }

            // Insert Berita
            DB::table('berita')->insert(array_merge(
                [
                    'id' => $beritaId,
                    'dibuat_oleh' => $superAdmin->id,
                    'views_count' => 0,
                    'status' => 'published',
                    'dibuat_pada' => now(),
                    'dipublikasi_pada' => now(),
                    'updated_at' => now(),
                ],
                $data
            ));

            // Insert Tags
            foreach ($tags as $tagName) {
                DB::table('berita_tags')->insert([
                    'id' => (string) Str::uuid(),
                    'berita_id' => $beritaId,
                    'tag' => $tagName
                ]);
            }
        }
    }
}
