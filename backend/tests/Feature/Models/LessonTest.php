<?php

namespace Tests\Feature\Models;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_has_one_video(): void
    {
        $lesson = Lesson::factory()->create();
        $video = Video::factory()->create(['lesson_id' => $lesson->id]);

        $this::assertInstanceOf(Video::class, $lesson->video);
        $this::assertInstanceOf(Course::class, $lesson->course);
        $this::assertEquals($video->id, $lesson->video->id);
    }
}
