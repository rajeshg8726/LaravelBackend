<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('users', function (Blueprint $table) {
            // Give every user 3 free credits by default
            $table->integer('ai_credits')->default(3)->after('email');
            $table->boolean('is_pro')->default(false)->after('ai_credits');
        });
    }

    public function down() {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ai_credits', 'is_pro']);
        });
    }
};
