<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_only_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('crime-types.index'))->assertOk();
        $this->actingAs($admin)->get(route('offense-types.index'))->assertOk();
        $this->actingAs($admin)->get(route('audit-logs.index'))->assertOk();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('backups.index'))->assertOk();
    }

    public function test_non_admin_users_cannot_access_admin_only_pages(): void
    {
        $strictAdminOnlyRoutes = [
            'users.index',
            'crime-types.index',
            'offense-types.index',
            'audit-logs.index',
            'backups.index',
        ];

        foreach ([User::ROLE_POLICE_OFFICER, User::ROLE_INVESTIGATOR, User::ROLE_LGU] as $role) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($strictAdminOnlyRoutes as $routeName) {
                $this->actingAs($user)
                    ->get(route($routeName))
                    ->assertForbidden();
            }
        }

        foreach ([User::ROLE_POLICE_OFFICER, User::ROLE_INVESTIGATOR] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('reports.index'))
                ->assertForbidden();
        }

        $lgu = User::factory()->create(['role' => User::ROLE_LGU]);

        $this->actingAs($lgu)
            ->get(route('reports.index'))
            ->assertRedirect(route('analytics.index'));
    }

    public function test_lgu_can_access_analytics_but_not_operational_crime_pages(): void
    {
        $lgu = User::factory()->create(['role' => User::ROLE_LGU]);

        $this->actingAs($lgu)
            ->get(route('analytics.index'))
            ->assertOk();

        $this->actingAs($lgu)
            ->get(route('crimes.index'))
            ->assertForbidden();
    }
}
