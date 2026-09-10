<?php

namespace Tests\Feature;

use App\Models\District;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportViolationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_violation_page_renders_without_gps_button(): void
    {
        District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $response = $this->get('/report-violation');

        $response->assertStatus(200);
        $response->assertSee('Report illegal fishing');
        $response->assertSee('Latitude');
        $response->assertSee('Longitude');
        $response->assertDontSee('>GPS</button>', false);
        $response->assertDontSee('useGps()', false);
    }
}
