<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('crime_incidents', 'crimes_reported_by_idx', function (Blueprint $table) {
            $table->index('reported_by', 'crimes_reported_by_idx');
        });

        $this->addIndexIfMissing('crime_incidents', 'crimes_assigned_officer_idx', function (Blueprint $table) {
            $table->index('assigned_officer', 'crimes_assigned_officer_idx');
        });

        $this->addIndexIfMissing('crime_incidents', 'crimes_created_at_idx', function (Blueprint $table) {
            $table->index('created_at', 'crimes_created_at_idx');
        });

        $this->addIndexIfMissing('evidence', 'evidence_crime_id_deleted_at_idx', function (Blueprint $table) {
            $table->index(['crime_id', 'deleted_at'], 'evidence_crime_id_deleted_at_idx');
        });

        $this->addIndexIfMissing('offense_types', 'offense_types_crime_type_active_idx', function (Blueprint $table) {
            $table->index(['crime_type_id', 'is_active'], 'offense_types_crime_type_active_idx');
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('offense_types', 'offense_types_crime_type_active_idx');
        $this->dropIndexIfExists('evidence', 'evidence_crime_id_deleted_at_idx');
        $this->dropIndexIfExists('crime_incidents', 'crimes_created_at_idx');
        $this->dropIndexIfExists('crime_incidents', 'crimes_assigned_officer_idx');
        $this->dropIndexIfExists('crime_incidents', 'crimes_reported_by_idx');
    }

    private function addIndexIfMissing(string $tableName, string $indexName, callable $callback): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, $callback);
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$tableName}')");

            return collect($indexes)->contains(fn ($index) => ($index->name ?? null) === $indexName);
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }
};
