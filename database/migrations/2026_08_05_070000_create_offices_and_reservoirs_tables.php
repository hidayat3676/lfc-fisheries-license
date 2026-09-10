<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['district_id', 'name']);
        });

        Schema::create('reservoirs', function (Blueprint $table) {
            $table->id();
            $table->string('seed_id', 32)->nullable()->unique();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('water_body_type', 32)->default('other');
            $table->string('trout_type', 32)->default('unknown');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('start_lat', 10, 7)->nullable();
            $table->decimal('start_lng', 10, 7)->nullable();
            $table->decimal('end_lat', 10, 7)->nullable();
            $table->decimal('end_lng', 10, 7)->nullable();
            $table->text('trout_stretch_notes')->nullable();
            $table->text('reserve_area_notes')->nullable();
            $table->text('lease_notes')->nullable();
            $table->string('length_km_notes')->nullable();
            $table->text('species_notes')->nullable();
            $table->text('coordinates_raw')->nullable();
            $table->string('directions_url')->nullable();
            $table->boolean('is_open_for_licensing')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('needs_review')->default(false);
            $table->string('source_file')->nullable();
            $table->timestamps();

            $table->index(['district_id', 'is_active']);
            $table->index(['water_body_type', 'trout_type']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservoirs');
        Schema::dropIfExists('offices');
    }
};
