<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            ['name' => 'Brgy. Assumption',                'city' => 'Koronadal City', 'latitude' => 6.4886, 'longitude' => 124.8452],
            ['name' => 'Brgy. Avanceña',                   'city' => 'Koronadal City', 'latitude' => 6.4912, 'longitude' => 124.8398],
            ['name' => 'Brgy. Cacub',                      'city' => 'Koronadal City', 'latitude' => 6.5024, 'longitude' => 124.8512],
            ['name' => 'Brgy. Caloocan',                   'city' => 'Koronadal City', 'latitude' => 6.4945, 'longitude' => 124.8587],
            ['name' => 'Brgy. Carpenter Hill',             'city' => 'Koronadal City', 'latitude' => 6.4789, 'longitude' => 124.8401],
            ['name' => 'Brgy. Concepcion',                 'city' => 'Koronadal City', 'latitude' => 6.4843, 'longitude' => 124.8525],
            ['name' => 'Brgy. Esperanza',                  'city' => 'Koronadal City', 'latitude' => 6.5098, 'longitude' => 124.8389],
            ['name' => 'Brgy. General Paulino Santos',     'city' => 'Koronadal City', 'latitude' => 6.4965, 'longitude' => 124.8267],
            ['name' => 'Brgy. Mabini',                     'city' => 'Koronadal City', 'latitude' => 6.5042, 'longitude' => 124.8443],
            ['name' => 'Brgy. Magsaysay',                  'city' => 'Koronadal City', 'latitude' => 6.5001, 'longitude' => 124.8305],
            ['name' => 'Brgy. Mambucal',                   'city' => 'Koronadal City', 'latitude' => 6.5105, 'longitude' => 124.8559],
            ['name' => 'Brgy. Morales',                    'city' => 'Koronadal City', 'latitude' => 6.4871, 'longitude' => 124.8376],
            ['name' => 'Brgy. Namnama',                    'city' => 'Koronadal City', 'latitude' => 6.5068, 'longitude' => 124.8341],
            ['name' => 'Brgy. New Pangasinan',             'city' => 'Koronadal City', 'latitude' => 6.4933, 'longitude' => 124.8479],
            ['name' => 'Brgy. Paraiso',                    'city' => 'Koronadal City', 'latitude' => 6.4805, 'longitude' => 124.8482],
            ['name' => 'Brgy. Rotonda',                    'city' => 'Koronadal City', 'latitude' => 6.4978, 'longitude' => 124.8415],
            ['name' => 'Brgy. San Isidro',                 'city' => 'Koronadal City', 'latitude' => 6.5082, 'longitude' => 124.8487],
            ['name' => 'Brgy. San Jose',                   'city' => 'Koronadal City', 'latitude' => 6.4899, 'longitude' => 124.8543],
            ['name' => 'Brgy. San Roque',                  'city' => 'Koronadal City', 'latitude' => 6.4956, 'longitude' => 124.8354],
            ['name' => 'Brgy. Saravia',                    'city' => 'Koronadal City', 'latitude' => 6.4817, 'longitude' => 124.8428],
            ['name' => 'Brgy. Sta. Cruz',                  'city' => 'Koronadal City', 'latitude' => 6.5035, 'longitude' => 124.8602],
            ['name' => 'Brgy. Sto. Niño',                  'city' => 'Koronadal City', 'latitude' => 6.5074, 'longitude' => 124.8427],
            ['name' => 'Brgy. Topland',                    'city' => 'Koronadal City', 'latitude' => 6.4762, 'longitude' => 124.8456],
            ['name' => 'Brgy. Zone 1',                     'city' => 'Koronadal City', 'latitude' => 6.4990, 'longitude' => 124.8495],
            ['name' => 'Brgy. Zone 2',                     'city' => 'Koronadal City', 'latitude' => 6.4982, 'longitude' => 124.8510],
            ['name' => 'Brgy. Zone 3',                     'city' => 'Koronadal City', 'latitude' => 6.4974, 'longitude' => 124.8525],
            ['name' => 'Brgy. Zone 4',                     'city' => 'Koronadal City', 'latitude' => 6.4966, 'longitude' => 124.8538],
        ];

        foreach ($barangays as $barangay) {
            Barangay::updateOrCreate(
                ['name' => $barangay['name']],
                $barangay
            );
        }
    }
}

