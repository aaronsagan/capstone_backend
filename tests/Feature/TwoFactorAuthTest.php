<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'donor',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        
        $this->google2fa = new Google2FA();
    }

    /** @test */
    public function it_can_check_2fa_status()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/me/2fa/status');

        $response->assertStatus(200)
            ->assertJson([
                'enabled' => false,
                'enabled_at' => null,
            ]);
    }

    /** @test */
    public function it_can_enable_2fa_and_get_qr_code()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/enable');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'secret',
                'qr_code',
                'recovery_codes',
            ]);

        // Verify secret was saved to database
        $this->user->refresh();
        $this->assertNotNull($this->user->two_factor_secret);
        $this->assertNotNull($this->user->two_factor_recovery_codes);
        $this->assertFalse($this->user->two_factor_enabled);
    }

    /** @test */
    public function it_prevents_enabling_2fa_when_already_enabled()
    {
        // First enable
        $this->user->update([
            'two_factor_secret' => encrypt('TESTSECRET123'),
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
        ]);

        // Try to enable again
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/enable');

        $response->assertStatus(422)
            ->assertJson([
                'message' => '2FA is already enabled'
            ]);
    }

    /** @test */
    public function it_can_verify_and_activate_2fa_with_valid_code()
    {
        // Enable 2FA first
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/enable');

        $secret = $response->json('secret');
        
        // Generate valid code
        $validCode = $this->google2fa->getCurrentOtp($secret);

        // Verify with valid code
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/verify', [
                'code' => $validCode,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '2FA enabled successfully',
            ])
            ->assertJsonStructure([
                'recovery_codes',
            ]);

        // Verify 2FA is now enabled
        $this->user->refresh();
        $this->assertTrue($this->user->two_factor_enabled);
        $this->assertNotNull($this->user->two_factor_enabled_at);
    }

    /** @test */
    public function it_rejects_invalid_verification_code()
    {
        // Enable 2FA first
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/enable');

        // Try to verify with invalid code
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/verify', [
                'code' => '000000', // Invalid code
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Invalid verification code. Please check and try again'
            ]);

        // Verify 2FA is still not enabled
        $this->user->refresh();
        $this->assertFalse($this->user->two_factor_enabled);
    }

    /** @test */
    public function it_requires_enable_before_verify()
    {
        // Try to verify without enabling first
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/verify', [
                'code' => '123456',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Please enable 2FA first by clicking Enable 2FA button'
            ]);
    }

    /** @test */
    public function it_can_disable_2fa_with_correct_password()
    {
        // Enable 2FA first
        $this->user->update([
            'two_factor_secret' => encrypt('TESTSECRET123'),
            'two_factor_recovery_codes' => encrypt(json_encode(['CODE1', 'CODE2'])),
            'two_factor_enabled' => true,
            'two_factor_enabled_at' => now(),
        ]);

        // Disable with correct password
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/disable', [
                'password' => 'password123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '2FA disabled successfully'
            ]);

        // Verify 2FA is disabled
        $this->user->refresh();
        $this->assertFalse($this->user->two_factor_enabled);
        $this->assertNull($this->user->two_factor_secret);
        $this->assertNull($this->user->two_factor_recovery_codes);
    }

    /** @test */
    public function it_rejects_disable_2fa_with_incorrect_password()
    {
        // Enable 2FA first
        $this->user->update([
            'two_factor_secret' => encrypt('TESTSECRET123'),
            'two_factor_enabled' => true,
        ]);

        // Try to disable with wrong password
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/disable', [
                'password' => 'wrongpassword',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Password is incorrect'
            ]);

        // Verify 2FA is still enabled
        $this->user->refresh();
        $this->assertTrue($this->user->two_factor_enabled);
    }

    /** @test */
    public function it_generates_exactly_10_recovery_codes()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/enable');

        $response->assertStatus(200);
        
        $recoveryCodes = $response->json('recovery_codes');
        $this->assertCount(10, $recoveryCodes);
        
        // Verify each code is in format XXXX-XXXX
        foreach ($recoveryCodes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        }
    }

    /** @test */
    public function it_encrypts_secret_and_recovery_codes_in_database()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/enable');

        $this->user->refresh();
        
        // Verify values are encrypted (not plain text)
        $this->assertNotEquals($response->json('secret'), $this->user->two_factor_secret);
        
        // Verify we can decrypt them
        $decryptedSecret = decrypt($this->user->two_factor_secret);
        $this->assertEquals($response->json('secret'), $decryptedSecret);
    }

    /** @test */
    public function it_requires_authentication_for_all_2fa_endpoints()
    {
        $endpoints = [
            ['method' => 'get', 'url' => '/api/me/2fa/status'],
            ['method' => 'post', 'url' => '/api/me/2fa/enable'],
            ['method' => 'post', 'url' => '/api/me/2fa/verify'],
            ['method' => 'post', 'url' => '/api/me/2fa/disable'],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['url']);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_validates_verification_code_format()
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/me/2fa/enable');

        // Test invalid code formats
        $invalidCodes = ['12345', '1234567', 'abcdef', ''];

        foreach ($invalidCodes as $invalidCode) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/me/2fa/verify', [
                    'code' => $invalidCode,
                ]);

            $response->assertStatus(422);
        }
    }
}
