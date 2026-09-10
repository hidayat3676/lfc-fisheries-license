<?php

return [

    'otp' => [
        'length' => 6,
        'ttl_minutes' => (int) env('RFLMS_OTP_TTL_MINUTES', 10),
        'resend_cooldown_seconds' => (int) env('RFLMS_OTP_RESEND_COOLDOWN', 60),
        'max_attempts' => (int) env('RFLMS_OTP_MAX_ATTEMPTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy defaults (overridable in Admin → Setup)
    |--------------------------------------------------------------------------
    */
    'policy' => [
        'seasonal_expiry_month_day' => env('RFLMS_SEASONAL_EXPIRY', '06-30'),
        'require_explicit_e_licence' => filter_var(env('RFLMS_REQUIRE_EXPLICIT_E_LICENCE', true), FILTER_VALIDATE_BOOLEAN),
        'qr_privacy' => env('RFLMS_QR_PRIVACY', 'masked'), // full | masked
        'show_cnic_on_public_qr' => filter_var(env('RFLMS_QR_SHOW_CNIC', false), FILTER_VALIDATE_BOOLEAN),
        'setup_confirmed_fees' => false,
        'setup_confirmed_eligibility' => false,
        'setup_confirmed_templates' => false,
        'setup_confirmed_qr' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff permission templates (applied on user create/edit)
    |--------------------------------------------------------------------------
    | Keys under modules are module keys from the list below.
    | Actions: view, create, edit, delete, status, approve
    */
    'permission_templates' => [
        'dg' => [
            'label' => 'DG / Provincial',
            'description' => 'Province-wide oversight: view all, approve licences, manage reports & settings.',
            'all_districts' => true,
            'modules' => [
                'dashboard' => ['view'],
                'districts' => ['view'],
                'offices' => ['view'],
                'reservoirs' => ['view', 'edit'],
                'license_categories' => ['view', 'edit'],
                'applications' => ['view', 'approve'],
                'licenses' => ['view'],
                'payments' => ['view'],
                'violations' => ['view', 'status'],
                'reports' => ['view'],
                'users' => ['view', 'create', 'edit'],
                'settings' => ['view', 'edit'],
            ],
        ],
        'district' => [
            'label' => 'District Officer',
            'description' => 'District-scoped ops: review applications, triage violations, manage local water bodies.',
            'all_districts' => false,
            'modules' => [
                'dashboard' => ['view'],
                'offices' => ['view'],
                'reservoirs' => ['view', 'edit'],
                'license_categories' => ['view'],
                'applications' => ['view', 'approve'],
                'licenses' => ['view'],
                'payments' => ['view'],
                'violations' => ['view', 'status'],
                'reports' => ['view'],
            ],
        ],
        'office' => [
            'label' => 'Office / Assistant',
            'description' => 'Day-to-day queue: view & process applications, limited master-data edits.',
            'all_districts' => false,
            'modules' => [
                'dashboard' => ['view'],
                'reservoirs' => ['view'],
                'license_categories' => ['view'],
                'applications' => ['view', 'approve'],
                'licenses' => ['view'],
                'payments' => ['view'],
                'violations' => ['view', 'status'],
                'reports' => ['view'],
            ],
        ],
        'field' => [
            'label' => 'Field Officer',
            'description' => 'Field checks: verify licences / QR, record & update violation status.',
            'all_districts' => false,
            'modules' => [
                'dashboard' => ['view'],
                'licenses' => ['view'],
                'violations' => ['view', 'create', 'status'],
                'applications' => ['view'],
            ],
        ],
    ],

    'modules' => [
        ['key' => 'dashboard', 'name' => 'Dashboard'],
        ['key' => 'districts', 'name' => 'Districts'],
        ['key' => 'offices', 'name' => 'Offices'],
        ['key' => 'reservoirs', 'name' => 'Reservoirs / Water Bodies'],
        ['key' => 'species', 'name' => 'Fish Species'],
        ['key' => 'license_categories', 'name' => 'Licence Categories & Seasons'],
        ['key' => 'applications', 'name' => 'Licence Applications'],
        ['key' => 'licenses', 'name' => 'Issued Licences'],
        ['key' => 'payments', 'name' => 'Payments'],
        ['key' => 'violations', 'name' => 'Violations / Illegal Fishing'],
        ['key' => 'reports', 'name' => 'Reports'],
        ['key' => 'users', 'name' => 'User Management'],
        ['key' => 'settings', 'name' => 'System Settings'],
        ['key' => 'audit', 'name' => 'Audit Logs'],
    ],

    'actions' => [
        'view',
        'create',
        'edit',
        'delete',
        'status',
        'approve',
    ],

    'seed_csv_path' => env(
        'RFLMS_SEED_CSV',
        dirname(base_path()).'/ignore/shared-sources/seed/reservoir-master-seed-v1.csv'
    ),

    'water_body_types' => [
        'dam' => 'Dam / Reservoir',
        'river' => 'River',
        'stream' => 'Stream',
        'canal' => 'Canal',
        'headworks' => 'Headworks',
        'other' => 'Other',
    ],

    'trout_types' => [
        'trout' => 'Trout',
        'non_trout' => 'Non-trout',
        'mixed' => 'Mixed',
        'unknown' => 'Unknown',
    ],

];
