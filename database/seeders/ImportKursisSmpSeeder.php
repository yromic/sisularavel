<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportKursisSmpSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Pastikan Divisi KURSISSMP ada
            $division = Division::firstOrCreate(
                ['code' => 'KURSISSMP'],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Kurikulum dan Kesiswaan SMP',
                    'status' => 'active',
                ]
            );

            $adminUser = User::where('role', 'super_admin')->first();
            $adminId = $adminUser?->id;

            // 2. Data Surat Masuk (22 data)
            $incomingData = [
                [
                    'number' => 'B-PM.02.04.55.06.26.474',
                    'subject' => 'Undangan Kegiatan Gebyar SAPA Sekolah dalam rangka World Food Safety Day',
                    'date' => '2026-06-26',
                    'origin' => 'Badan POM / Instansi Eksternal',
                    'drive' => 'https://drive.google.com/uc?export=view&id=17MbPKQMJwNL1ukPeIcMPgjdavMSip6gX',
                ],
                [
                    'number' => 'B/047/N/KA/PROO/2026',
                    'subject' => 'Rakor Akselerasi Implementasi P4GN',
                    'date' => '2026-05-21',
                    'origin' => 'BNNP Jawa Tengah',
                    'drive' => 'https://drive.google.com/uc?export=view&id=1jzMGL2T8pMVimpnY6Zk4bhQKM07ZbZlN',
                ],
                [
                    'number' => '400.3.5/500/2026',
                    'subject' => 'undangan rapat tentang bantuan',
                    'date' => '2026-06-09',
                    'origin' => 'Instansi Pengirim',
                    'drive' => 'https://drive.google.com/uc?export=view&id=1JLOUTJ-BbuyaHP27L_gnT8fPhb-IyMaC',
                ],
                [
                    'number' => 'B/047/N/KA/PROO/2026-02',
                    'subject' => 'Rakor Akselerasi Implementasi P4GN (Lampiran II)',
                    'date' => '2026-05-21',
                    'origin' => 'BNNP Jawa Tengah',
                    'drive' => 'https://drive.google.com/uc?export=view&id=1nAo6BgadcV5uBp3OTg3Ubuj5Y6IEJ_5e',
                ],
                [
                    'number' => '0419/BAN.PDM.Jateng/TU/2026',
                    'subject' => 'Pemberitahuan Pengajuan Reakreditasi',
                    'date' => '2026-05-21',
                    'origin' => 'BAN PDM Jateng',
                    'drive' => 'https://drive.google.com/open?id=1oDlmfWgwg73saVkSDxawB3JYhtNrzba4',
                ],
                [
                    'number' => '1852/B/C4/DM.00.02',
                    'subject' => 'Pendampingan sekolah model implementasi PM dan KKA Region I',
                    'date' => '2026-06-02',
                    'origin' => 'Kemendikbudristek Ditjen PDM',
                    'drive' => 'https://drive.google.com/open?id=1LlpfBlJOlfSBuhNbliKAvCJ2I32hidCh',
                ],
                [
                    'number' => 'B/0471/V/KA/PR.00/2026/BNNP',
                    'subject' => 'Undangan Rapat Rakor Akselerasi Implementasi P4GN',
                    'date' => '2026-06-04',
                    'origin' => 'BNNP Jawa Tengah',
                    'drive' => 'https://drive.google.com/open?id=1Mu2LtKQ9sAClzN-P_jEAOxzBiBYq5sdC',
                ],
                [
                    'number' => '500.14/513/2026',
                    'subject' => 'Permohonan KAK dan Uptade data Spasial',
                    'date' => '2026-05-25',
                    'origin' => 'Instansi Pengirim',
                    'drive' => 'https://drive.google.com/open?id=1jS_A_J2qn2zwYK7y4x7jPeAw4Pw6aF-M',
                ],
                [
                    'number' => '0535/B/C6.3/DM.00.02',
                    'subject' => 'Pendataan dan Pemetaan ULD Bid. Pendidikan Tahun 2026',
                    'date' => '2026-05-24',
                    'origin' => 'Kemendikbudristek Ditjen PDM',
                    'drive' => 'https://drive.google.com/open?id=1fRNRkoG91YajbeOyke9M6HQHthVXnr88',
                ],
                [
                    'number' => '0562/B/c6.3/DM.002/2026',
                    'subject' => 'Pelaksana Gugus kendali Mutu (GKM)',
                    'date' => '2026-06-02',
                    'origin' => 'Kemendikbudristek Ditjen PDM',
                    'drive' => 'https://drive.google.com/open?id=1zT6ax9suAKsKxKanr5MYyEwnQcEurA7e',
                ],
                [
                    'number' => '04/KUDA EMAS/VI/2026',
                    'subject' => 'Mohon bantuan menyebarluaskan',
                    'date' => '2026-06-01',
                    'origin' => 'Kuda Emas',
                    'drive' => 'https://drive.google.com/open?id=10r77oPm5BP7mCffpIVAliPoRpg_vAN-L',
                ],
                [
                    'number' => '421.7/114/smp/.N 3/V/2026',
                    'subject' => 'Pemberitahuan kegiatan Studi wisata',
                    'date' => '2026-05-26',
                    'origin' => 'SMP Negeri 3',
                    'drive' => 'https://drive.google.com/open?id=1G3bIdEpkZ3RpX-ZQrXQIxXK0Xag7w_hp',
                ],
                [
                    'number' => '1003/09568',
                    'subject' => 'Rapat penyusunan rancangan peraturan bupati semarang',
                    'date' => '2026-06-05',
                    'origin' => 'Setda Kab. Semarang',
                    'drive' => 'https://drive.google.com/open?id=157nnSn0JBnFftGwq5On9y5INWjyVP5jJ',
                ],
                [
                    'number' => '421.2/066/26',
                    'subject' => 'Permohonan bantuan perawatan perbaikan mushola',
                    'date' => '2026-06-02',
                    'origin' => 'Instansi Pengirim',
                    'drive' => 'https://drive.google.com/open?id=1OZlQvYE1AbmQfH1YgMXCO_D4_gmG4wrg',
                ],
                [
                    'number' => '0552/B/C6.3/DM.00.02/2026',
                    'subject' => 'Kegiatan pemetaan mutu pendidikan Prov.Jateng',
                    'date' => '2026-05-29',
                    'origin' => 'Dinas Pendidikan Provinsi Jateng',
                    'drive' => 'https://drive.google.com/open?id=1hcBP4n9k-bOUHuLVnOywJqPt5KVy0J0F',
                ],
                [
                    'number' => '003/WWMR/VI',
                    'subject' => 'Permohonan sambutan wasana warsa',
                    'date' => '2026-06-08',
                    'origin' => 'Panitia Wasana Warsa',
                    'drive' => 'https://drive.google.com/open?id=1GbNqLvv0mQwc6WHx8iibQz70vpWdC4WK',
                ],
                [
                    'number' => '36/FBBD/26',
                    'subject' => 'Permohonan Ijin Praktik pengalaman lapangan',
                    'date' => '2026-03-30',
                    'origin' => 'FBBD',
                    'drive' => 'https://drive.google.com/open?id=1sdx4cj50fkxTsSoV4ix5uyIGlNyJta-0',
                ],
                [
                    'number' => '0014/08560',
                    'subject' => 'Rakor Sinergitas pelaksanaan kebijakan bid . Kersa pelayanan dasar dan kementasan kemiskinan',
                    'date' => '2026-05-19',
                    'origin' => 'Setda Kab. Semarang',
                    'drive' => 'https://drive.google.com/open?id=1vAc8w5M5FUZPZM9N3IAnesdO9DCNalLB',
                ],
                [
                    'number' => '0014/08856',
                    'subject' => 'Sholat idul Adha',
                    'date' => '2026-05-22',
                    'origin' => 'Panitia Peringatan Hari Besar Islam',
                    'drive' => 'https://drive.google.com/open?id=1hoZsZqGkNY7srh7CFSISs1oYAvJ4GCKx',
                ],
                [
                    'number' => '25.5.26',
                    'subject' => 'Proposal smp literasi istiqomah',
                    'date' => '2026-05-25',
                    'origin' => 'SMP Literasi Istiqomah',
                    'drive' => 'https://drive.google.com/open?id=1FJtznTKNEdkWiKRMYlbXdef51bJkfIAg',
                ],
                [
                    'number' => '421.2/306/2026',
                    'subject' => 'Pemberitahuan Outing Class',
                    'date' => '2026-05-22',
                    'origin' => 'SMP Pengirim',
                    'drive' => 'https://drive.google.com/open?id=1aRuhpeouVFxtG-cgJRX1yvoSWgJmHefM',
                ],
                [
                    'number' => 'UND-1/PK/PK.3',
                    'subject' => 'Sosialisasi PMK Nomor 25 tahun 2026',
                    'date' => '2026-05-21',
                    'origin' => 'Kementerian Keuangan RI',
                    'drive' => 'https://drive.google.com/open?id=1iYsb5WXh1dYCW9GoLRhK6zbJ5MgvH-ez',
                ],
            ];

            foreach ($incomingData as $item) {
                $letter = IncomingLetter::create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $division->id,
                    'letter_number' => $item['number'],
                    'origin_agency' => $item['origin'],
                    'letter_date' => $item['date'],
                    'received_date' => $item['date'],
                    'subject' => $item['subject'],
                    'created_by_user_id' => $adminId,
                ]);

                $fileId = $this->extractDriveId($item['drive']);

                Attachment::create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $division->id,
                    'attachable_type' => IncomingLetter::class,
                    'attachable_id' => $letter->id,
                    'original_name' => Str::slug($item['number']).'.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => 0,
                    'is_missing' => true,
                    'migration_status' => 'migrated',
                    'legacy_drive_file_id' => $fileId,
                    'legacy_drive_url' => $item['drive'],
                    'created_by_user_id' => $adminId,
                ]);
            }

            // 3. Data Surat Keluar (18 data baris, deduplikasi baris 28 & pembagian Nota Dinas)
            $outgoingData = [
                [
                    'number' => '005/2289/2026',
                    'subject' => 'Undangan sosialisasi Penerbitan Ijazah',
                    'date' => '2026-06-25',
                    'drive' => 'https://drive.google.com/uc?export=view&id=10fyvA1YNQTLYAZspBSTPR70rHaSfAhbM',
                ],
                [
                    'number' => '400.3.5/800/2026',
                    'subject' => 'Pengelolaan Dokumen Murid Kelas IX Tahun Ajaran 2025/2026 Jenjang SMP',
                    'date' => '2026-06-15',
                    'drive' => 'https://drive.google.com/uc?export=view&id=1tFButrp773wAbN5PpTNswwJMRqbitixH',
                ],
                [
                    'number' => '800.1.11.1/2179/2026',
                    'subject' => 'Surat Tugas Melaksanakan tugas keabsahan O2SN Tingkat Provinsi Jawa Tengah 2026',
                    'date' => '2026-06-17',
                    'drive' => 'https://drive.google.com/open?id=1rtQKpjsCWKJXQ3AkLYyzqm7zMnJPuQdE',
                ],
                [
                    'number' => '800.1.11.1/2175/2026',
                    'subject' => 'Surat Tugas Rapat Koordinasi Program Prioritas Makan Bergizi Gratis(MBG) Tahun 2026',
                    'date' => '2026-06-17',
                    'drive' => 'https://drive.google.com/open?id=1VhFBKS1WZGlxD1yE5XCVDVYajCs3tzXJ',
                ],
                [
                    'number' => '400.3/1261/2026',
                    'subject' => 'Surat Izin Operasional Sekolah',
                    'date' => '2026-04-16',
                    'drive' => 'https://drive.google.com/open?id=1rRJjr-JPDYpofS3ew4MB06zpj2tL4AEk',
                ],
                // Catatan: Baris 28 sama persis dengan baris 24 (400.3.5/800/2026). Disesuaikan dengan sufiks untuk kepatuhan UNIQUE annual constraint.
                [
                    'number' => '400.3.5/800/2026-DUPLIKAT',
                    'subject' => 'Pengelolaan Dokumen Murid Kelas IX Tahun Ajaran 2025/2026 Jenjang SMP (Salinan Arsip)',
                    'date' => '2026-06-15',
                    'drive' => 'https://drive.google.com/uc?export=view&id=1tFButrp773wAbN5PpTNswwJMRqbitixH',
                ],
                [
                    'number' => '400.3.5/2109/2026',
                    'subject' => 'Pelaksanaan Gugus Kendali Mutu Bulan Juni',
                    'date' => '2026-06-10',
                    'drive' => 'https://drive.google.com/open?id=1DUPxnbZHGxd5tZgxPbsdoD-qMHzc7t0i',
                ],
                [
                    'number' => '400.3.5/2030/2026',
                    'subject' => 'Permohonan Tenaga Medis  GSI Jenjang SMP Tahun 2026',
                    'date' => '2026-06-08',
                    'drive' => 'https://drive.google.com/open?id=1LD2g6ZaTGT-CMYrusEt88AcyIqrN9jUu',
                ],
                [
                    'number' => '800.1.11.1/2032/2026',
                    'subject' => 'Untuk melaksanakan kegiatan GSI',
                    'date' => '2026-06-08',
                    'drive' => 'https://drive.google.com/open?id=11TBOHcKNuhGJQdB9BsZNDvIynsVQm0F3',
                ],
                [
                    'number' => '800.1.11.1/2031/2026',
                    'subject' => 'Untuk Menjadi Juri dan Panitia lomba GSI Jenjang SMP Tahun 2026',
                    'date' => '2026-06-08',
                    'drive' => 'https://drive.google.com/open?id=1WrahVDBjP3E6fnonwDdc-ENWukclITZ3',
                ],
                [
                    'number' => '400.3.5/2033/2026',
                    'subject' => 'Surat Edaran GSI Jenjang SMP  Tahun 2026',
                    'date' => '2026-06-05',
                    'drive' => 'https://drive.google.com/open?id=1PPCzedX9gFQWOFCB6ZUnqVWGTp9wq6op',
                ],
                [
                    'number' => '400.3.5/1882/2026',
                    'subject' => 'Permohonan Personil Dukcapil',
                    'date' => '2026-05-26',
                    'drive' => 'https://drive.google.com/open?id=1oJaUN8DEFa8ZOG0AXuintEqL8VUGKaV5',
                ],
                [
                    'number' => '400.3.5/1881/2026',
                    'subject' => 'Permohonan Personil Pusdatin',
                    'date' => '2026-05-26',
                    'drive' => 'https://drive.google.com/open?id=15iXXjgrht8sWYU0y1_qngLGPRzbRcGhW',
                ],
                [
                    'number' => '400.3.5/1880/2026',
                    'subject' => 'Permohonan Personil Dinas Sosial Kabupaten Semarang',
                    'date' => '2026-05-26',
                    'drive' => 'https://drive.google.com/open?id=1iJCoWHQTIVjS6lUG_5-de_QRc28YQ62c',
                ],
                [
                    'number' => 'ND/PEMINJAMAN/2026',
                    'subject' => 'Permohonan Peminjaman Tempat',
                    'date' => '2026-05-19',
                    'drive' => 'https://drive.google.com/open?id=1WG4sQ3DCH44m2zBZc9yGrJxcHTuaQMce',
                ],
                [
                    'number' => '400.3/1538.A/2026',
                    'subject' => 'Undangan',
                    'date' => '2026-05-04',
                    'drive' => 'https://drive.google.com/open?id=1kp_7kgrQ3o3yiWll5mo3wYr7EeEHcYEv',
                ],
                [
                    'number' => 'ND/INTERNET/2026',
                    'subject' => 'Permohonan optimalisasi Internet',
                    'date' => '2026-05-20',
                    'drive' => 'https://drive.google.com/open?id=1yc6Xy3Mu8WdJzdSbFPRetSeonULwVEfj',
                ],
                [
                    'number' => '400.3/1502/2026',
                    'subject' => 'Surat Piagam FLS3N',
                    'date' => '2026-05-25',
                    'drive' => 'https://drive.google.com/file/d/1RMiAbPoD-2JqUysDE8J0sr-eKw2SrrpS/view?usp=sharing',
                ],
            ];

            foreach ($outgoingData as $item) {
                $year = (int) substr($item['date'], 0, 4);

                $letter = OutgoingLetter::create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $division->id,
                    'letter_year' => $year,
                    'letter_number' => $item['number'],
                    'letter_date' => $item['date'],
                    'disseminated_date' => $item['date'],
                    'subject' => $item['subject'],
                    'created_by_user_id' => $adminId,
                ]);

                $fileId = $this->extractDriveId($item['drive']);

                Attachment::create([
                    'uuid' => (string) Str::uuid(),
                    'division_id' => $division->id,
                    'attachable_type' => OutgoingLetter::class,
                    'attachable_id' => $letter->id,
                    'original_name' => Str::slug($item['number']).'.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => 0,
                    'is_missing' => true,
                    'migration_status' => 'migrated',
                    'legacy_drive_file_id' => $fileId,
                    'legacy_drive_url' => $item['drive'],
                    'created_by_user_id' => $adminId,
                ]);
            }
        });
    }

    private function extractDriveId(string $url): ?string
    {
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
