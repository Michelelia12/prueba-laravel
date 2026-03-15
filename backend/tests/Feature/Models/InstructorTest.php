<?php

namespace Tests\Feature\Models;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_has_many_courses(): void
    {
        $instructor = Instructor::factory()->create();
        $courses = Course::factory()->count(3)->create(['instructor_id' => $instructor->id]);

        $this::assertCount(3, $instructor->courses);
        $this::assertEquals($courses->pluck('id')->sort(), $instructor->courses->pluck('id')->sort());
    }

    public function test_instructor_average_rating_is_calculated_correctly(): void
    {
        $instructor = Instructor::factory()->create();
        Rating::factory()->create(['ratable_id' => $instructor->id, 'ratable_type' => Instructor::class, 'score' => 4]);
        Rating::factory()->create(['ratable_id' => $instructor->id, 'ratable_type' => Instructor::class, 'score' => 5]);
        Rating::factory()->create(['ratable_id' => $instructor->id, 'ratable_type' => Instructor::class, 'score' => 3]);

        $average = $instructor->averageRating();

        $this::assertEquals(4.0, $average);
    }

    public function test_instructor_average_rating_is_zero_when_no_ratings_exist(): void
    {
        $instructor = Instructor::factory()->create();

        $average = $instructor->averageRating();

        $this::assertEquals(0.0, $average);
    }
}
