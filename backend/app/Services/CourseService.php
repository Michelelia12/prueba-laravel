<?php

namespace App\Services;

use App\Models\Course;

class CourseService
{
    /**
     * Calculate the average rating for a course
     */
    public function getAverageRating(Course $course): float
    {
        /** @phpstan-ignore-next-line */
        $avg = $course->ratings()->avg('score');

        return is_numeric($avg) ? (float) $avg : 0.0;
    }

    /**
     * Get course statistics including average rating and total comments
     */
    public function getCourseStats(Course $course): array
    {
        return [
            'average_rating' => $this->getAverageRating($course),
            'total_ratings' => $course->ratings()::count(),
            'total_comments' => $course->comments()::count(),
            'total_lessons' => $course->lessons()::count(),
        ];
    }
}
