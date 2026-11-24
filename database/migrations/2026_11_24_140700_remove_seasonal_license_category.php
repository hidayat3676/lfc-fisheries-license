<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('license_categories')) {
            return;
        }

        $category = DB::table('license_categories')
            ->where('code', 'SEASONAL')
            ->first();

        if ($category) {
            if (Schema::hasTable('closed_seasons')) {
                DB::table('closed_seasons')
                    ->where('scope_type', 'category')
                    ->where('scope_id', $category->id)
                    ->delete();
            }

            DB::table('license_categories')
                ->where('id', $category->id)
                ->delete();
        }
    }

    public function down(): void
    {
        //
    }
};

