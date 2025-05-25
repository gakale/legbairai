<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Gbairai\Core\Models\Follow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserFollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_unfollow_another_user_and_counters_are_correct(): void
    {
        // Créez deux utilisateurs
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Authentifiez-vous en tant que userA
        Sanctum::actingAs($userA);

        // 1. userA suit userB
        $response = $this->postJson("/api/v1/users/{$userB->id}/follow");
        $response->assertStatus(200);
        $this->assertDatabaseHas('follows', [
            'follower_user_id' => $userA->id,
            'following_user_id' => $userB->id,
        ]);

        // 2. Vérifiez les compteurs et le flag sur userB
        $response = $this->getJson("/api/v1/users/{$userB->id}");
        $response->assertStatus(200)
            ->assertJsonFragment([
                'followers_count' => 1,
                'is_followed_by_current_user' => true,
            ]);

        // 3. Vérifiez le compteur de followings sur userA
        $response = $this->getJson("/api/v1/users/{$userA->id}");
        $response->assertStatus(200)
            ->assertJsonFragment([
                'followings_count' => 1,
            ]);

        // 4. userA unfollow userB
        $response = $this->deleteJson("/api/v1/users/{$userB->id}/unfollow");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('follows', [
            'follower_user_id' => $userA->id,
            'following_user_id' => $userB->id,
        ]);
    }
}
