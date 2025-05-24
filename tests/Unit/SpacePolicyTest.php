<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Space;
use App\Policies\SpacePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpacePolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_space_when_authorized()
    {
        $user = User::factory()->create();
        $policy = new SpacePolicy();
        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function user_cannot_create_space_when_unauthorized()
    {
        $user = null; // ou un mock User sans droits
        $policy = new SpacePolicy();
        $this->assertFalse($policy->create($user));
    }
}
