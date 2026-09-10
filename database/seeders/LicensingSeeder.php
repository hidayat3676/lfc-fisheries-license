<?php

namespace Database\Seeders;

use App\Models\ClosedSeason;
use App\Models\LicenseCategory;
use Illuminate\Database\Seeder;

class LicensingSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'DAILY',
                'name' => 'Daily Licence',
                'description' => 'Valid for one fishing day.',
                'duration_type' => 'daily',
                'duration_days' => 1,
                'fee_amount' => 500,
                'expiry_rule' => 'from_start',
                'fixed_expiry_month_day' => null,
                'instructions' => 'Carry CNIC and digital/printed licence while fishing.',
                'terms' => 'Follow local fishing rules and bag limits.',
            ],
            [
                'code' => 'MONTHLY',
                'name' => 'Monthly Licence',
                'description' => 'Valid for 30 days from start date.',
                'duration_type' => 'monthly',
                'duration_days' => 30,
                'fee_amount' => 3000,
                'expiry_rule' => 'from_start',
                'fixed_expiry_month_day' => null,
                'instructions' => 'Valid for the selected water body only.',
                'terms' => 'Non-transferable. Subject to closed-season rules.',
            ],
            [
                'code' => 'SEASONAL',
                'name' => 'Seasonal Licence',
                'description' => 'Valid until 30 June of the fishing year.',
                'duration_type' => 'seasonal',
                'duration_days' => null,
                'fee_amount' => 8000,
                'expiry_rule' => 'fixed_date',
                'fixed_expiry_month_day' => '06-30',
                'instructions' => 'Expires every year on 30 June regardless of issue date.',
                'terms' => 'Subject to breeding closed seasons and reservoir rules.',
            ],
        ];

        foreach ($categories as $category) {
            LicenseCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                $category + [
                    'currency' => 'PKR',
                    'max_fish_limit' => null,
                    'required_documents' => ['payment_receipt'],
                    'is_active' => true,
                ]
            );
        }

        ClosedSeason::query()->updateOrCreate(
            [
                'scope_type' => 'global',
                'scope_id' => null,
                'start_month' => 6,
                'start_day' => 1,
                'end_month' => 7,
                'end_day' => 31,
            ],
            [
                'reason' => 'Breeding / closed season (draft policy — confirm with DG)',
                'is_active' => true,
            ]
        );
    }
}
