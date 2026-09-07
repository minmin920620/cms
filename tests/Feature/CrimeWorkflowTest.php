<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\Evidence;
use App\Models\OffenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrimeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_findings_and_upload_evidence(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $crime = $this->crime();

        $response = $this
            ->actingAs($admin)
            ->patch(route('crimes.update', $crime), $this->payload([
                'investigation_findings' => 'Admin finding note.',
                'evidence_files' => [
                    UploadedFile::fake()->image('scene.jpg'),
                ],
            ], $crime));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('crimes.show', $crime));

        $crime->refresh();

        $this->assertSame('Admin finding note.', $crime->investigation_findings);
        $this->assertNotNull($crime->findings_recorded_at);
        $this->assertDatabaseHas('evidence', [
            'crime_id' => $crime->id,
            'file_name' => 'scene.jpg',
            'uploaded_by' => $admin->id,
        ]);
    }

    public function test_assigned_investigator_can_update_findings_and_upload_evidence(): void
    {
        Storage::fake('local');

        $investigator = User::factory()->create(['role' => User::ROLE_INVESTIGATOR]);
        $crime = $this->crime(['assigned_officer' => $investigator->id]);

        $response = $this
            ->actingAs($investigator)
            ->patch(route('crimes.update', $crime), [
                'status' => Crime::STATUS_CIRAS_RECORDING,
                'status_notes' => 'Started investigation.',
                'investigation_findings' => 'Investigator finding note.',
                'evidence_category' => 'evidence',
                'evidence_files' => [
                    UploadedFile::fake()->create('statement.pdf', 64, 'application/pdf'),
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('crimes.show', $crime));

        $crime->refresh();

        $this->assertSame('Investigator finding note.', $crime->investigation_findings);
        $this->assertSame(Crime::STATUS_CIRAS_RECORDING, $crime->status);
        $this->assertDatabaseHas('evidence', [
            'crime_id' => $crime->id,
            'file_name' => 'statement.pdf',
            'uploaded_by' => $investigator->id,
        ]);
    }

    private function crime(array $overrides = []): Crime
    {
        $crimeType = CrimeType::create([
            'name' => 'Theft',
            'description' => 'Theft incidents',
            'icon' => 'fas fa-shield',
            'color' => '#dc2626',
            'is_active' => true,
        ]);

        $offenseType = OffenseType::create([
            'name' => 'THEFT- RPC Art. 308',
            'crime_type_id' => $crimeType->id,
            'description' => 'Theft offense',
            'is_active' => true,
        ]);

        $barangay = Barangay::create([
            'name' => 'Brgy. Zone 1',
            'city' => 'Koronadal City',
            'latitude' => 6.4986500,
            'longitude' => 124.8491500,
            'is_active' => true,
        ]);

        $reporter = User::factory()->create(['role' => User::ROLE_POLICE_OFFICER]);

        return Crime::create(array_merge([
            'case_number' => 'CRM-TEST-0001',
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

    private function payload(array $overrides, Crime $crime): array
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
}
