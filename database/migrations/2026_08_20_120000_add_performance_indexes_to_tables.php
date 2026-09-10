<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['user_type', 'is_active']);
            $table->index('mobile');
        });

        Schema::table('citizen_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('residence_district_id');
            $table->index('cnic');
        });

        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('district_id');
            $table->index('cnic');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('reservoirs', function (Blueprint $table) {
            $table->index(['is_active', 'is_open_for_licensing']);
            $table->index('office_id');
        });

        Schema::table('license_categories', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('closed_seasons', function (Blueprint $table) {
            $table->index(['is_active', 'scope_type', 'scope_id']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index('reservoir_id');
            $table->index('category_id');
            $table->index('reviewed_by');
            $table->index('psid_code');
            $table->index('payment_method');
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->index('application_id');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->index('application_id');
            $table->index('user_id');
            $table->index('reservoir_id');
            $table->index('category_id');
            $table->index('issued_by');
            $table->index(['user_id', 'reservoir_id', 'status']);
            $table->index(['status', 'expiry_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('application_id');
            $table->index('verified_by');
            $table->index('status');
            $table->index('reference');
        });

        Schema::table('violation_reports', function (Blueprint $table) {
            $table->index('reporter_user_id');
            $table->index('assigned_to');
            $table->index('reservoir_id');
        });

        Schema::table('violation_media', function (Blueprint $table) {
            $table->index('violation_report_id');
        });
    }

    public function down(): void
    {
        Schema::table('violation_media', function (Blueprint $table) {
            $table->dropIndex(['violation_report_id']);
        });

        Schema::table('violation_reports', function (Blueprint $table) {
            $table->dropIndex(['reporter_user_id']);
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['reservoir_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['application_id']);
            $table->dropIndex(['verified_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['reference']);
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex(['application_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['reservoir_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['issued_by']);
            $table->dropIndex(['user_id', 'reservoir_id', 'status']);
            $table->dropIndex(['status', 'expiry_date']);
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex(['application_id']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['reservoir_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['reviewed_by']);
            $table->dropIndex(['psid_code']);
            $table->dropIndex(['payment_method']);
        });

        Schema::table('closed_seasons', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'scope_type', 'scope_id']);
        });

        Schema::table('license_categories', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('reservoirs', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_open_for_licensing']);
            $table->dropIndex(['office_id']);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('admin_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['district_id']);
            $table->dropIndex(['cnic']);
        });

        Schema::table('citizen_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['residence_district_id']);
            $table->dropIndex(['cnic']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_type', 'is_active']);
            $table->dropIndex(['mobile']);
        });
    }
};
