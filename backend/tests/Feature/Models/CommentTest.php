<?php

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $course->id,
            'commentable_type' => Course::class,
        ]);

        $this::assertInstanceOf(User::class, $comment->user);
        $this::assertEquals($user->id, $comment->user->id);
    }

    public function test_comment_morphs_to_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $course->id,
            'commentable_type' => Course::class,
        ]);

        $this::assertInstanceOf(Course::class, $comment->commentable);
        $this::assertEquals($course->id, $comment->commentable->id);
    }
}
