<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE crime_incidents MODIFY status ENUM('pending','under_investigation','ciras_recording','for_review','for_correction','data_stored','irf_printed','for_signature','blotter_entered','ucper_compiled','resolved','closed') NOT NULL DEFAULT 'pending'");
        DB::table('crime_incidents')
            ->where('status', 'under_investigation')
            ->update(['status' => 'ciras_recording']);
        DB::statement("ALTER TABLE crime_incidents MODIFY status ENUM('pending','ciras_recording','for_review','for_correction','data_stored','irf_printed','for_signature','blotter_entered','ucper_compiled','resolved','closed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::table('crime_incidents')
            ->whereIn('status', ['ciras_recording', 'for_review', 'for_correction', 'data_stored', 'irf_printed', 'for_signature', 'blotter_entered', 'ucper_compiled'])
            ->update(['status' => 'under_investigation']);
        DB::statement("ALTER TABLE crime_incidents MODIFY status ENUM('pending','under_investigation','resolved','closed') NOT NULL DEFAULT 'pending'");
    }
};
