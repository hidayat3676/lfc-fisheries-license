<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Urdu names for Khyber Pakhtunkhwa districts.
     *
     * @var array<string, string>
     */
    public const DISTRICT_URDU_NAMES = [
        'Abbottabad' => 'ایبٹ آباد',
        'Bajaur' => 'باجوڑ',
        'Bannu' => 'بنوں',
        'Battagram' => 'بٹگرام',
        'Buner' => 'بونیر',
        'Charsadda' => 'چارسدہ',
        'Chitral lower' => 'لوئر چترال',
        'Chitral Upper' => 'اپر چترال',
        'Dera Ismail Khan' => 'ڈیرہ اسماعیل خان',
        'Hangu' => 'ہنگو',
        'Haripur' => 'ہری پور',
        'Karak' => 'کرک',
        'Khyber' => 'خیبر',
        'Kohat' => 'کوہاٹ',
        'Kohistan Lower' => 'لوئر کوہستان',
        'Kohistan Upper' => 'اپر کوہستان',
        'Kolai Palas' => 'کولائی پالس',
        'Kurram' => 'کرم',
        'Lakki Marwat' => 'لکی مروت',
        'Lower Dir' => 'لوئر دیر',
        'Malakand' => 'ملاکنڈ',
        'Mansehra' => 'مانسہرہ',
        'Mardan' => 'مردان',
        'Mohmand' => 'مہمند',
        'North Waziristan' => 'شمالی وزیرستان',
        'Nowshera' => 'نوشہرہ',
        'Orakzai' => 'اورکزئی',
        'Peshawar' => 'پشاور',
        'Shangla' => 'شانگلہ',
        'South Waziristan Lower' => 'لوئر جنوبی وزیرستان',
        'South Waziristan Upper' => 'اپر جنوبی وزیرستان',
        'Subdivision Dikhan/Darazinda' => 'سب ڈویژن درازندہ (ڈی آئی خان)',
        'Subdivision Hassan khel' => 'سب ڈویژن حسن خیل',
        'Subdivision wazir bannu/lucky' => 'سب ڈویژن وزیر بنوں/لکی',
        'Swabi' => 'صوابی',
        'Swat' => 'سوات',
        'Tank' => 'ٹانک',
        'Torghar' => 'تورغر',
        'Upper Dir' => 'اپر دیر',
    ];

    public function run(): void
    {
        $hasUrduColumn = Schema::hasColumn('districts', 'urdu_name');

        foreach (self::DISTRICTS as $name) {
            $attributes = [
                'code' => strtoupper(str_replace(['.', ' ', '-', '/'], '', $name)),
                'is_active' => true,
            ];

            if ($hasUrduColumn) {
                $attributes['urdu_name'] = self::DISTRICT_URDU_NAMES[$name] ?? null;
            }

            District::query()->updateOrCreate(
                ['name' => $name],
                $attributes
            );
        }
    }
}
