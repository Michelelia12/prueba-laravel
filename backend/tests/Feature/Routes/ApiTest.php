<?php

namespace Tests\Feature\Routes;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_course_with_valid_data(): void
    {
        $instructor = Instructor::factory()->create();

        $data = [
            'title' => 'Test Course',
            'description' => 'This is a test course description.',
            'instructor_id' => $instructor->id,
            'price' => 99.99,
            'level' => 'beginner',
        ];

        $response = $this->postJson('/api/v1/courses', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'instructor_id',
                'price',
                'level',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('courses', $data);
    }

    public function test_cannot_create_a_course_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/courses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'instructor_id']);
    }

    public function test_cannot_create_a_course_with_invalid_data_types(): void
    {
        $data = [
            'title' => 123, // should be string
            'description' => [], // should be string
            'instructor_id' => 'not-an-id', // should be int
            'price' => 'not-a-number', // should be numeric
            'level' => 'invalid-level', // should be one of the enum
        ];

        $response = $this->postJson('/api/v1/courses', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'instructor_id', 'price', 'level']);
    }

    public function test_can_retrieve_a_single_course(): void
    {
        $course = Course::factory()->create();

        $response = $this->getJson("/api/v1/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'course' => [
                    'id',
                    'title',
                    'instructor',
                    'lessons',
                    'ratings',
                    'comments',
                ],
                'average_rating',
            ]);
    }

    public function test_returns_404_when_course_not_found(): void
    {
        $response = $this->getJson('/api/v1/courses/999');

        $response->assertStatus(404);
    }

    public function test_can_list_all_courses(): void
    {
        Course::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/courses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'instructor',
                    ],
                ],
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_can_update_a_course_with_valid_data(): void
    {
        $course = Course::factory()->create();
        $newInstructor = Instructor::factory()->create();

        $data = [
            'title' => 'Updated Course Title',
            'description' => 'Updated description.',
            'instructor_id' => $newInstructor->id,
            'price' => 149.99,
            'level' => 'intermediate',
        ];

        $response = $this->putJson("/api/v1/courses/{$course->id}", $data);

        $response->assertStatus(200)
            ->assertJson($data);

        $this->assertDatabaseHas('courses', $data);
    }

    public function test_cannot_update_a_course_with_invalid_data(): void
    {
        $course = Course::factory()->create();

        $data = [
            'title' => '', // empty string
            'description' => 123, // not string
            'instructor_id' => 999, // non-existent
            'price' => -10, // negative
            'level' => 'expert', // invalid
        ];

        $response = $this->putJson("/api/v1/courses/{$course->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'instructor_id', 'price', 'level']);
    }

    public function test_returns_404_when_updating_nonexistent_course(): void
    {
        $data = [
            'title' => 'Updated Title',
        ];

        $response = $this->putJson('/api/v1/courses/999', $data);

        $response->assertStatus(404);
    }

    public function test_can_delete_a_course(): void
    {
        $course = Course::factory()->create();

        $response = $this->deleteJson("/api/v1/courses/{$course->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_course(): void
    {
        $response = $this->deleteJson('/api/v1/courses/999');

        $response->assertStatus(404);
    }

    public function test_course_response_includes_instructors_list(): void
    {
        $course = Course::factory()->create();

        $response = $this->getJson("/api/v1/courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'course' => [
                    'instructor' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ]);
    }

    public function test_user_can_add_course_to_favorites(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/courses/{$course->id}/favorite");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Course added to favorites']);

        $this->assertDatabaseHas('course_user_favorites', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_user_cannot_add_same_course_to_favorites_twice(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $user->favoriteCourses()->attach($course);

        $response = $this->actingAs($user)->postJson("/api/v1/courses/{$course->id}/favorite");

        $response->assertStatus(409)
            ->assertJson(['message' => 'Course already favorited']);
    }

    public function test_user_can_remove_course_from_favorites(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $user->favoriteCourses()->attach($course);

        $response = $this->actingAs($user)->deleteJson("/api/v1/courses/{$course->id}/favorite");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Course removed from favorites']);

        $this->assertDatabaseMissing('course_user_favorites', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_user_not_authenticated_cannot_remove_course_from_favorites(): void
    {
        $course = Course::factory()->create();

        $response = $this->deleteJson("/api/v1/courses/{$course->id}/favorite");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_add_favorite(): void
    {
        $course = Course::factory()->create();

        $response = $this->postJson("/api/v1/courses/{$course->id}/favorite");

        $response->assertStatus(401);
    }

    public function test_instructors_list_is_paginated(): void
    {
        Instructor::factory()->count(30)->create();

        $response = $this->getJson('/api/v1/instructors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'bio',
                    ],
                ],
                'path',
                'per_page',
                'next_page_url',
                'current_page',
            ]);
    }

    public function test_instructors_query_uses_pagination_not_full_load(): void
    {
        Instructor::factory()->count(100)->create();

        $response = $this->getJson('/api/v1/instructors?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    public function test_instructors_endpoint_response_time_is_acceptable_with_large_dataset(): void
    {
        Instructor::factory()->count(1000)->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/instructors?per_page=50');

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);

        // Assert response time is less than 1 second
        $this::assertLessThan(1.0, $responseTime, 'Response time should be less than 1 second');
    }

    public function test_returns_instructors_with_default_pagination(): void
    {
        Instructor::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/instructors/all');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'bio'],
                ],
                'path',
                'per_page',
                'next_cursor',
                'next_page_url',
                'prev_cursor',
                'prev_page_url',
            ]);
    }

    public function test_can_list_all_instructors(): void
    {
        Instructor::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/instructors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'courses',
                    ],
                ],
                'current_page',
                'per_page',
                'total',
                'last_page',
                'from',
                'to',
            ]);
    }

    public function test_can_create_an_instructor_with_valid_data(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Experienced instructor',
            'avatar' => 'https://example.com/avatar.jpg',
        ];

        $response = $this->postJson('/api/v1/instructors', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'bio',
                'avatar',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('instructors', $data);
    }

    public function test_cannot_create_an_instructor_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/instructors', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_cannot_create_an_instructor_with_invalid_data(): void
    {
        $data = [
            'name' => '', // empty
            'email' => 'invalid-email', // invalid format
            'bio' => 123, // not string
        ];

        $response = $this->postJson('/api/v1/instructors', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'bio']);
    }

    public function test_cannot_create_instructor_with_duplicate_email(): void
    {
        Instructor::factory()->create(['email' => 'john@example.com']);

        $data = [
            'name' => 'Jane Doe',
            'email' => 'john@example.com', // duplicate
        ];

        $response = $this->postJson('/api/v1/instructors', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_retrieve_a_single_instructor(): void
    {
        $instructor = Instructor::factory()->create();

        $response = $this->getJson("/api/v1/instructors/{$instructor->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'instructor' => [
                    'id',
                    'name',
                    'email',
                    'bio',
                    'avatar',
                    'courses',
                    'ratings',
                    'comments',
                ],
                'average_rating',
            ]);
    }

    public function test_returns_404_when_instructor_not_found(): void
    {
        $response = $this->getJson('/api/v1/instructors/999');

        $response->assertStatus(404);
    }

    public function test_can_update_an_instructor_with_valid_data(): void
    {
        $instructor = Instructor::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'bio' => 'Updated bio',
        ];

        $response = $this->putJson("/api/v1/instructors/{$instructor->id}", $data);

        $response->assertStatus(200)
            ->assertJson($data);

        $this->assertDatabaseHas('instructors', $data);
    }

    public function test_cannot_update_instructor_with_invalid_data(): void
    {
        $instructor = Instructor::factory()->create();

        $data = [
            'name' => '',
            'email' => 'invalid',
        ];

        $response = $this->putJson("/api/v1/instructors/{$instructor->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_returns_404_when_updating_nonexistent_instructor(): void
    {
        $data = ['name' => 'Updated'];

        $response = $this->putJson('/api/v1/instructors/999', $data);

        $response->assertStatus(404);
    }

    public function test_can_delete_an_instructor(): void
    {
        $instructor = Instructor::factory()->create();

        $response = $this->deleteJson("/api/v1/instructors/{$instructor->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('instructors', ['id' => $instructor->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_instructor(): void
    {
        $response = $this->deleteJson('/api/v1/instructors/999');

        $response->assertStatus(404);
    }

    public function test_can_list_all_lessons(): void
    {
        Lesson::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/lessons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'course_id',
                        'sequence',
                    ],
                ],
                'current_page',
                'per_page',
                'total',
                'last_page',
                'from',
                'to',
            ]);
    }

    public function test_can_create_a_lesson_with_valid_data(): void
    {
        $course = Course::factory()->create();

        $data = [
            'title' => 'Introduction Lesson',
            'description' => 'This is the first lesson',
            'course_id' => $course->id,
            'sequence' => 1,
        ];

        $response = $this->postJson('/api/v1/lessons', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'course_id',
                'sequence',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('lessons', $data);
    }

    public function test_cannot_create_a_lesson_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/lessons', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'course_id']);
    }

    public function test_cannot_create_a_lesson_with_invalid_data(): void
    {
        $data = [
            'title' => 123, // not string
            'course_id' => 'invalid', // not exists
            'sequence' => 'not-number', // not integer
        ];

        $response = $this->postJson('/api/v1/lessons', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'course_id', 'sequence']);
    }

    public function test_can_retrieve_a_single_lesson(): void
    {
        $lesson = Lesson::factory()->create();

        $response = $this->getJson("/api/v1/lessons/{$lesson->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'course_id',
                'sequence',
                'course',
                'video',
            ]);
    }

    public function test_returns_404_when_lesson_not_found(): void
    {
        $response = $this->getJson('/api/v1/lessons/999');

        $response->assertStatus(404);
    }

    public function test_can_update_a_lesson_with_valid_data(): void
    {
        $lesson = Lesson::factory()->create();
        $newCourse = Course::factory()->create();

        $data = [
            'title' => 'Updated Lesson',
            'description' => 'Updated description',
            'course_id' => $newCourse->id,
            'sequence' => 2,
        ];

        $response = $this->putJson("/api/v1/lessons/{$lesson->id}", $data);

        $response->assertStatus(200)
            ->assertJson($data);

        $this->assertDatabaseHas('lessons', $data);
    }

    public function test_cannot_update_lesson_with_invalid_data(): void
    {
        $lesson = Lesson::factory()->create();

        $data = [
            'title' => '',
            'course_id' => 999, // non-existent
        ];

        $response = $this->putJson("/api/v1/lessons/{$lesson->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'course_id']);
    }

    public function test_returns_404_when_updating_nonexistent_lesson(): void
    {
        $data = ['title' => 'Updated'];

        $response = $this->putJson('/api/v1/lessons/999', $data);

        $response->assertStatus(404);
    }

    public function test_can_delete_a_lesson(): void
    {
        $lesson = Lesson::factory()->create();

        $response = $this->deleteJson("/api/v1/lessons/{$lesson->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_returns_404_when_deleting_nonexistent_lesson(): void
    {
        $response = $this->deleteJson('/api/v1/lessons/999');

        $response->assertStatus(404);
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('api/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_can_filter_lessons_by_course(): void
    {
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        Lesson::factory()->count(3)->create(['course_id' => $course1->id]);
        Lesson::factory()->count(2)->create(['course_id' => $course2->id]);

        $response = $this->getJson('/api/v1/lessons?course_id='.$course1->id);

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_instructors_list_uses_cursor_pagination(): void
    {
        Instructor::factory()->count(10)->create();

        // First request to get cursor
        $response1 = $this->getJson('/api/v1/instructors?per_page=5');
        $response1->assertStatus(200);

        $nextCursor = $response1->json('next_cursor');

        // Second request with cursor
        $response2 = $this->getJson('/api/v1/instructors?cursor='.$nextCursor);
        $response2->assertStatus(200);
    }
}
