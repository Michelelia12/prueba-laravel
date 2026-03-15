<?php

namespace Tests\Feature\Models;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Comment;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Rating;
use App\Models\User;
use Assert\Assertion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_favorite_a_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $user->favoriteCourses()->attach($course);

        Assertion::isInstanceOf($user->favoriteCourses->first(), Course::class);
        $this::assertCount(1, $user->favoriteCourses);
        $this::assertEquals($course->id, $user->favoriteCourses->first()->id);
    }

    public function test_user_can_favorite_multiple_courses(): void
    {
        $user = User::factory()->create();
        $courses = Course::factory()->count(3)->create();

        $user->favoriteCourses()->attach($courses);

        $this::assertCount(3, $user->favoriteCourses);
        $this::assertEquals($courses->pluck('id')->sort(), $user->favoriteCourses->pluck('id')->sort());
    }

    public function test_user_can_comment_on_a_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $course->id,
            'commentable_type' => Course::class,
        ]);

        Assertion::isInstanceOf($comment->user, User::class);
        Assertion::isInstanceOf($course->comments->first(), Comment::class);
        $this::assertCount(1, $course->comments);
        $this::assertEquals($comment->id, $course->comments->first()->id);
        $this::assertEquals($user->id, $comment->user->id);
    }

    public function test_user_can_rate_a_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $rating = Rating::factory()->create([
            'user_id' => $user->id,
            'ratable_id' => $course->id,
            'ratable_type' => Course::class,
            'score' => 5,
        ]);

        Assertion::isInstanceOf($course->ratings->first(), Rating::class);
        Assertion::isInstanceOf($rating->user, User::class);
        $this::assertCount(1, $course->ratings);
        $this::assertCount(1, $user->ratings);
        $this::assertEquals($rating->id, $course->ratings->first()->id);
        $this::assertEquals($user->id, $rating->user->id);
        $this::assertEquals(5, $rating->score);
    }

    public function test_user_can_comment_on_an_instructor(): void
    {
        $user = User::factory()->create();
        $instructor = Instructor::factory()->create();

        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $instructor->id,
            'commentable_type' => Instructor::class,
        ]);

        Assertion::isInstanceOf($instructor->comments->first(), Comment::class);
        Assertion::isInstanceOf($comment->user, User::class);
        $this::assertEquals(count($user->comments), 1);
        $this::assertCount(1, $instructor->comments);
        $this::assertEquals($comment->id, $instructor->comments->first()->id);
        $this::assertEquals($user->id, $comment->user->id);
    }

    public function test_user_can_rate_an_instructor(): void
    {
        $user = User::factory()->create();
        $instructor = Instructor::factory()->create();

        $rating = Rating::factory()->create([
            'user_id' => $user->id,
            'ratable_id' => $instructor->id,
            'ratable_type' => Instructor::class,
            'score' => 4,
        ]);

        Assertion::isInstanceOf($instructor->ratings->first(), Rating::class);
        Assertion::isInstanceOf($rating->user, User::class);
        $this::assertCount(1, $instructor->ratings);
        $this::assertEquals($rating->id, $instructor->ratings->first()->id);
        $this::assertEquals($user->id, $rating->user->id);
        $this::assertEquals(4, $rating->score);
    }

    public function test_create_user(): void
    {
        $user = User::factory()->create();
        $this::assertInstanceOf(User::class, $user);
    }

    public function test_user_has_many_favorite_courses(): void
    {
        $user = User::factory()->create();
        $courses = Course::factory()->count(3)->create();

        $user->favoriteCourses()->attach($courses);

        $this::assertCount(3, $user->favoriteCourses);
        $this::assertEquals($courses->pluck('id')->sort(), $user->favoriteCourses->pluck('id')->sort());
    }

    public function test_user_casts_work(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this::assertInstanceOf(Carbon::class, $user->email_verified_at);
    }
}
