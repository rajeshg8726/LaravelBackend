<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_credit_refresh_at')) {
                $table->timestamp('last_credit_refresh_at')->nullable()->after('ai_credits');
            }
            if (!Schema::hasColumn('users', 'has_received_profile_bonus')) {
                $table->boolean('has_received_profile_bonus')->default(false)->after('last_credit_refresh_at');
            }
            if (!Schema::hasColumn('users', 'is_first_analysis_free_used')) {
                $table->boolean('is_first_analysis_free_used')->default(false)->after('has_received_profile_bonus');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_credit_refresh_at',
                'has_received_profile_bonus',
                'is_first_analysis_free_used'
            ]);
        });
    }
};
