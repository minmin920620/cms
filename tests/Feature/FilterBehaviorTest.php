<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Crime;
use App\Models\CrimeType;
use App\Models\OffenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_crime_incident_filters_apply_to_listing(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$theft, $robbery, $theftOffense, $robberyOffense, $zoneOne, $zoneTwo] = $this->seedFilterData();

        $included = $this->crime([
            'case_number' => 'CRM-FILTER-0001',
            'title' => 'Included theft',
            'crime_type_id' => $theft->id,
            'offense_type_id' => $theftOffense->id,
            'barangay_id' => $zoneOne->id,
            'status' => Crime::STATUS_FOR_REVIEW,
            'date_occurred' => '2026-08-10',
        ]);
        $this->crime([
            'case_number' => 'CRM-FILTER-0002',
            'title' => 'Excluded robbery',
            'crime_type_id' => $robbery->id,
            'offense_type_id' => $robberyOffense->id,
            'barangay_id' => $zoneTwo->id,
            'status' => Crime::STATUS_PENDING,
            'date_occurred' => '2026-08-11',
        ]);

        $this->actingAs($admin)
            ->get(route('crimes.index', [
                'crime_type_id' => $included->crime_type_id,
                'barangay_id' => $included->barangay_id,
                'status' => $included->status,
            ]))
            ->assertOk()
            ->assertSee('Brgy. Zone 1')
            ->assertSee('THEFT- RPC Art. 308')
            ->assertDontSee('ROBBERY- RPC Art. 293');
    }

    public function test_dashboard_shortcut_filters_apply_to_crime_listing(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$theft, $robbery, $theftOffense, $robberyOffense, $zoneOne, $zoneTwo] = $this->seedFilterData();
        $investigator = User::factory()->create(['role' => User::ROLE_INVESTIGATOR]);

        $this->crime([
            'case_number' => 'CRM-UNASSIGNED-0001',
            'crime_type_id' => $theft->id,
            'offense_type_id' => $theftOffense->id,
            'barangay_id' => $zoneOne->id,
            'assigned_officer' => null,
            'status' => Crime::STATUS_PENDING,
        ]);
        $this->crime([
            'case_number' => 'CRM-ASSIGNED-0001',
            'crime_type_id' => $robbery->id,
            'offense_type_id' => $robberyOffense->id,
            'barangay_id' => $zoneTwo->id,
            'assigned_officer' => $investigator->id,
            'status' => Crime::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('crimes.index', ['unassigned' => 1]))
            ->assertOk()
            ->assertSee('Brgy. Zone 1')
            ->assertSee('THEFT- RPC Art. 308')
            ->assertDontSee('ROBBERY- RPC Art. 293');
    }

    public function test_map_data_filters_apply_to_geojson(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$theft, $robbery, $theftOffense, $robberyOffense, $zoneOne, $zoneTwo] = $this->seedFilterData();

        $this->crime([
            'case_number' => 'CRM-MAP-0001',
            'title' => 'Mapped included',
            'crime_type_id' => $theft->id,
            'offense_type_id' => $theftOffense->id,
            'barangay_id' => $zoneOne->id,
            'status' => Crime::STATUS_PENDING,
            'date_occurred' => '2026-08-10',
            'latitude' => 6.4986500,
            'longitude' => 124.8491500,
        ]);
        $this->crime([
            'case_number' => 'CRM-MAP-0002',
            'title' => 'Mapped excluded',
            'crime_type_id' => $robbery->id,
            'offense_type_id' => $robberyOffense->id,
            'barangay_id' => $zoneTwo->id,
            'status' => Crime::STATUS_RESOLVED,
            'date_occurred' => '2026-08-11',
            'latitude' => 6.5000000,
            'longitude' => 124.8600000,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('map.data', [
                'crime_type_id' => $theft->id,
                'status' => Crime::STATUS_PENDING,
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->json();

        $this->assertCount(1, $response['features']);
        $this->assertSame('CRM-MAP-0001', $response['features'][0]['properties']['case_number']);
    }

    public function test_admin_report_filters_and_lgu_summary_date_filters_apply(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lgu = User::factory()->create(['role' => User::ROLE_LGU]);
        [$theft, $robbery, $theftOffense, $robberyOffense, $zoneOne, $zoneTwo] = $this->seedFilterData();

        $this->crime([
            'case_number' => 'CRM-REPORT-0001',
            'crime_type_id' => $theft->id,
            'offense_type_id' => $theftOffense->id,
            'barangay_id' => $zoneOne->id,
            'status' => Crime::STATUS_PENDING,
            'date_occurred' => '2026-08-10',
        ]);
        $this->crime([
            'case_number' => 'CRM-REPORT-0002',
            'crime_type_id' => $robbery->id,
            'offense_type_id' => $robberyOffense->id,
            'barangay_id' => $zoneTwo->id,
            'status' => Crime::STATUS_RESOLVED,
            'date_occurred' => '2026-07-10',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.preview', [
                'crime_type_id' => $theft->id,
                'barangay_id' => $zoneOne->id,
                'status' => Crime::STATUS_PENDING,
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('CRM-REPORT-0001')
            ->assertDontSee('CRM-REPORT-0002');

        $this->actingAs($lgu)
            ->get(route('analytics.summary-report', [
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertOk()
            ->assertSee('CRM-REPORT-0001')
            ->assertDontSee('CRM-REPORT-0002');
    }

    private function seedFilterData(): array
    {
        $theft = CrimeType::create([
            'name' => 'Theft',
            'description' => 'Theft incidents',
            'icon' => 'fas fa-shield',
            'color' => '#dc2626',
            'is_active' => true,
        ]);
        $robbery = CrimeType::create([
            'name' => 'Robbery',
            'description' => 'Robbery incidents',
            'icon' => 'fas fa-mask',
            'color' => '#2563eb',
            'is_active' => true,
        ]);

        $theftOffense = OffenseType::create(['name' => 'THEFT- RPC Art. 308', 'crime_type_id' => $theft->id, 'is_active' => true]);
        $robberyOffense = OffenseType::create(['name' => 'ROBBERY- RPC Art. 293', 'crime_type_id' => $robbery->id, 'is_active' => true]);

        $zoneOne = Barangay::create([
            'name' => 'Brgy. Zone 1',
            'city' => 'Koronadal City',
            'latitude' => 6.4986500,
            'longitude' => 124.8491500,
            'is_active' => true,
        ]);
        $zoneTwo = Barangay::create([
            'name' => 'Brgy. Zone 2',
            'city' => 'Koronadal City',
            'latitude' => 6.5000000,
            'longitude' => 124.8600000,
            'is_active' => true,
        ]);

        return [$theft, $robbery, $theftOffense, $robberyOffense, $zoneOne, $zoneTwo];
    }

    private function crime(array $overrides = []): Crime
    {
        $reporter = User::factory()->create(['role' => User::ROLE_POLICE_OFFICER]);

        return Crime::create(array_merge([
            'case_number' => 'CRM-FILTER-DEFAULT',
            'title' => 'Filter test incident',
            'crime_type_id' => CrimeType::first()->id,
            'offense_type_id' => OffenseType::first()->id,
            'barangay_id' => Barangay::first()->id,
            'date_reported' => '2026-08-10',
            'date_occurred' => '2026-08-10',
            'status' => Crime::STATUS_PENDING,
            'address' => 'Koronadal City',
            'reported_by' => $reporter->id,
        ], $overrides));
    }
}
