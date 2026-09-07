<?php

use App\Models\OffenseType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('crime_incidents', 'offense_type_id')) {
            $afterColumn = Schema::hasColumn('crime_incidents', 'offense') ? 'offense' : 'stage_of_felony';

            Schema::table('crime_incidents', function (Blueprint $table) use ($afterColumn) {
                $table->foreignId('offense_type_id')
                    ->nullable()
                    ->after($afterColumn)
                    ->constrained('offense_types')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('crime_incidents', 'offense')) {
            DB::table('crime_incidents')
                ->whereNotNull('offense')
                ->where('offense', '<>', '')
                ->orderBy('id')
                ->select(['id', 'offense'])
                ->chunkById(100, function ($crimes) {
                    foreach ($crimes as $crime) {
                        $offenseType = OffenseType::firstOrCreate(
                            ['name' => $crime->offense],
                            ['is_active' => true]
                        );

                        DB::table('crime_incidents')
                            ->where('id', $crime->id)
                            ->update(['offense_type_id' => $offenseType->id]);
                    }
                });

            Schema::table('crime_incidents', function (Blueprint $table) {
                $table->dropColumn('offense');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('crime_incidents', 'offense')) {
            Schema::table('crime_incidents', function (Blueprint $table) {
                $table->string('offense', 500)->nullable()->after('stage_of_felony');
            });
        }

        if (Schema::hasColumn('crime_incidents', 'offense_type_id')) {
            DB::table('crime_incidents')
                ->leftJoin('offense_types', 'crime_incidents.offense_type_id', '=', 'offense_types.id')
                ->whereNotNull('crime_incidents.offense_type_id')
                ->update(['crime_incidents.offense' => DB::raw('offense_types.name')]);

            Schema::table('crime_incidents', function (Blueprint $table) {
                $table->dropConstrainedForeignId('offense_type_id');
            });
        }
    }
};
