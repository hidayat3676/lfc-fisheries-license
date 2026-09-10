<?php

namespace Database\Seeders;

use App\Models\AdminProfile;
use App\Models\District;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('rflms.modules') as $i => $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                [
                    'name' => $module['name'],
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]
            );
        }

        $districtNames = [
            'Bajaur', 'Bannu', 'Battagram', 'D.I. Khan', 'Dir Lower', 'Dir Upper',
            'Hangu', 'Haripur', 'Karak', 'Khyber', 'Kohat', 'Lower Chitral',
            'Malakand', 'Mansehra', 'Mardan', 'North Waziristan', 'Nowshera',
            'Peshawar', 'Shangla', 'Swabi', 'Swat', 'Upper Chitral',
        ];

        foreach ($districtNames as $name) {
            District::query()->updateOrCreate(
                ['name' => $name],
                [
                    'code' => strtoupper(str_replace(['.', ' ', '-'], '', $name)),
                    'is_active' => true,
                ]
            );
        }

        $super = User::query()->updateOrCreate(
            ['email' => 'superadmin@fisheries.kp.gov.pk'],
            [
                'name' => 'Super Admin',
                'password' => 'ChangeMe123!',
                'user_type' => User::TYPE_SUPER_ADMIN,
                'all_districts' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        AdminProfile::query()->updateOrCreate(
            ['user_id' => $super->id],
            [
                'full_name' => 'Super Admin',
                'designation_label' => 'System Super Admin',
                'mobile' => null,
            ]
        );

        $this->call(ReservoirMasterSeeder::class);
        $this->call(LicensingSeeder::class);
    }
}
