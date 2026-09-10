<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 32)->default('citizen')->after('id');
            $table->string('mobile', 30)->nullable()->after('email');
            $table->boolean('all_districts')->default(false)->after('password');
            $table->boolean('is_active')->default(true)->after('all_districts');
            $table->index('user_type');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 32)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'district_id']);
        });

        Schema::create('user_module_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_status')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'module_id']);
        });

        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('purpose', 32);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'purpose']);
        });

        Schema::create('citizen_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('cnic', 20)->nullable()->unique();
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('address')->nullable();
            $table->foreignId('residence_district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->string('province')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->string('designation_label')->nullable();
            $table->string('mobile', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_profiles');
        Schema::dropIfExists('citizen_profiles');
        Schema::dropIfExists('email_otps');
        Schema::dropIfExists('user_module_permissions');
        Schema::dropIfExists('user_districts');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('districts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_type']);
            $table->dropColumn(['user_type', 'mobile', 'all_districts', 'is_active']);
        });
    }
};
