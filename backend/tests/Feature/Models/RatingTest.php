<?php

namespace Tests\Feature\Models;

use App\Models\Course;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $user->id,
            'ratable_id' => $course->id,
            'ratable_type' => Course::class,
        ]);

        $this::assertInstanceOf(User::class, $rating->user);
        $this::assertEquals($user->id, $rating->user->id);
    }

    public function test_rating_morphs_to_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $rating = Rating::factory()->create([
            'user_id' => $user->id,
            'ratable_id' => $course->id,
            'ratable_type' => Course::class,
        ]);

        $this::assertInstanceOf(Course::class, $rating->ratable);
        $this::assertEquals($course->id, $rating->ratable->id);
    }
}
