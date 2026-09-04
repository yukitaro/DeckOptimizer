<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Deck;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_cannot_access_admin_routes()
    {
        $user = User::factory()->create(['role' => 'User']);

        $this->actingAs($user)
             ->get('/admin/dashboard')
             ->assertForbidden();
    }

    /** @test */
    public function poweruser_can_access_scraper_feature()
    {
        $user = User::factory()->create([
            'role' => 'PowerUser',
            'entitlements' => ['scraper' => true],
        ]);

        $this->actingAs($user)
             ->get('/scraper/run')
             ->assertOk();
    }

    /** @test */
    public function admin_can_manage_roles()
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $target = User::factory()->create(['role' => 'User']);

        $this->actingAs($admin)
             ->post("/admin/users/{$target->id}/promote", ['role' => 'PowerUser'])
             ->assertOk();

        $this->assertEquals('PowerUser', $target->fresh()->role);
    }

    /** @test */
    public function shared_user_can_edit_deck()
    {
        $deck = Deck::factory()->create();
        $owner = User::factory()->create();
        $shared = User::factory()->create();

        // simulate sharing logic
        $deck->shareWith($shared);

        $this->actingAs($shared)
             ->put("/decks/{$deck->id}", ['name' => 'Updated'])
             ->assertOk();
    }

    /** @test */
    public function non_shared_user_cannot_edit_deck()
    {
        $deck = Deck::factory()->create();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
             ->put("/decks/{$deck->id}", ['name' => 'Updated'])
             ->assertForbidden();
    }

    /** @test */
    public function user_without_scraper_entitlement_is_denied()
    {
        $user = User::factory()->create([
            'role' => 'PowerUser',
            'entitlements' => ['scraper' => false],
        ]);

        $this->actingAs($user)
             ->get('/scraper/run')
             ->assertForbidden();
    }
}
