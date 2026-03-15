<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'score' => $this->faker->numberBetween(1, 5),
            'user_id' => User::factory(),
            'ratable_id' => Course::factory(),
            'ratable_type' => Course::class,
        ];
    }
}
