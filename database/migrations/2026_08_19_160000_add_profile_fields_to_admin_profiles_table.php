<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->string('cnic', 20)->nullable();
            $table->string('father_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->dropColumn(['cnic', 'father_name', 'dob', 'gender', 'address', 'emergency_contact']);
        });
    }
};
