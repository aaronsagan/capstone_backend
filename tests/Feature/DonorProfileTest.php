<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Donation;
use App\Models\DonorMilestone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->donor = User::factory()->create([
            'role' => 'donor',
            'email_verified_at' => now(),
        ]);
        
        $this->otherDonor = User::factory()->create([
            'role' => 'donor',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function it_can_get_donor_profile()
    {
        $response = $this->getJson("/api/donors/{$this->donor->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar_url',
                    'total_donated',
                    'campaigns_supported_count',
                    'is_owner',
                ],
            ]);
    }

    /** @test */
    public function it_masks_email_for_non_owners()
    {
        $response = $this->getJson("/api/donors/{$this->donor->id}");

        $email = $response->json('data.email');
        $this->assertStringContainsString('***', $email);
    }

    /** @test */
    public function it_shows_full_email_to_owner()
    {
        $response = $this->actingAs($this->donor, 'sanctum')
            ->getJson("/api/donors/{$this->donor->id}");

        $email = $response->json('data.email');
        $this->assertEquals($this->donor->email, $email);
    }

    /** @test */
    public function it_can_get_donor_activity()
    {
        $response = $this->getJson("/api/donors/{$this->donor->id}/activity");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'has_more',
                ],
            ]);
    }

    /** @test */
    public function it_can_get_donor_milestones()
    {
        // Create a milestone
        DonorMilestone::create([
            'donor_id' => $this->donor->id,
            'key' => 'first_donation',
            'title' => 'First Donation',
            'description' => 'Made your first donation',
            'icon' => 'Heart',
        ]);

        $response = $this->getJson("/api/donors/{$this->donor->id}/milestones");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'title',
                        'description',
                        'icon',
                        'is_achieved',
                        'progress',
                    ],
                ],
            ]);
    }

    /** @test */
    public function owner_can_update_profile()
    {
        $response = $this->actingAs($this->donor, 'sanctum')
            ->putJson("/api/donors/{$this->donor->id}/profile", [
                'bio' => 'New bio text',
                'address' => 'New location',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);
    }

    /** @test */
    public function non_owner_cannot_update_profile()
    {
        $response = $this->actingAs($this->otherDonor, 'sanctum')
            ->putJson("/api/donors/{$this->donor->id}/profile", [
                'bio' => 'Hacked bio',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized. You can only update your own profile.',
            ]);
    }

    /** @test */
    public function guest_cannot_update_profile()
    {
        $response = $this->putJson("/api/donors/{$this->donor->id}/profile", [
            'bio' => 'Guest bio',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_profile_update_data()
    {
        $response = $this->actingAs($this->donor, 'sanctum')
            ->putJson("/api/donors/{$this->donor->id}/profile", [
                'bio' => str_repeat('a', 1001), // Exceeds max length
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bio']);
    }

    /** @test */
    public function it_returns_404_for_non_existent_donor()
    {
        $response = $this->getJson("/api/donors/99999");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_only_shows_verified_donors()
    {
        $unverifiedDonor = User::factory()->create([
            'role' => 'donor',
            'email_verified_at' => null,
        ]);

        $response = $this->getJson("/api/donors/{$unverifiedDonor->id}");

        $response->assertStatus(404);
    }
}
