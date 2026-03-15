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
}
