<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\UserFactory;
use Gbairai\Core\Actions\Spaces\CreateSpaceAction;
use Gbairai\Core\Enums\SpaceType;
use Gbairai\Core\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaceCreationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_space()
    {
        // 1. Create a test user
        $user = User::factory()->create();
        $this->assertNotNull($user, "User creation failed.");
        echo "User created: ID = {$user->id}, Email = {$user->email}\n";

        // 2. Simulate API login (conceptual) - Handled by using the created user directly

        // 3. Create a space using CreateSpaceAction
        $createSpaceAction = new CreateSpaceAction();
        $spaceData = [
            'title' => 'Test Space',
            'description' => 'This is a test space.',
            'type' => SpaceType::PUBLIC_FREE,
        ];

        $space = $createSpaceAction->execute($user, $spaceData);
        $this->assertNotNull($space, "Space creation failed.");
        echo "Space created: ID = {$space->id}\n";

        // 4. Verify space creation in the database
        $fetchedSpace = Space::find($space->id);
        $this->assertNotNull($fetchedSpace, "Failed to fetch space from database.");
        $this->assertEquals($user->id, $fetchedSpace->host_user_id, "Space host_user_id does not match user ID.");

        echo "Space creation test passed successfully!\n";
    }
}
