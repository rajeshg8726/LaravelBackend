<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_first_resume_health_free_used')) {
                $table->boolean('is_first_resume_health_free_used')->default(false)->after('is_first_analysis_free_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_first_resume_health_free_used')) {
                $table->dropColumn('is_first_resume_health_free_used');
            }
        });
    }
};
