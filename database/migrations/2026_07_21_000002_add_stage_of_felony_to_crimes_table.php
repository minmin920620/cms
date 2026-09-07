<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('crime_incidents', 'stage_of_felony')) {
            Schema::table('crime_incidents', function (Blueprint $table) {
                $table->string('stage_of_felony')->nullable()->after('time_occurred');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('crime_incidents', 'stage_of_felony')) {
            Schema::table('crime_incidents', function (Blueprint $table) {
                $table->dropColumn('stage_of_felony');
            });
        }
    }
};
