<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyLetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = Division::all()->keyBy('code');
        $defaultUser = User::first();

        // 5 Dummy Surat Masuk (Incoming Letters)
        $incomingLetters = [
            [
                'division_code' => 'DIKDAS',
                'letter_number' => '0421/B1/GT.02.00/2026',
                'origin_agency' => 'Kementerian Pendidikan Dasar dan Menengah RI',
                'letter_date' => '2026-02-10',
                'received_date' => '2026-02-12',
                'subject' => 'Undangan Rapat Koordinasi Penjaminan Mutu & Asesmen Nasional Jenjang SD Tahun 2026',
            ],
            [
                'division_code' => 'DIKDAS',
                'letter_number' => '1152/B7.4/PP.01.07/2026',
                'origin_agency' => 'Balai Guru Penggerak (BGP) Provinsi Jawa Tengah',
                'letter_date' => '2026-02-18',
                'received_date' => '2026-02-20',
                'subject' => 'Permohonan Fasilitasi & Delegasi Peserta Lokakarya Program Guru Penggerak Angkatan 11',
            ],
            [
                'division_code' => 'PAUD',
                'letter_number' => '440/0892/DINKES/2026',
                'origin_agency' => 'Dinas Kesehatan Kabupaten Semarang',
                'letter_date' => '2026-02-22',
                'received_date' => '2026-02-23',
                'subject' => 'Koordinasi Skrining Tumbuh Kembang & Program Pemberian Makanan Tambahan (PMT) Anak Usia Dini',
            ],
            [
                'division_code' => 'KEBUDAYAAN',
                'letter_number' => '0314/F7.10/KB.02.01/2026',
                'origin_agency' => 'Balai Pelestarian Kebudayaan (BPK) Wilayah X',
                'letter_date' => '2026-02-24',
                'received_date' => '2026-02-25',
                'subject' => 'Usulan Registrasi & Verifikasi Bersama Objek Diduga Cagar Budaya (ODCB) di Kawasan Ambarawa',
            ],
            [
                'division_code' => 'PEMUDA_OLAHRAGA',
                'letter_number' => '088/KONI-KAB/SMG/II/2026',
                'origin_agency' => 'Komite Olahraga Nasional Indonesia (KONI) Kab. Semarang',
                'letter_date' => '2026-02-25',
                'received_date' => '2026-02-26',
                'subject' => 'Rekomendasi Penyelenggaraan Kejuaraan Antar Pelajar & Pemusatan Latihan Kontingen Popda 2026',
            ],
        ];

        foreach ($incomingLetters as $data) {
            $division = $divisions->get($data['division_code']) ?? $divisions->first();
            if (!$division) {
                continue;
            }

            $user = User::where('division_id', $division->id)->first() ?? $defaultUser;

            IncomingLetter::firstOrCreate(
                [
                    'division_id' => $division->id,
                    'letter_number' => $data['letter_number'],
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'origin_agency' => $data['origin_agency'],
                    'letter_date' => $data['letter_date'],
                    'received_date' => $data['received_date'],
                    'subject' => $data['subject'],
                    'created_by_user_id' => $user?->id,
                ]
            );
        }

        // 5 Dummy Surat Keluar (Outgoing Letters)
        $outgoingLetters = [
            [
                'division_code' => 'DIKDAS',
                'letter_year' => 2026,
                'letter_number' => '421.2/0145/DIKDAS/2026',
                'letter_date' => '2026-02-15',
                'disseminated_date' => '2026-02-16',
                'subject' => 'Surat Edaran Persiapan Teknis & Verifikasi Data Calon Peserta ANBK Jenjang SD Tahun 2026',
            ],
            [
                'division_code' => 'PAUD',
                'letter_year' => 2026,
                'letter_number' => '422/0092/PAUD/2026',
                'letter_date' => '2026-02-24',
                'disseminated_date' => '2026-02-24',
                'subject' => 'Undangan Bimbingan Teknis Standar Pelayanan Minimal (SPM) dan Manajemen Kelembagaan PAUD & PKBM',
            ],
            [
                'division_code' => 'KEBUDAYAAN',
                'letter_year' => 2026,
                'letter_number' => '430/0038/BUD/2026',
                'letter_date' => '2026-02-26',
                'disseminated_date' => '2026-02-26',
                'subject' => 'Surat Tugas Tim Peninjauan Lapangan Konservasi dan Inventarisasi Cagar Budaya Candi Ngempon',
            ],
            [
                'division_code' => 'PEMUDA_OLAHRAGA',
                'letter_year' => 2026,
                'letter_number' => '426/0071/PORA/2026',
                'letter_date' => '2026-02-27',
                'disseminated_date' => '2026-02-27',
                'subject' => 'Pemberitahuan Jadwal Seleksi dan Pemanggilan Atlet Kontingen POPDA Tingkat Karesidenan Semarang',
            ],
            [
                'division_code' => 'SEKRETARIAT',
                'letter_year' => 2026,
                'letter_number' => '005/0284/SEKR/2026',
                'letter_date' => '2026-02-27',
                'disseminated_date' => '2026-02-28',
                'subject' => 'Undangan Rapat Koordinasi Rekonsiliasi Aset Daerah dan Evaluasi Kinerja Triwulan I TA 2026',
            ],
        ];

        foreach ($outgoingLetters as $data) {
            $division = $divisions->get($data['division_code']) ?? $divisions->first();
            if (!$division) {
                continue;
            }

            $user = User::where('division_id', $division->id)->first() ?? $defaultUser;

            OutgoingLetter::firstOrCreate(
                [
                    'division_id' => $division->id,
                    'letter_year' => $data['letter_year'],
                    'letter_number' => $data['letter_number'],
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'letter_date' => $data['letter_date'],
                    'disseminated_date' => $data['disseminated_date'],
                    'subject' => $data['subject'],
                    'created_by_user_id' => $user?->id,
                ]
            );
        }
    }
}
