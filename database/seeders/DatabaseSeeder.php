<?php

namespace Database\Seeders;

use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $divisions = $this->seedDivisions();
        $this->seedUsers($divisions);
        $this->seedSchools();
        $this->seedWorkUnits();
        $this->seedCategories($divisions);
        $this->seedSettings();
    }

    /**
     * @return array<string, Division>
     */
    private function seedDivisions(): array
    {
        $data = [
            'DIKDAS' => 'Bidang Pembinaan SD',
            'PAUD' => 'Bidang PAUD dan PNF',
            'KEBUDAYAAN' => 'Bidang Kebudayaan',
            'PEMUDA_OLAHRAGA' => 'Bidang Kepemudaan dan Olahraga',
            'SEKRETARIAT' => 'Sekretariat Dinas',
        ];

        $divisions = [];
        foreach ($data as $code => $name) {
            $divisions[$code] = Division::query()->updateOrCreate(
                ['code' => $code],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'status' => 'active',
                ]
            );
        }

        return $divisions;
    }

    /**
     * @param  array<string, Division>  $divisions
     */
    private function seedUsers(array $divisions): void
    {
        $accounts = [
            [
                'username' => 'superadmin',
                'name' => 'Super Admin SISURAT',
                'email' => 'superadmin@sisurat.test',
                'role' => 'super_admin',
                'division_id' => null,
            ],
            [
                'username' => 'admin_dikdas',
                'name' => 'Admin DIKDAS',
                'email' => 'admin.dikdas@sisurat.test',
                'role' => 'division_admin',
                'division_id' => $divisions['DIKDAS']->id,
            ],
            [
                'username' => 'op_dikdas01',
                'name' => 'Operator DIKDAS',
                'email' => 'operator.dikdas@sisurat.test',
                'role' => 'operator',
                'division_id' => $divisions['DIKDAS']->id,
            ],
            [
                'username' => 'admin_paud',
                'name' => 'Admin PAUD',
                'email' => 'admin.paud@sisurat.test',
                'role' => 'division_admin',
                'division_id' => $divisions['PAUD']->id,
            ],
            [
                'username' => 'op_paud01',
                'name' => 'Operator PAUD',
                'email' => 'operator.paud@sisurat.test',
                'role' => 'operator',
                'division_id' => $divisions['PAUD']->id,
            ],
            [
                'username' => 'admin_budaya',
                'name' => 'Admin Kebudayaan',
                'email' => 'admin.budaya@sisurat.test',
                'role' => 'division_admin',
                'division_id' => $divisions['KEBUDAYAAN']->id,
            ],
            [
                'username' => 'op_budaya01',
                'name' => 'Operator Kebudayaan',
                'email' => 'operator.budaya@sisurat.test',
                'role' => 'operator',
                'division_id' => $divisions['KEBUDAYAAN']->id,
            ],
            [
                'username' => 'admin_pora',
                'name' => 'Admin Pemuda & Olahraga',
                'email' => 'admin.pora@sisurat.test',
                'role' => 'division_admin',
                'division_id' => $divisions['PEMUDA_OLAHRAGA']->id,
            ],
            [
                'username' => 'op_pora01',
                'name' => 'Operator Pemuda & Olahraga',
                'email' => 'operator.pora@sisurat.test',
                'role' => 'operator',
                'division_id' => $divisions['PEMUDA_OLAHRAGA']->id,
            ],
            [
                'username' => 'admin_sekretariat',
                'name' => 'Admin Sekretariat',
                'email' => 'admin.sekretariat@sisurat.test',
                'role' => 'division_admin',
                'division_id' => $divisions['SEKRETARIAT']->id,
            ],
            [
                'username' => 'op_sekretariat01',
                'name' => 'Operator Sekretariat',
                'email' => 'operator.sekretariat@sisurat.test',
                'role' => 'operator',
                'division_id' => $divisions['SEKRETARIAT']->id,
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['username' => $account['username']],
                array_merge($account, [
                    'uuid' => (string) Str::uuid(),
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'must_reset_password' => false,
                ])
            );
        }
    }

    private function seedSchools(): void
    {
        $schools = [
            ['npsn' => '20320340', 'name' => 'SMP Negeri 1 Ungaran'],
            ['npsn' => '20320341', 'name' => 'SMP Negeri 2 Ungaran'],
            ['npsn' => '20320342', 'name' => 'SMP Negeri 3 Ungaran'],
            ['npsn' => '20320343', 'name' => 'SMP Negeri 1 Ambarawa'],
            ['npsn' => '20320678', 'name' => 'SD Negeri Bandungan 01'],
            ['npsn' => '20320700', 'name' => 'SD Negeri Ungaran 01'],
            ['npsn' => '20320701', 'name' => 'SD Negeri Ungaran 02'],
            ['npsn' => '20320755', 'name' => 'SD Negeri Bergas Lor 01'],
            ['npsn' => '20320810', 'name' => 'SD Negeri Ambarawa 01'],
            ['npsn' => '20320850', 'name' => 'SD Negeri Bawen 01'],
        ];

        foreach ($schools as $school) {
            School::query()->updateOrCreate(
                ['npsn' => $school['npsn']],
                ['name' => $school['name'], 'is_active' => true]
            );
        }
    }

    private function seedWorkUnits(): void
    {
        $units = [
            ['name' => 'Dinas Pendidikan, Kebudayaan, Kepemudaan dan Olahraga', 'code' => 'DISDIKBUDPORA'],
            ['name' => 'Sekretariat Disdikbudpora', 'code' => 'SEKRETARIAT'],
            ['name' => 'Korwilcam Bidang Pendidikan Ungaran Barat', 'code' => 'KORWIL-UNGBAR'],
            ['name' => 'Korwilcam Bidang Pendidikan Bergas', 'code' => 'KORWIL-BERGAS'],
            ['name' => 'Korwilcam Bidang Pendidikan Ambarawa', 'code' => 'KORWIL-AMBARAWA'],
            ['name' => 'Korwilcam Bidang Pendidikan Bawen', 'code' => 'KORWIL-BAWEN'],
            ['name' => 'Korwilcam Bidang Pendidikan Tengaran', 'code' => 'KORWIL-TENGARAN'],
            ['name' => 'Kantor Kementerian Agama Kab. Semarang', 'code' => 'KEMENAG'],
            ['name' => 'Cabang Dinas Pendidikan Wilayah I', 'code' => 'CABDIN-1'],
        ];

        foreach ($units as $unit) {
            WorkUnit::query()->updateOrCreate(
                ['name' => $unit['name']],
                ['code' => $unit['code'], 'is_active' => true]
            );
        }
    }

    /**
     * @param  array<string, Division>  $divisions
     */
    private function seedCategories(array $divisions): void
    {
        $competitionCategories = [
            'DIKDAS' => [
                'FLS2N (Festival & Lomba Seni Siswa Nasional)',
                'O2SN (Olimpiade Olahraga Siswa Nasional)',
                'OSN (Olimpiade Sains Nasional)',
                'Gala Siswa Indonesia (GSI)',
            ],
            'PAUD' => [
                'Lomba Kreativitas Guru & Anak PAUD',
                'Apresiasi Bunda PAUD',
            ],
            'KEBUDAYAAN' => [
                'Festival Seni Tradisional Daerah',
                'Lomba Cerdas Cermat Sejarah & Museum',
            ],
            'PEMUDA_OLAHRAGA' => [
                'Pekan Olahraga Pelajar Daerah (POPDA)',
                'Seleksi Pemuda Pelopor',
            ],
            'SEKRETARIAT' => [
                'Penghargaan Pegawai Teladan',
            ],
        ];

        foreach ($competitionCategories as $divCode => $categories) {
            $division = $divisions[$divCode] ?? null;
            if (! $division) {
                continue;
            }
            foreach ($categories as $catName) {
                CompetitionCategory::query()->firstOrCreate(
                    ['division_id' => $division->id, 'name' => $catName],
                    ['is_active' => true]
                );
            }
        }

        $pickupCategories = [
            'Diambil Langsung oleh Pihak Sekolah',
            'Diambil oleh Korwilcam Bidang Pendidikan',
            'Diambil oleh Orang Tua / Wali Siswa',
            'Diserahkan pada Upacara / Acara Resmi',
        ];

        foreach ($divisions as $division) {
            foreach ($pickupCategories as $pickupName) {
                PickupCategory::query()->firstOrCreate(
                    ['division_id' => $division->id, 'name' => $pickupName],
                    ['is_active' => true]
                );
            }
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            'app_name' => 'SISURAT Disdikbudpora Kab. Semarang',
            'district_name' => 'Pemerintah Kabupaten Semarang',
            'agency_name' => 'Dinas Pendidikan, Kebudayaan, Kepemudaan, dan Olahraga',
            'office_address' => 'Jl. Gatot Subroto No. 24, Ungaran, Kabupaten Semarang',
            'contact_email' => 'disdikbudpora@semarangkab.go.id',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
