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
