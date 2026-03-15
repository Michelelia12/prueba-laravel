<?php

namespace Tests\Feature\Models;

use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_belongs_to_lesson(): void
    {
        $lesson = Lesson::factory()->create();
        $video = Video::factory()->create(['lesson_id' => $lesson->id]);

        $this::assertInstanceOf(Lesson::class, $video->lesson);
        $this::assertEquals($lesson->id, $video->lesson->id);
    }
}
