<?php

namespace Tests\Feature\Routes;

use App\Models\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorApiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_cannot_update_instructor_with_duplicate_email(): void
    {
        $instructor1 = Instructor::factory()->create(['email' => 'instructor1@example.com']);
        $instructor2 = Instructor::factory()->create(['email' => 'instructor2@example.com']);

        $data = ['email' => 'instructor1@example.com']; // duplicate

        $response = $this->putJson("/api/v1/instructors/{$instructor2->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
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
}
