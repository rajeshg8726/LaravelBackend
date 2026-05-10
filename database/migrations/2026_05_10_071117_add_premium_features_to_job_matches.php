<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('job_matches', function (Blueprint $table) {
            $table->json('missing_keywords')->nullable()->after('ai_feedback');
            $table->longText('cover_letter')->nullable()->after('missing_keywords');
        });
    }

    public function down() {
        Schema::table('job_matches', function (Blueprint $table) {
            $table->dropColumn(['missing_keywords', 'cover_letter']);
        });
    }
};
