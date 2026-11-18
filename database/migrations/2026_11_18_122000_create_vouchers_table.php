<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('dpt_payment_id', 64)->unique();
                $table->string('tracking_number', 64)->nullable()->index();
                $table->string('psid', 64)->nullable()->index();
                $table->unsignedBigInteger('arc_id')->nullable()->index();
                $table->string('citizen_cnic', 32)->nullable();
                $table->string('department_name')->nullable();
                $table->string('service_name')->nullable();
                $table->string('service_type_name')->nullable();
                $table->string('service_key', 32)->nullable();
                $table->string('voucher_against', 32)->default('CNIC');
                $table->string('tag', 64)->nullable();
                $table->decimal('fee_amount', 12, 2)->default(0);
                $table->integer('status_code')->default(0);
                $table->string('status', 32)->default('pending')->index();
                $table->string('status_msg')->nullable();
                $table->timestamp('epay_expire_date')->nullable();
                $table->string('service_d', 128)->nullable();
                $table->string('remarks')->nullable();
                $table->string('transaction_id', 64)->nullable();
                $table->decimal('transaction_amount', 12, 2)->nullable();
                $table->string('transaction_date', 64)->nullable();
                $table->string('synching_status', 64)->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};

