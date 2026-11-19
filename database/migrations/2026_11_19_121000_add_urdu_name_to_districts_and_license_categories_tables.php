<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('districts') && ! Schema::hasColumn('districts', 'urdu_name')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->string('urdu_name', 120)->nullable()->after('name');
            });
        }

        if (Schema::hasTable('license_categories') && ! Schema::hasColumn('license_categories', 'urdu_name')) {
            Schema::table('license_categories', function (Blueprint $table) {
                $table->string('urdu_name', 120)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('districts') && Schema::hasColumn('districts', 'urdu_name')) {
            Schema::table('districts', function (Blueprint $table) {
                $table->dropColumn('urdu_name');
            });
        }

        if (Schema::hasTable('license_categories') && Schema::hasColumn('license_categories', 'urdu_name')) {
            Schema::table('license_categories', function (Blueprint $table) {
                $table->dropColumn('urdu_name');
            });
        }
    }
};

