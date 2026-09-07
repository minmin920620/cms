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
        if (! Schema::hasTable('evidence')) {
            Schema::create('evidence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crime_id')->constrained('crime_incidents')->onDelete('cascade');
                $table->string('file_path');
                $table->string('file_type', 50)->nullable();
                $table->string('file_name')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });

            return;
        }

        Schema::table('evidence', function (Blueprint $table) {
            $table->foreign('crime_id')->references('id')->on('crime_incidents')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};

