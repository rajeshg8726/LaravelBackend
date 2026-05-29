<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'work_experience')) {
                $table->json('work_experience')->nullable()->after('skills');
            }
            if (!Schema::hasColumn('users', 'education')) {
                $table->json('education')->nullable()->after('work_experience');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['work_experience', 'education']);
        });
    }
};
