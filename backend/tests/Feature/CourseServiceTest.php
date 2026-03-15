<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Rating;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CourseService $courseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courseService = app(CourseService::class);
    }

    public function test_get_average_rating_calculates_correctly(): void
    {
        $course = Course::factory()->create();
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 4]);
        Rating::factory()->create(['ratable_id' => $course->id, 'ratable_type' => Course::class, 'score' => 5]);

        $average = $this->courseService->getAverageRating($course);

        $this::assertEquals(4.5, $average);
    }

    public function test_get_average_rating_returns_zero_when_no_ratings(): void
    {
        $course = Course::factory()->create();

        $average = $this->courseService->getAverageRating($course);

        $this::assertEquals(0.0, $average);
    }
}
