<?php

namespace Database\Seeders;

use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class WorkUnitSeeder extends Seeder
{
    public function run(): void
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
}
