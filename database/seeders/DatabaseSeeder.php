<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CrimeTypeSeeder::class,
            BarangaySeeder::class,
            UserSeeder::class,
            SampleCrimeSeeder::class,
        ]);
    }
}
