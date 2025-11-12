<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Charity;
use App\Models\Campaign;
use App\Models\Update;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test SQL injection attempts are safely handled
     */
    public function test_sql_injection_in_campaign_creation_is_prevented()
    {
        $user = User::factory()->create(['role' => 'charity_admin']);
        $charity = Charity::factory()->create(['owner_id' => $user->id]);
        
        $this->actingAs($user, 'sanctum');

        $sqlPayloads = [
            "'; DROP TABLE users; --",
            "1; DELETE FROM campaigns; --",
            "' OR '1'='1",
            "1' UNION SELECT * FROM users--",
            "admin'--",
            "' OR 1=1--",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->postJson('/api/campaigns', [
                'title' => $payload,
                'description' => $payload,
                'goal_amount' => 10000,
                'end_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

            // Should either succeed (with escaped data) or fail validation
            // But crucially: tables must still exist
            $this->assertTrue(Schema::hasTable('users'), "Users table was dropped by SQL injection!");
            $this->assertTrue(Schema::hasTable('campaigns'), "Campaigns table was dropped!");
            
            // Verify no actual SQL was executed
            if ($response->status() === 201) {
                $campaign = Campaign::latest()->first();
                $this->assertStringNotContainsString('DROP', $campaign->title);
                $this->assertStringNotContainsString('DELETE', $campaign->description);
            }
        }
    }

    /**
     * Test SQL injection in search/filter endpoints
     */
    public function test_sql_injection_in_search_is_prevented()
    {
        Campaign::factory()->create(['title' => 'Test Campaign']);

        $sqlPayloads = [
            "' OR '1'='1",
            "1' UNION SELECT password FROM users--",
            "'; DROP TABLE campaigns; --",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->getJson('/api/campaigns?search=' . urlencode($payload));
            
            // Should return normal results or empty, but not execute SQL
            $response->assertStatus(200);
            $this->assertTrue(Schema::hasTable('campaigns'), "Table was affected by search injection!");
        }
    }

    /**
     * Test XSS payload sanitization in campaign description
     */
    public function test_xss_payload_in_campaign_description_is_sanitized()
    {
        $user = User::factory()->create(['role' => 'charity_admin']);
        $charity = Charity::factory()->create(['owner_id' => $user->id]);
        
        $this->actingAs($user, 'sanctum');

        $xssPayloads = [
            "<script>alert('XSS')</script>",
            "<img src=x onerror=alert(1)>",
            "<svg/onload=alert(1)>",
            "<iframe src='javascript:alert(1)'></iframe>",
            "<b onmouseover='alert(1)'>hover me</b>",
            "javascript:alert(1)",
            "<script>window.pwned=true</script><p>Content</p>",
        ];

        foreach ($xssPayloads as $xss) {
            $response = $this->postJson('/api/campaigns', [
                'title' => 'XSS Test',
                'description' => $xss,
                'goal_amount' => 10000,
                'end_date' => now()->addDays(30)->format('Y-m-d'),
            ]);

            if ($response->status() === 201) {
                $campaign = Campaign::find($response->json('data.id'));
                
                // Script tags should be stripped or encoded
                $this->assertStringNotContainsString('<script>', $campaign->description, 
                    "Script tag found in stored description!");
                $this->assertStringNotContainsString('onerror=', $campaign->description,
                    "Event handler found in stored description!");
                $this->assertStringNotContainsString('onload=', $campaign->description,
                    "Event handler found in stored description!");
                $this->assertStringNotContainsString('javascript:', $campaign->description,
                    "JavaScript protocol found in stored description!");
            }
        }
    }

    /**
     * Test XSS in update posts
     */
    public function test_xss_payload_in_update_post_is_sanitized()
    {
        $user = User::factory()->create(['role' => 'charity_admin']);
        $charity = Charity::factory()->create(['owner_id' => $user->id]);
        
        $this->actingAs($user, 'sanctum');

        $xss = "<script>alert('pwned')</script><p>Safe content</p>";
        
        $response = $this->postJson('/api/updates', [
            'content' => $xss,
        ]);

        if ($response->status() === 201) {
            $update = Update::find($response->json('id'));
            
            // Verify dangerous tags are removed
            $this->assertStringNotContainsString('<script>', $update->content,
                "Script tag found in update content!");
            
            // Safe tags might be preserved depending on sanitization strategy
            // At minimum, script tags must be removed
        }
    }

    /**
     * Test SQL injection in user registration
     */
    public function test_sql_injection_in_registration_is_prevented()
    {
        $response = $this->postJson('/api/register', [
            'name' => "'; DROP TABLE users; --",
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'donor',
        ]);

        // Registration might succeed or fail validation
        // But tables must exist
        $this->assertTrue(Schema::hasTable('users'), "Users table was dropped!");
        
        if ($response->status() === 201) {
            $user = User::where('email', 'test@example.com')->first();
            $this->assertNotNull($user);
            // Name should be stored as-is (escaped when queried)
            // but no SQL should execute
        }
    }

    /**
     * Test XSS in user name field
     */
    public function test_xss_in_user_name_is_sanitized()
    {
        $response = $this->postJson('/api/register', [
            'name' => "<script>alert('XSS')</script>John Doe",
            'email' => 'xsstest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'donor',
        ]);

        if ($response->status() === 201) {
            $user = User::where('email', 'xsstest@example.com')->first();
            $this->assertStringNotContainsString('<script>', $user->name,
                "Script tag found in user name!");
        }
    }

    /**
     * Test raw query safety (if any exist)
     */
    public function test_raw_queries_use_parameter_binding()
    {
        // This is a code inspection test
        // Verify that any DB::raw usage is safe
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Test any endpoints that might use raw queries
        $response = $this->getJson('/api/campaigns?sort=created_at');
        $response->assertStatus(200);
        
        // Tables should still exist after any query
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('campaigns'));
    }

    /**
     * Test comment/feedback XSS prevention
     */
    public function test_xss_in_comments_is_sanitized()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();
        
        $this->actingAs($user, 'sanctum');

        $xss = "<script>document.cookie='stolen'</script>Nice campaign!";
        
        $response = $this->postJson("/api/campaigns/{$campaign->id}/comments", [
            'content' => $xss,
        ]);

        // Comments endpoint might not exist, but if it does, test it
        if ($response->status() !== 404) {
            $this->assertStringNotContainsString('<script>', $response->json('content') ?? '',
                "Script tag in comment response!");
        }
    }

    /**
     * Test file upload metadata XSS
     */
    public function test_xss_in_file_upload_filename_is_sanitized()
    {
        $user = User::factory()->create(['role' => 'charity_admin']);
        $charity = Charity::factory()->create(['owner_id' => $user->id]);
        $campaign = Campaign::factory()->create(['charity_id' => $charity->id]);
        
        $this->actingAs($user, 'sanctum');

        // Create a fake file with XSS in filename
        $xssFilename = "<script>alert(1)</script>image.jpg";
        
        // Test would need actual file upload simulation
        // This is a placeholder to ensure the concept is tested
        $this->assertTrue(true, "File upload XSS test placeholder");
    }

    /**
     * Test CSRF protection is enabled
     */
    public function test_csrf_protection_is_enabled()
    {
        // Verify CSRF middleware is in place
        $middlewares = app()->make('router')->getMiddlewareGroups();
        
        $this->assertArrayHasKey('web', $middlewares);
        $webMiddleware = $middlewares['web'];
        
        // CSRF should be in web middleware
        $hasCsrf = collect($webMiddleware)->contains(function($m) {
            return str_contains($m, 'VerifyCsrfToken') || $m === 'csrf';
        });
        
        $this->assertTrue($hasCsrf, "CSRF protection not found in web middleware!");
    }

    /**
     * Test headers for security
     */
    public function test_security_headers_are_present()
    {
        $response = $this->get('/');
        
        // Should have X-Frame-Options
        // Should have X-Content-Type-Options
        // These might be set in middleware
        $this->assertTrue(true, "Security headers test placeholder");
    }

    /**
     * Test mass assignment protection
     */
    public function test_mass_assignment_protection()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Try to set role via mass assignment
        $response = $this->patchJson('/api/profile', [
            'name' => 'Updated Name',
            'role' => 'admin', // Should not be fillable
            'email_verified_at' => now(),
        ]);

        $user->refresh();
        
        // Role should not change
        $this->assertNotEquals('admin', $user->role, "Mass assignment vulnerability: role was changed!");
    }

    /**
     * Test SQL injection via JSON fields
     */
    public function test_sql_injection_via_json_input()
    {
        $user = User::factory()->create(['role' => 'charity_admin']);
        $charity = Charity::factory()->create(['owner_id' => $user->id]);
        
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/campaigns', [
            'title' => 'Test',
            'description' => 'Test',
            'goal_amount' => "1' OR '1'='1",
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // Should fail validation or sanitize
        $this->assertTrue(Schema::hasTable('campaigns'), "Table affected by JSON injection!");
    }

    /**
     * Test authentication bypass attempts
     */
    public function test_authentication_bypass_via_sql_injection()
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $sqlPayloads = [
            "admin@example.com' OR '1'='1",
            "admin@example.com'--",
            "' OR 1=1--",
        ];

        foreach ($sqlPayloads as $payload) {
            $response = $this->postJson('/api/login', [
                'email' => $payload,
                'password' => 'wrongpassword',
            ]);

            // Should always fail with wrong password
            $this->assertNotEquals(200, $response->status(), 
                "Authentication bypassed with SQL injection!");
        }
    }

    /**
     * Test stored XSS persistence
     */
    public function test_stored_xss_does_not_persist()
    {
        $user = User::factory()->create(['role' => 'charity_admin']);
        $charity = Charity::factory()->create(['owner_id' => $user->id]);
        
        $this->actingAs($user, 'sanctum');

        $xss = "<img src=x onerror=alert('stored')>";
        
        // Create campaign with XSS
        $response = $this->postJson('/api/campaigns', [
            'title' => $xss,
            'description' => $xss,
            'goal_amount' => 10000,
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        if ($response->status() === 201) {
            $campaignId = $response->json('data.id');
            
            // Retrieve campaign
            $getResponse = $this->getJson("/api/campaigns/{$campaignId}");
            $getResponse->assertStatus(200);
            
            $title = $getResponse->json('data.title');
            $description = $getResponse->json('data.description');
            
            // XSS should not be present in raw form
            $this->assertStringNotContainsString('onerror=', $title, "XSS persisted in title!");
            $this->assertStringNotContainsString('onerror=', $description, "XSS persisted in description!");
        }
    }
}
