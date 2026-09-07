<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crime_incidents', function (Blueprint $table) {
            $table->text('investigation_findings')->nullable()->after('status_notes');
            $table->timestamp('findings_recorded_at')->nullable()->after('investigation_findings');
        });
    }

    public function down(): void
    {
        Schema::table('crime_incidents', function (Blueprint $table) {
            $table->dropColumn(['investigation_findings', 'findings_recorded_at']);
        });
    }
};
