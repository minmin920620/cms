<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\OffenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_lgu_can_generate_analytics_summary_report(): void
    {
        $lgu = User::factory()->create(['role' => User::ROLE_LGU]);
        $this->crime();

        $response = $this
            ->actingAs($lgu)
            ->get(route('analytics.summary-report'));

        $response
            ->assertOk()
            ->assertSee('Crime Analytics Summary Report')
            ->assertSee('Total Incidents')
            ->assertSee('Incidents by Barangay')
            ->assertDontSee('Test incident');
    }

    public function test_lgu_is_redirected_from_detailed_reports_to_analytics(): void
    {
        $lgu = User::factory()->create(['role' => User::ROLE_LGU]);

        $this
            ->actingAs($lgu)
            ->get(route('reports.index'))
            ->assertRedirect(route('analytics.index'));
    }

    private function crime(): Crime
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

        return Crime::create([
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
        ]);
    }
}
