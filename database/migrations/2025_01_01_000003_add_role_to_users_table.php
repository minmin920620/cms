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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'police_officer', 'investigator'])->default('police_officer')->after('email');
            $table->string('phone', 20)->nullable()->after('password');
            $table->string('badge_number', 50)->nullable()->after('phone');
            $table->string('avatar')->nullable()->after('badge_number');
            $table->boolean('is_active')->default(true)->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'badge_number', 'avatar', 'is_active']);
        });
    }
};

