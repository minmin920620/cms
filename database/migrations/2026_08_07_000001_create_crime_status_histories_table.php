<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crime_status_histories')) {
            Schema::table('crime_status_histories', function (Blueprint $table) {
                $table->foreign('crime_id')->references('id')->on('crime_incidents')->cascadeOnDelete();
            });

            return;
        }

        Schema::create('crime_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_id')->constrained('crime_incidents')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            $table->index(['crime_id', 'created_at']);
            $table->index(['changed_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crime_status_histories');
    }
};
