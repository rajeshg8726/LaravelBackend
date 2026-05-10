<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The Candidate
            $table->foreignId('job_id')->constrained()->onDelete('cascade'); // The Job
            $table->integer('match_score'); // 1-100
            $table->text('ai_feedback')->nullable(); // The 2-sentence feedback
            $table->timestamps();
            
            // Prevent duplicate matches for the same user and job
            $table->unique(['user_id', 'job_id']); 
        });
    }

    public function down() {
        Schema::dropIfExists('job_matches');
    }
};
