<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_cannot_create_space_and_gets_403()
    {
        $response = $this->postJson('/api/spaces', [
            'title' => 'Test Space',
            'type' => 'PUBLIC_FREE',
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function authorized_user_can_create_space()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->postJson('/api/spaces', [
            'title' => 'Test Space',
            'type' => 'PUBLIC_FREE',
        ]);
        $response->assertStatus(201);
    }
}
