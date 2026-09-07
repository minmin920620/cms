<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->string('storage_disk', 50)->default('public')->after('file_path');
            $table->index(['crime_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            $table->dropIndex(['crime_id', 'created_at']);
            $table->dropColumn('storage_disk');
        });
    }
};
