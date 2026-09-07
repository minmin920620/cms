<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crime_incidents', function (Blueprint $table) {
            $table->index('status', 'crimes_status_idx');
            $table->index('date_occurred', 'crimes_date_occurred_idx');
            $table->index(['status', 'date_occurred'], 'crimes_status_date_idx');
            $table->index(['barangay_id', 'date_occurred'], 'crimes_barangay_date_idx');
            $table->index(['crime_type_id', 'date_occurred'], 'crimes_type_date_idx');
        });

        Schema::table('crime_types', function (Blueprint $table) {
            $table->unique('name', 'crime_types_name_unique');
            $table->index('is_active', 'crime_types_is_active_idx');
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->unique(['name', 'city'], 'barangays_name_city_unique');
            $table->index('is_active', 'barangays_is_active_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_idx');
            $table->index('is_active', 'users_is_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_idx');
            $table->dropIndex('users_is_active_idx');
        });

        Schema::table('barangays', function (Blueprint $table) {
            $table->dropUnique('barangays_name_city_unique');
            $table->dropIndex('barangays_is_active_idx');
        });

        Schema::table('crime_types', function (Blueprint $table) {
            $table->dropUnique('crime_types_name_unique');
            $table->dropIndex('crime_types_is_active_idx');
        });

        Schema::table('crime_incidents', function (Blueprint $table) {
            $table->dropIndex('crimes_status_idx');
            $table->dropIndex('crimes_date_occurred_idx');
            $table->dropIndex('crimes_status_date_idx');
            $table->dropIndex('crimes_barangay_date_idx');
            $table->dropIndex('crimes_type_date_idx');
        });
    }
};
