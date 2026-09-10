<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('duration_type', 32);
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('fee_amount', 12, 2);
            $table->string('currency', 8)->default('PKR');
            $table->unsignedInteger('max_fish_limit')->nullable();
            $table->text('instructions')->nullable();
            $table->text('terms')->nullable();
            $table->json('required_documents')->nullable();
            $table->string('expiry_rule', 32)->default('from_start');
            $table->string('fixed_expiry_month_day', 5)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('closed_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 32)->default('global');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->unsignedTinyInteger('start_month');
            $table->unsignedTinyInteger('start_day')->default(1);
            $table->unsignedTinyInteger('end_month');
            $table->unsignedTinyInteger('end_day')->default(28);
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservoir_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('license_categories')->restrictOnDelete();
            $table->date('fishing_start_date');
            $table->date('fishing_end_date')->nullable();
            $table->decimal('fee_amount_snapshot', 12, 2);
            $table->string('currency', 8)->default('PKR');
            $table->string('status', 32)->default('submitted');
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_receipt_path')->nullable();
            $table->string('psid_code')->nullable();
            $table->text('officer_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('doc_type', 64);
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_no', 32)->unique();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservoir_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('license_categories')->restrictOnDelete();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('status', 32)->default('active');
            $table->string('qr_token', 64)->unique();
            $table->string('pdf_path')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('method', 32);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('PKR');
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('closed_seasons');
        Schema::dropIfExists('license_categories');
    }
};
