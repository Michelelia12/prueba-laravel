<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courseId = $request->query('course_id');
        $query = Lesson::with(['course', 'video']);

        if ($courseId !== null) {
            $query->where('course_id', $courseId);
        }

        $lessons = $query->paginate(15);

        return response()->json($lessons);
    }

    public function create(): void
    {
        // API endpoint - not used
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'description' => 'nullable|string',
            'title' => ['required', 'string', 'max:255'],
            'sequence' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        /** @var array<string, mixed> $validated */
        $lesson = Lesson::create($validated);

        return response()->json($lesson, 201);
    }

    public function show(Lesson $lesson): JsonResponse
    {
        $lesson->load(['course', 'video']);

        return response()->json($lesson);
    }

    public function edit(string $id): void
    {
        // API endpoint - not used
    }

    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'sometimes|required|exists:courses,id',
            'sequence' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        /** @var array<string, mixed> $validated */
        $lesson->update($validated);

        return response()->json($lesson);
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $lesson->delete();

        return response()->json(null, 204);
    }
}
