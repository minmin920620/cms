<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            if (! Schema::hasColumn('evidence', 'category')) {
                $table->string('category', 40)->default('evidence')->after('file_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            if (Schema::hasColumn('evidence', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
