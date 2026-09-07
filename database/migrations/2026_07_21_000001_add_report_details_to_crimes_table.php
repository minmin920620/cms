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
        $columns = [
            'date_reported' => fn (Blueprint $table) => $table->date('date_reported')->nullable(),
            'time_reported' => fn (Blueprint $table) => $table->time('time_reported')->nullable(),
            'street_highway' => fn (Blueprint $table) => $table->string('street_highway')->nullable(),
            'offense' => fn (Blueprint $table) => $table->string('offense', 500)->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('crime_incidents', $column)) {
                Schema::table('crime_incidents', $definition);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = collect(['date_reported', 'time_reported', 'street_highway', 'offense'])
            ->filter(fn (string $column) => Schema::hasColumn('crime_incidents', $column))
            ->all();

        if ($columns) {
            Schema::table('crime_incidents', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
