<?php

namespace Tests\Feature;

use App\Models\District;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DistrictSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistrictSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_district_seeder_seeds_all_expected_districts(): void
    {
        $this->seed(DistrictSeeder::class);

        $expectedDistricts = DistrictSeeder::DISTRICTS;

        $this->assertSame(count($expectedDistricts), District::query()->count());
        $this->assertSame(count($expectedDistricts), District::query()->where('is_active', true)->count());

        foreach ($expectedDistricts as $name) {
            $district = District::query()->where('name', $name)->first();
            $this->assertNotNull($district, "District {$name} was not found.");
            $this->assertTrue($district->is_active);
            $expectedCode = strtoupper(str_replace(['.', ' ', '-', '/'], '', $name));
            $this->assertSame($expectedCode, $district->code);
            $this->assertLessThanOrEqual(32, strlen($district->code));
        }
    }

    public function test_database_seeder_runs_district_seeder_cleanly(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(count(DistrictSeeder::DISTRICTS), District::query()->count());
        $this->assertTrue(District::query()->where('name', 'Abbottabad')->exists());
        $this->assertTrue(District::query()->where('name', 'Charsadda')->exists());
        $this->assertTrue(District::query()->where('name', 'Buner')->exists());
        $this->assertTrue(District::query()->where('name', 'Kurram')->exists());
    }
}
