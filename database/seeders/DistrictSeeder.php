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
        'Paharpor',
        'Peshawar',
        'Shangla',
        'South Waziristan',
        'Swabi',
        'Swat',
        'Swat Upper',
        'Tank',
        'Torghar',
        'Upper Dir',
    ];

    /**
     * Official 3-character codes for Khyber Pakhtunkhwa districts.
     *
     * @var array<string, string>
     */
    public const DISTRICT_CODES = [
        'Abbottabad' => 'ABT',
        'Bajaur' => 'BJR',
        'Bannu' => 'BNU',
        'Battagram' => 'BTG',
        'Buner' => 'BNR',
        'Charsadda' => 'CHD',
        'Chitral lower' => 'CTL',
        'Chitral Upper' => 'CTU',
        'Dera Ismail Khan' => 'DIK',
        'Hangu' => 'HNG',
        'Haripur' => 'HRP',
        'Karak' => 'KRK',
        'Khyber' => 'KHY',
        'Kohat' => 'KHT',
        'Kohistan Lower' => 'KHL',
        'Kohistan Upper' => 'KHU',
        'Kolai Palas' => 'KLP',
        'Kurram' => 'KRM',
        'Lakki Marwat' => 'LKM',
        'Lower Dir' => 'DIR',
        'Malakand' => 'MLK',
        'Mansehra' => 'MNS',
        'Mardan' => 'MRD',
        'Mohmand' => 'MHM',
        'North Waziristan' => 'NWA',
        'Nowshera' => 'NSR',
        'Orakzai' => 'ORZ',
        'Paharpor' => 'PHP',
        'Peshawar' => 'PEW',
        'Shangla' => 'SHG',
        'South Waziristan' => 'SWZ',
        'Swabi' => 'SWB',
        'Swat' => 'SWT',
        'Swat Upper' => 'SWP',
        'Tank' => 'TNK',
        'Torghar' => 'TGR',
        'Upper Dir' => 'DRU',
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
        'Paharpor' => 'پہاڑ پور',
        'Peshawar' => 'پشاور',
        'Shangla' => 'شانگلہ',
        'South Waziristan' => 'جنوبی وزیرستان',
        'Swabi' => 'صوابی',
        'Swat' => 'سوات',
        'Swat Upper' => 'اپر سوات',
        'Tank' => 'ٹانک',
        'Torghar' => 'تورغر',
        'Upper Dir' => 'اپر دیر',
    ];

    public static function codeFor(string $name): string
    {
        if (isset(self::DISTRICT_CODES[$name])) {
            return self::DISTRICT_CODES[$name];
        }

        foreach (self::DISTRICT_CODES as $districtName => $code) {
            if (strcasecmp($districtName, trim($name)) === 0) {
                return $code;
            }
        }

        $clean = preg_replace('/[^A-Za-z]/', '', $name);

        return str_pad(strtoupper(substr($clean ?: 'KPK', 0, 3)), 3, 'X');
    }

    public function run(): void
    {
        $hasUrduColumn = Schema::hasColumn('districts', 'urdu_name');

        foreach (self::DISTRICTS as $name) {
            $attributes = [
                'code' => self::codeFor($name),
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
