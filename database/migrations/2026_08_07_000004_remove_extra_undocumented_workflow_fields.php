<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crime_incidents', function (Blueprint $table) {
            if (Schema::hasColumn('crime_incidents', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('crime_incidents', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('crime_incidents', 'approval_notes')) {
                $table->dropColumn('approval_notes');
            }
        });

        Schema::table('evidence', function (Blueprint $table) {
            if (Schema::hasColumn('evidence', 'document_stage')) {
                $table->dropColumn('document_stage');
            }

            if (Schema::hasColumn('evidence', 'is_workflow_proof')) {
                $table->dropColumn('is_workflow_proof');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evidence', function (Blueprint $table) {
            if (! Schema::hasColumn('evidence', 'document_stage')) {
                $table->string('document_stage')->nullable()->after('description');
            }

            if (! Schema::hasColumn('evidence', 'is_workflow_proof')) {
                $table->boolean('is_workflow_proof')->default(false)->after('document_stage');
            }
        });

        Schema::table('crime_incidents', function (Blueprint $table) {
            if (! Schema::hasColumn('crime_incidents', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('assigned_officer')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('crime_incidents', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('crime_incidents', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approved_at');
            }
        });
    }
};
