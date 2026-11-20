<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('license_categories') && ! Schema::hasColumn('license_categories', 'type')) {
            Schema::table('license_categories', function (Blueprint $table) {
                $table->string('type', 32)->default('non_trout')->after('urdu_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('license_categories') && Schema::hasColumn('license_categories', 'type')) {
            Schema::table('license_categories', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};

