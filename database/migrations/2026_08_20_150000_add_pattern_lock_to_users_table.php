<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pattern_lock_enabled')->default(false)->after('is_active');
            $table->string('pattern_lock_hash')->nullable()->after('pattern_lock_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pattern_lock_enabled', 'pattern_lock_hash']);
        });
    }
};
