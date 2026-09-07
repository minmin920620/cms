<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_id')->constrained('crime_incidents')->cascadeOnDelete();
            $table->foreignId('old_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['crime_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_assignment_histories');
    }
};
