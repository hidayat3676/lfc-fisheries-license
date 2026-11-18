<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reservoirs', 'license_type')) {
            Schema::table('reservoirs', function (Blueprint $table) {
                $table->text('license_type')->nullable()->after('trout_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reservoirs', 'license_type')) {
            Schema::table('reservoirs', function (Blueprint $table) {
                $table->dropColumn('license_type');
            });
        }
    }
};

