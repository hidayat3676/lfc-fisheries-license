<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PatternLockTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->citizen = User::factory()->create([
            'name' => 'Pattern User',
            'email' => 'pattern@example.com',
            'password' => Hash::make('Password123!'),
            'user_type' => User::TYPE_CITIZEN,
            'is_active' => true,
            'pattern_lock_enabled' => false,
            'pattern_lock_hash' => null,
        ]);
    }

    public function test_citizen_can_enable_pattern_lock_via_web_profile(): void
    {
        $response = $this->actingAs($this->citizen)
            ->post('/account/profile/pattern-lock', [
                'pattern_lock_enabled' => 1,
                'pattern' => '12589',
            ]);

        $response->assertRedirect('/account/profile');
        $response->assertSessionHas('status', 'Pattern lock enabled successfully.');

        $this->citizen->refresh();
        $this->assertTrue($this->citizen->pattern_lock_enabled);
        $this->assertTrue(Hash::check('12589', $this->citizen->pattern_lock_hash));
    }

    public function test_login_redirects_to_pattern_verify_when_pattern_lock_enabled(): void
    {
        $this->citizen->update([
            'pattern_lock_enabled' => true,
            'pattern_lock_hash' => Hash::make('12589'),
        ]);

        $response = $this->post('/login', [
            'email' => 'pattern@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('pattern.verify'));
        $response->assertSessionHas('pending_pattern_user_id', $this->citizen->id);
        $this->assertGuest();
    }

    public function test_invalid_pattern_verification_fails(): void
    {
        $this->citizen->update([
            'pattern_lock_enabled' => true,
            'pattern_lock_hash' => Hash::make('12589'),
        ]);

        $this->withSession(['pending_pattern_user_id' => $this->citizen->id]);

        $response = $this->post('/login/pattern-verify', [
            'pattern' => '98521', // wrong pattern
        ]);

        $response->assertSessionHasErrors('pattern');
        $this->assertGuest();
    }

    public function test_valid_pattern_verification_logs_user_in(): void
    {
        $this->citizen->update([
            'pattern_lock_enabled' => true,
            'pattern_lock_hash' => Hash::make('12589'),
        ]);

        $this->withSession(['pending_pattern_user_id' => $this->citizen->id]);

        $response = $this->post('/login/pattern-verify', [
            'pattern' => '12589',
        ]);

        $response->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($this->citizen);
    }

    public function test_api_can_enable_and_verify_pattern_lock(): void
    {
        // 1. Enable pattern lock via API
        $response = $this->actingAs($this->citizen, 'sanctum')
            ->postJson('/api/v1/profile/pattern-lock', [
                'enabled' => true,
                'pattern' => '14789',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('user.pattern_lock_enabled', true);

        // 2. Verify pattern via API
        $response = $this->actingAs($this->citizen, 'sanctum')
            ->postJson('/api/v1/auth/verify-pattern', [
                'pattern' => '14789',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // 3. Verify incorrect pattern fails
        $response = $this->actingAs($this->citizen, 'sanctum')
            ->postJson('/api/v1/auth/verify-pattern', [
                'pattern' => '98741',
            ]);

        $response->assertStatus(422);
    }
}
