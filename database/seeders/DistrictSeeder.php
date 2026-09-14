<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Complete list of districts across Khyber Pakhtunkhwa.
     *
     * @var array<int, string>
     */
    public const DISTRICTS = [
        'Abbottabad',
        'Bajaur',
        'Bannu',
        'Battagram',
        'Buner',
        'Charsadda',
        'Chitral lower',
        'Chitral Upper',
        'Dera Ismail Khan',
        'Hangu',
        'Haripur',
        'Karak',
        'Khyber',
        'Kohat',
        'Kohistan Lower',
        'Kohistan Upper',
        'Kolai Palas',
        'Kurram',
        'Lakki Marwat',
        'Lower Dir',
        'Malakand',
        'Mansehra',
        'Mardan',
        'Mohmand',
        'North Waziristan',
        'Nowshera',
        'Orakzai',
        'Peshawar',
        'Shangla',
        'South Waziristan Lower',
        'South Waziristan Upper',
        'Subdivision Dikhan/Darazinda',
        'Subdivision Hassan khel',
        'Subdivision wazir bannu/lucky',
        'Swabi',
        'Swat',
        'Tank',
        'Torghar',
        'Upper Dir',
    ];

    public function run(): void
    {
        foreach (self::DISTRICTS as $name) {
            District::query()->updateOrCreate(
                ['name' => $name],
                [
                    'code' => strtoupper(str_replace(['.', ' ', '-', '/'], '', $name)),
                    'is_active' => true,
                ]
            );
        }
    }
}
