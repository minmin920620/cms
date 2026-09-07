<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crime_incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('crime_incidents', 'approval_status')) {
                $table->string('approval_status', 30)->default('none')->after('status_notes');
                $table->string('approval_requested_status', 50)->nullable()->after('approval_status');
                $table->text('approval_notes')->nullable()->after('approval_requested_status');
                $table->foreignId('approval_requested_by')->nullable()->after('approval_notes')->constrained('users')->nullOnDelete();
                $table->timestamp('approval_requested_at')->nullable()->after('approval_requested_by');
                $table->foreignId('approved_by')->nullable()->after('approval_requested_at')->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crime_incidents', function (Blueprint $table) {
            foreach (['approved_by', 'approval_requested_by'] as $column) {
                if (Schema::hasColumn('crime_incidents', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['approved_at', 'approval_requested_at', 'approval_notes', 'approval_requested_status', 'approval_status'] as $column) {
                if (Schema::hasColumn('crime_incidents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
