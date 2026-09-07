<?php

namespace Database\Seeders;

use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\OffenseType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CrimeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Theft', 'description' => 'Unlawful taking of property', 'icon' => 'fas fa-hand-holding', 'color' => '#ef4444'],
            ['name' => 'Robbery', 'description' => 'Taking property by force or threat', 'icon' => 'fas fa-mask', 'color' => '#f97316'],
            ['name' => 'Assault', 'description' => 'Physical attack or threat of harm', 'icon' => 'fas fa-fist-raised', 'color' => '#eab308'],
            ['name' => 'Homicide', 'description' => 'Unlawful killing of a person', 'icon' => 'fas fa-skull', 'color' => '#dc2626'],
            ['name' => 'Murder', 'description' => 'Unlawful killing with qualifying circumstances', 'icon' => 'fas fa-skull-crossbones', 'color' => '#991b1b'],
            ['name' => 'Parricide', 'description' => 'Killing of a close family member', 'icon' => 'fas fa-user-slash', 'color' => '#7f1d1d'],
            ['name' => 'Drug-Related', 'description' => 'Offenses involving illegal drugs', 'icon' => 'fas fa-pills', 'color' => '#8b5cf6'],
            ['name' => 'Cyber Crime', 'description' => 'Crimes committed via digital means', 'icon' => 'fas fa-laptop', 'color' => '#3b82f6'],
            ['name' => 'Fraud', 'description' => 'Deception for financial gain', 'icon' => 'fas fa-handshake', 'color' => '#06b6d4'],
            ['name' => 'Physical Injury', 'description' => 'Inflicting physical harm', 'icon' => 'fas fa-bolt', 'color' => '#f43f5e'],
            ['name' => 'Sexual Offense', 'description' => 'Sexual crimes and misconduct', 'icon' => 'fas fa-gavel', 'color' => '#ec4899'],
            ['name' => 'Traffic Violation', 'description' => 'Violations of traffic laws', 'icon' => 'fas fa-car', 'color' => '#14b8a6'],
            ['name' => 'Carnapping', 'description' => 'Unlawful taking of a motor vehicle', 'icon' => 'fas fa-car-side', 'color' => '#0f766e'],
            ['name' => 'Vandalism', 'description' => 'Deliberate destruction of property', 'icon' => 'fas fa-paint-roller', 'color' => '#7c3aed'],
            ['name' => 'Other', 'description' => 'Other types of crimes', 'icon' => 'fas fa-question-circle', 'color' => '#6b7280'],
        ];

        $offenseToCrimeType = [
            'THEFT- RPC Art. 308' => 'Theft',
            'ANTI-RAPE LAW OF 1997 - RA 8353' => 'Sexual Offense',
            'NEW ANTI-CARNAPPING ACT OF 2016 - MC-RA 10883(repealed RA 6539)' => 'Carnapping',
            'QUALIFIED THEFT - RPC Art. 310 as amended by BP Blg 71' => 'Theft',
            'PARRICIDE -RPC Art. 246' => 'Parricide',
            'LESS SERIOUS PHYSICAL INJURIES - RPC Art. 265' => 'Physical Injury',
            'MURDER -RPC Art. 248' => 'Murder',
            'ROBBERY - RPC Art. 293' => 'Robbery',
            'HOMICIDE - RPC Art. 249' => 'Homicide',
            'ASSAULT' => 'Assault',
            'CYBERCRIME OFFENSE' => 'Cyber Crime',
            'DRUG-RELATED OFFENSE' => 'Drug-Related',
            'FRAUD' => 'Fraud',
            'MALICIOUS MISCHIEF / VANDALISM' => 'Vandalism',
            'TRAFFIC VIOLATION' => 'Traffic Violation',
            'OTHER OFFENSE' => 'Other',
        ];

        foreach ($types as $type) {
            CrimeType::updateOrCreate(
                ['name' => $type['name']],
                $type + ['is_active' => true]
            );
        }

        foreach ($offenseToCrimeType as $offense => $crimeTypeName) {
            OffenseType::updateOrCreate(
                ['name' => $offense],
                [
                    'crime_type_id' => CrimeType::where('name', $crimeTypeName)->value('id'),
                    'is_active' => true,
                ]
            );

            $offenseType = CrimeType::where('name', $offense)->first();
            $crimeType = CrimeType::where('name', $crimeTypeName)->first();

            if (!$offenseType || !$crimeType) {
                continue;
            }

            $offenseTypeId = OffenseType::where('name', $offense)->value('id');

            if (Schema::hasColumn('crime_incidents', 'offense')) {
                Crime::where('crime_type_id', $offenseType->id)
                    ->where(function ($query) {
                        $query->whereNull('offense')->orWhere('offense', '');
                    })
                    ->update(['offense' => $offense]);
            }

            if (Schema::hasColumn('crime_incidents', 'offense_type_id')) {
                Crime::where('crime_type_id', $offenseType->id)
                    ->whereNull('offense_type_id')
                    ->update(['offense_type_id' => $offenseTypeId]);
            }

            Crime::where('crime_type_id', $offenseType->id)
                ->update(['crime_type_id' => $crimeType->id]);
        }

        CrimeType::whereIn('name', OffenseType::DEFAULT_OFFENSES)->update(['is_active' => false]);
    }
}

