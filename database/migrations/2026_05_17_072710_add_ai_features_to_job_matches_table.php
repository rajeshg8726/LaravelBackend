<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('job_matches', 'cover_letter')) {
                $table->longText('cover_letter')->nullable();
            }
            if (!Schema::hasColumn('job_matches', 'optimized_profile')) {
                $table->longText('optimized_profile')->nullable();
            }
            if (!Schema::hasColumn('job_matches', 'interview_questions')) {
                $table->json('interview_questions')->nullable();
            }
            if (!Schema::hasColumn('job_matches', 'salary_benchmark')) {
                $table->json('salary_benchmark')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_matches', function (Blueprint $table) {
            $table->dropColumn(['cover_letter', 'optimized_profile', 'interview_questions', 'salary_benchmark']);
        });
    }
};
