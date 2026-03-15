<?php

namespace Tests\Feature\Models;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Lesson;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_belongs_to_instructor(): void
    {
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);

        $this::assertInstanceOf(Instructor::class, $course->instructor);
        $this::assertEquals($instructor->id, $course->instructor->id);
    }

    public function test_course_has_many_lessons(): void
    {
        $course = Course::factory()->create();
        $lessons = Lesson::factory()->count(5)->create(['course_id' => $course->id]);

        $this::assertCount(5, $course->lessons);
        $this::assertEquals($lessons->pluck('id')->sort(), $course->lessons->pluck('id')->sort());
    }

    public function test_course_can_be_favorited_by_multiple_users(): void
    {
        $course = Course::factory()->create();
        $users = User::factory()->count(4)->create();

        $course->favoritedByUsers()->attach($users);

        $this::assertCount(4, $course->favoritedByUsers);
        $this::assertEquals($users->pluck('id')->sort(), $course->favoritedByUsers->pluck('id')->sort());
    }

    public function test_average_rating_is_calculated_correctly_for_a_course(): void
    {
        $course = Course::factory()->create();
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 4]);
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 5]);
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 3]);

        $average = $course->averageRating();

        $this::assertEquals(4.0, $average);
    }

    public function test_average_rating_is_zero_when_no_ratings_exist(): void
    {
        $course = Course::factory()->create();

        $average = $course->averageRating();

        $this::assertEquals(0.0, $average);
    }

    public function test_average_rating_updates_when_new_rating_is_added(): void
    {
        $course = Course::factory()->create();
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 4]);

        $average = $course->averageRating();
        $this::assertEquals(4.0, $average);

        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 2]);

        // Refresh the course to get updated average
        $course->refresh();
        $average = $course->averageRating();
        $this::assertEquals(3.0, $average);
    }

    public function test_average_rating_is_rounded_to_expected_precision(): void
    {
        $course = Course::factory()->create();
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 4]);
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 5]);

        $average = $course->averageRating();

        $this::assertEquals(4.5, $average);
    }

    public function test_average_rating_handles_single_rating(): void
    {
        $course = Course::factory()->create();
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 5]);

        $average = $course->averageRating();

        $this::assertEquals(5.0, $average);
    }
}
