<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\OffenseType;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemFeatureCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_police_officer_can_plot_incident_from_map(): void
    {
        $policeOfficer = User::factory()->create(['role' => User::ROLE_POLICE_OFFICER]);
        [$crimeType, $offenseType, $barangay] = $this->referenceData();

        $response = $this->actingAs($policeOfficer)->postJson(route('map.plot-incident'), [
            'title' => 'Map plotted incident',
            'crime_type_id' => $crimeType->id,
            'offense_type_id' => $offenseType->id,
            'barangay_id' => $barangay->id,
            'date_occurred' => now()->toDateString(),
            'latitude' => 6.4986500,
            'longitude' => 124.8491500,
            'address' => 'Brgy. Zone 1, Koronadal City',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('crime.title', 'Map plotted incident');

        $this->assertDatabaseHas('crime_incidents', [
            'title' => 'Map plotted incident',
            'reported_by' => $policeOfficer->id,
            'status' => Crime::STATUS_PENDING,
        ]);
    }

    public function test_lgu_cannot_plot_incident_from_map(): void
    {
        $lgu = User::factory()->create(['role' => User::ROLE_LGU]);
        [$crimeType, $offenseType, $barangay] = $this->referenceData();

        $this->actingAs($lgu)->postJson(route('map.plot-incident'), [
            'title' => 'Unauthorized map plot',
            'crime_type_id' => $crimeType->id,
            'offense_type_id' => $offenseType->id,
            'barangay_id' => $barangay->id,
            'date_occurred' => now()->toDateString(),
            'latitude' => 6.4986500,
            'longitude' => 124.8491500,
        ])->assertForbidden();
    }

    public function test_assignment_notification_can_be_marked_as_read_by_owner_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $investigator = User::factory()->create(['role' => User::ROLE_INVESTIGATOR]);
        $otherInvestigator = User::factory()->create(['role' => User::ROLE_INVESTIGATOR]);
        $crime = $this->crime();

        $this->actingAs($admin)
            ->patch(route('crimes.update', $crime), $this->crimePayload($crime, [
                'assigned_officer' => $investigator->id,
            ]))
            ->assertRedirect(route('crimes.show', $crime));

        $notification = SystemNotification::where('user_id', $investigator->id)
            ->where('type', 'case_assigned')
            ->firstOrFail();

        $this->actingAs($otherInvestigator)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->actingAs($investigator)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_can_preview_report_and_download_pdf_export(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->crime(['title' => 'Report visible incident']);

        $this->actingAs($admin)
            ->get(route('reports.preview'))
            ->assertOk()
            ->assertSee('Report visible incident');

        $this->actingAs($admin)
            ->get(route('reports.export-pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_import_crimes_from_csv(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$crimeType, $offenseType, $barangay] = $this->referenceData();
        $csv = implode("\n", [
            'case_number,title,crime_type,offense_type,barangay,date_reported,date_occurred,address,status,latitude,longitude',
            'CRM-IMPORT-0001,Imported incident,' . $crimeType->name . ',' . $offenseType->name . ',' . $barangay->name . ',2026-08-01,2026-08-01,Imported address,pending,6.49865,124.84915',
        ]);

        $response = $this->actingAs($admin)->post(route('crimes.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('crimes.csv', $csv),
        ]);

        $response
            ->assertRedirect(route('crimes.index'))
            ->assertSessionHas('success', '1 crime record(s) imported.');

        $this->assertDatabaseHas('crime_incidents', [
            'case_number' => 'CRM-IMPORT-0001',
            'title' => 'Imported incident',
            'reported_by' => $admin->id,
        ]);
    }

    public function test_admin_can_download_database_backup(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->crime(['case_number' => 'CRM-BACKUP-0001']);

        $response = $this->actingAs($admin)->get(route('backups.download'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/sql')
            ->assertSee('Crime Mapping System backup')
            ->assertSee('CRM-BACKUP-0001');
    }

    private function crime(array $overrides = []): Crime
    {
        [$crimeType, $offenseType, $barangay] = $this->referenceData();
        $reporter = User::factory()->create(['role' => User::ROLE_POLICE_OFFICER]);

        return Crime::create(array_merge([
            'case_number' => 'CRM-TEST-' . str_pad((string) Crime::withTrashed()->count() + 1, 4, '0', STR_PAD_LEFT),
            'title' => 'Test incident',
            'crime_type_id' => $crimeType->id,
            'offense_type_id' => $offenseType->id,
            'barangay_id' => $barangay->id,
            'date_reported' => now()->toDateString(),
            'date_occurred' => now()->toDateString(),
            'status' => Crime::STATUS_PENDING,
            'address' => 'Brgy. Zone 1, Koronadal City',
            'reported_by' => $reporter->id,
        ], $overrides));
    }

    private function crimePayload(Crime $crime, array $overrides = []): array
    {
        return array_merge([
            'title' => $crime->title,
            'crime_type_id' => $crime->crime_type_id,
            'barangay_id' => $crime->barangay_id,
            'date_reported' => $crime->date_reported->toDateString(),
            'date_occurred' => $crime->date_occurred->toDateString(),
            'offense_type_id' => $crime->offense_type_id,
            'address' => $crime->address,
            'status' => $crime->status,
            'evidence_category' => 'evidence',
        ], $overrides);
    }

    private function referenceData(): array
    {
        $crimeType = CrimeType::firstOrCreate(
            ['name' => 'Theft'],
            [
                'description' => 'Theft incidents',
                'icon' => 'fas fa-shield',
                'color' => '#dc2626',
                'is_active' => true,
            ]
        );

        $offenseType = OffenseType::firstOrCreate(
            ['name' => 'THEFT- RPC Art. 308'],
            [
                'crime_type_id' => $crimeType->id,
                'description' => 'Theft offense',
                'is_active' => true,
            ]
        );

        $barangay = Barangay::firstOrCreate(
            ['name' => 'Brgy. Zone 1'],
            [
                'city' => 'Koronadal City',
                'latitude' => 6.4986500,
                'longitude' => 124.8491500,
                'is_active' => true,
            ]
        );

        return [$crimeType, $offenseType, $barangay];
    }
}
