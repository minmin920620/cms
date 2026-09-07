<?php

use App\Models\Crime;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Crime::query()
            ->whereDoesntHave('statusHistories')
            ->select(['id', 'status', 'created_at', 'updated_at'])
            ->each(function (Crime $crime) {
                DB::table('crime_status_histories')->insert([
                    'crime_id' => $crime->id,
                    'changed_by' => null,
                    'old_status' => null,
                    'new_status' => $crime->status,
                    'remarks' => 'Existing crime incident record included in the crime mapping workflow history.',
                    'created_at' => $crime->updated_at ?? $crime->created_at ?? now(),
                    'updated_at' => $crime->updated_at ?? $crime->created_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('crime_status_histories')
            ->whereNull('changed_by')
            ->whereNull('old_status')
            ->where('remarks', 'Existing crime incident record included in the crime mapping workflow history.')
            ->delete();
    }
};
