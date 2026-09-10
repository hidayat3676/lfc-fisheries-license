<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_no', 32)->unique();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_phone', 30)->nullable();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservoir_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('violation_type', 64);
            $table->text('description');
            $table->timestamp('occurred_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'district_id']);
        });

        Schema::create('violation_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('violation_report_id')->constrained('violation_reports')->cascadeOnDelete();
            $table->string('path');
            $table->string('media_type', 32)->default('image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_media');
        Schema::dropIfExists('violation_reports');
    }
};
