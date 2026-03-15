<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    protected CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function index(Request $request): JsonResponse
    {
        $courses = Course::with(['instructor'])
            ->paginate(15);

        return response()->json($courses);
    }

    public function getAllInstructors(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', '50');
        $cursor = $request->query('cursor', null);

        $instructors = Instructor::select('id', 'name', 'email', 'bio')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        return response()->json($instructors);
    }

    /**
     * @codeCoverageIgnore
     */
    public function create(): void
    {
        // API endpoint - not used
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'instructor_id' => 'required|exists:instructors,id',
            'price' => 'nullable|numeric|min:0',
            'level' => 'nullable|in:beginner,intermediate,advanced',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        /** @var array<string, mixed> $validated */
        $course = Course::create($validated);

        return response()->json($course, 201);
    }

    public function show(Course $course): JsonResponse
    {
        $course->load(['instructor', 'lessons.video', 'ratings', 'comments']);

        return response()->json([
            'course' => $course,
            'average_rating' => $this->courseService->getAverageRating($course),
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    public function edit(string $id): void
    {
        // API endpoint - not used
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'instructor_id' => 'sometimes|required|exists:instructors,id',
            'price' => 'nullable|numeric|min:0',
            'level' => 'nullable|in:beginner,intermediate,advanced',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        /** @var array<string, mixed> $validated */
        $course->update($validated);

        return response()->json($course);
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(null, 204);
    }

    public function addFavorite(Request $request, Course $course): JsonResponse
    {
        // Assuming user is authenticated
        $user = $request->user();

        if (is_null($user)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        /* @phpstan-ignore-next-line */
        if ($user->favoriteCourses()->where('course_id', $course->id)->exists()) {
            return response()->json(['message' => 'Course already favorited'], 409);
        }

        $user->favoriteCourses()->attach($course);

        return response()->json(['message' => 'Course added to favorites'], 200);
    }

    public function removeFavorite(Request $request, Course $course): JsonResponse
    {
        // Assuming user is authenticated
        $user = $request->user();

        if (is_null($user)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user->favoriteCourses()->detach($course);

        return response()->json(['message' => 'Course removed from favorites'], 200);
    }
}
