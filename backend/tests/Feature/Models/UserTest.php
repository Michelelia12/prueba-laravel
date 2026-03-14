<?php

namespace Tests\Feature\Models;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(User::class, $user);
    }
}
