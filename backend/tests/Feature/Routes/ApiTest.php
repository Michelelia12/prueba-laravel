<?php

namespace Tests\Feature\Routes;

use App\Models\Course;
use App\Models\Instructor;
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

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('api/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }
}
