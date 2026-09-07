<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'adminusercms2026@gmail.com'], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'phone' => '09170000001',
            'badge_number' => 'ADM-001',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'officercms2026@gmail.com'], [
            'name' => 'Police Officer Juan',
            'password' => Hash::make('password'),
            'role' => User::ROLE_POLICE_OFFICER,
            'phone' => '09170000002',
            'badge_number' => 'POL-001',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'investigatorcms2026@gmail.com'], [
            'name' => 'Investigator Maria',
            'password' => Hash::make('password'),
            'role' => User::ROLE_INVESTIGATOR,
            'phone' => '09170000003',
            'badge_number' => 'INV-001',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'lgucms2026@gmail.com'], [
            'name' => 'LGU Viewer',
            'password' => Hash::make('password'),
            'role' => User::ROLE_LGU,
            'phone' => '09170000004',
            'badge_number' => 'LGU-001',
            'is_active' => true,
        ]);
    }
}

