<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;
    private District $district;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->district = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $this->citizen = User::factory()->create([
            'name' => 'Test Citizen',
            'email' => 'citizen@test.com',
            'user_type' => User::TYPE_CITIZEN,
            'is_active' => true,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ahmad Khan',
            'father_name' => 'Muhammad Khan',
            'cnic' => '17301-1234567-1',
            'dob' => '1995-05-15',
            'gender' => 'male',
            'mobile' => '03001234567',
            'address' => 'House 123, Sector F-10, Peshawar',
            'residence_district_id' => $this->district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03007654321',
        ], $overrides);
    }

    public function test_profile_update_fails_when_profile_pic_is_missing(): void
    {
        $payload = $this->validPayload();

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->putJson('/api/v1/profile', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['profile_pic']);
    }

    public function test_profile_update_fails_when_profile_pic_is_not_an_image(): void
    {
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
        $payload = $this->validPayload(['profile_pic' => $file]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->put('/api/v1/profile', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['profile_pic']);
    }

    public function test_profile_update_succeeds_with_uploaded_image_file(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);
        $payload = $this->validPayload(['profile_pic' => $file]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->put('/api/v1/profile', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Profile updated');
        $response->assertJsonPath('profile_complete', true);

        $photoPath = $response->json('profile.photo_path');
        $this->assertNotEmpty($photoPath);
        Storage::disk('public')->assertExists($photoPath);

        $this->citizen->refresh();
        $this->assertEquals($photoPath, $this->citizen->citizenProfile->photo_path);
        $this->assertNotEmpty($this->citizen->citizenProfile->photo_url);
    }

    public function test_profile_update_succeeds_with_photo_field_alias(): void
    {
        $file = UploadedFile::fake()->image('my_photo.png', 200, 200);
        $payload = $this->validPayload(['photo' => $file]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->put('/api/v1/profile', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Profile updated');

        $photoPath = $response->json('profile.photo_path');
        $this->assertNotEmpty($photoPath);
        Storage::disk('public')->assertExists($photoPath);
    }

    public function test_profile_update_succeeds_via_post_method(): void
    {
        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);
        $payload = $this->validPayload(['profile_pic' => $file]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->post('/api/v1/profile', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Profile updated');

        $photoPath = $response->json('profile.photo_path');
        $this->assertNotEmpty($photoPath);
        Storage::disk('public')->assertExists($photoPath);
    }

    public function test_profile_update_succeeds_with_base64_image_string(): void
    {
        // 1x1 transparent PNG base64
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $payload = $this->validPayload(['profile_pic' => $base64]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->putJson('/api/v1/profile', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Profile updated');

        $photoPath = $response->json('profile.photo_path');
        $this->assertNotEmpty($photoPath);
        Storage::disk('public')->assertExists($photoPath);
    }

    public function test_profile_update_deletes_old_photo_when_new_one_is_uploaded(): void
    {
        // First upload
        $file1 = UploadedFile::fake()->image('first.jpg');
        $this->actingAs($this->citizen, 'sanctum')
            ->put('/api/v1/profile', $this->validPayload(['profile_pic' => $file1]));

        $firstPath = $this->citizen->fresh()->citizenProfile->photo_path;
        Storage::disk('public')->assertExists($firstPath);

        // Second upload
        $file2 = UploadedFile::fake()->image('second.png');
        $this->actingAs($this->citizen, 'sanctum')
            ->put('/api/v1/profile', $this->validPayload(['profile_pic' => $file2]));

        $secondPath = $this->citizen->fresh()->citizenProfile->photo_path;
        $this->assertNotEquals($firstPath, $secondPath);
        Storage::disk('public')->assertExists($secondPath);
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_profile_update_can_retain_existing_photo_path(): void
    {
        // Create profile with existing photo
        $existingPath = 'profiles/existing_sample.jpg';
        Storage::disk('public')->put($existingPath, 'fake-image-data');

        $this->citizen->citizenProfile()->create([
            'full_name' => 'Existing Name',
            'father_name' => 'Father',
            'cnic' => '17301-1234567-1',
            'dob' => '1995-05-15',
            'gender' => 'male',
            'address' => 'Old Address',
            'residence_district_id' => $this->district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03007654321',
            'photo_path' => $existingPath,
        ]);

        $payload = $this->validPayload([
            'address' => 'Updated New Address',
            'profile_pic' => $existingPath,
        ]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->putJson('/api/v1/profile', $payload);

        $response->assertStatus(200);
        $this->assertEquals($existingPath, $this->citizen->fresh()->citizenProfile->photo_path);
        Storage::disk('public')->assertExists($existingPath);
    }

    public function test_photo_url_returns_ngrok_url_when_called_with_forwarded_headers(): void
    {
        $existingPath = 'profiles/ngrok_sample.jpg';
        Storage::disk('public')->put($existingPath, 'fake-image-data');

        $this->citizen->citizenProfile()->create([
            'full_name' => 'Ngrok User',
            'father_name' => 'Father',
            'cnic' => '17301-9999999-1',
            'dob' => '1995-05-15',
            'gender' => 'male',
            'address' => 'Sample Address',
            'residence_district_id' => $this->district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03001234567',
            'photo_path' => $existingPath,
        ]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->withHeaders([
                'X-Forwarded-Host' => 'demo-ngrok-url.ngrok-free.app',
                'X-Forwarded-Proto' => 'https',
            ])
            ->getJson('/api/v1/profile');

        $response->assertStatus(200);
        $this->assertEquals(
            'https://demo-ngrok-url.ngrok-free.app/storage/profiles/ngrok_sample.jpg',
            $response->json('profile.photo_url')
        );
    }

    public function test_photo_url_respects_image_base_url_config_if_set(): void
    {
        config(['app.image_base_url' => 'https://cdn.example.com']);

        $profile = new \App\Models\CitizenProfile([
            'photo_path' => 'profiles/custom.jpg',
        ]);

        $this->assertEquals('https://cdn.example.com/storage/profiles/custom.jpg', $profile->photo_url);
    }
}
