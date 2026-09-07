<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\OffenseType;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleCrimeSeeder extends Seeder
{
    public function run(): void
    {
        $reporter = User::where('email', 'officercms2026@gmail.com')->first() ?? User::first();
        $investigator = User::where('email', 'investigatorcms2026@gmail.com')->first();

        if (!$reporter) {
            return;
        }

        $samplePlan = [
            'Brgy. Zone 1' => 10,
            'Brgy. Morales' => 4,
            'Brgy. San Jose' => 3,
            'Brgy. Rotonda' => 2,
            'Brgy. Assumption' => 1,
        ];

        $crimeTypes = CrimeType::crimeTypes()->pluck('id', 'name');
        $offenses = OffenseType::offenseChoices();
        $typeNames = $crimeTypes->keys()->values();
        $statuses = [
            Crime::STATUS_PENDING,
            Crime::STATUS_CIRAS_RECORDING,
            Crime::STATUS_FOR_REVIEW,
            Crime::STATUS_FOR_SIGNATURE,
            Crime::STATUS_BLOTTER_ENTERED,
            Crime::STATUS_UCPER_COMPILED,
            Crime::STATUS_RESOLVED,
            Crime::STATUS_CLOSED,
        ];
        $incidentTitles = [
            'Theft' => 'Reported theft incident',
            'Robbery' => 'Robbery complaint',
            'Assault' => 'Assault report',
            'Homicide' => 'Homicide report',
            'Drug-Related' => 'Drug-related activity report',
            'Traffic Violation' => 'Traffic violation incident',
            'Vandalism' => 'Vandalism complaint',
        ];

        $sampleIndex = 1;

        foreach ($samplePlan as $barangayName => $incidentCount) {
            $barangay = Barangay::where('name', $barangayName)
                ->where('city', 'Koronadal City')
                ->first();

            if (!$barangay) {
                continue;
            }

            for ($i = 1; $i <= $incidentCount; $i++) {
                $typeName = $typeNames[($sampleIndex - 1) % $typeNames->count()];
                $status = $statuses[($sampleIndex - 1) % count($statuses)];
                $latOffset = (($i % 5) - 2) * 0.00035;
                $lngOffset = ((int) floor(($i - 1) / 5) - 1) * 0.00035;

                Crime::updateOrCreate(
                    ['case_number' => 'SAMPLE-' . str_pad((string) $sampleIndex, 4, '0', STR_PAD_LEFT)],
                    [
                        'title' => $incidentTitles[$typeName] ?? 'Sample crime incident',
                        'crime_type_id' => $crimeTypes[$typeName],
                        'offense_type_id' => $offenses->isNotEmpty()
                            ? $offenses[($sampleIndex - 1) % $offenses->count()]->id
                            : null,
                        'barangay_id' => $barangay->id,
                        'date_occurred' => now()->subDays($sampleIndex - 1)->toDateString(),
                        'time_occurred' => now()->subHours($i)->format('H:i:s'),
                        'status' => $status,
                        'status_notes' => in_array($status, Crime::COMPLETED_STATUSES, true) ? 'Sample case completed.' : null,
                        'latitude' => (float) $barangay->latitude + $latOffset,
                        'longitude' => (float) $barangay->longitude + $lngOffset,
                        'address' => $barangay->name . ', Koronadal City',
                        'reported_by' => $reporter->id,
                        'assigned_officer' => $investigator?->id,
                        'resolved_at' => in_array($status, Crime::COMPLETED_STATUSES, true) ? now()->subDays($sampleIndex - 1) : null,
                    ]
                );

                $sampleIndex++;
            }
        }
    }
}
