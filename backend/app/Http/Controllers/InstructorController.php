<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InstructorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', '15');
        $instructors = Instructor::with(['courses'])
            ->paginate($perPage);

        return response()->json($instructors);
    }

    public function create(): void
    {
        // API endpoint - not used
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:instructors',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        /** @var array<string, mixed> $validated */
        $instructor = Instructor::create($validated);

        return response()->json($instructor, 201);
    }

    public function show(Instructor $instructor): JsonResponse
    {
        $instructor->load(['courses', 'ratings', 'comments']);

        return response()->json([
            'instructor' => $instructor,
            'average_rating' => $instructor->averageRating(),
        ]);
    }

    public function edit(string $id): void
    {
        // API endpoint - not used
    }

    public function update(Request $request, Instructor $instructor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('instructors')->ignore($instructor->id)],
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        /** @var array<string, mixed> $validated */
        $instructor->update($validated);

        return response()->json($instructor);
    }

    public function destroy(Instructor $instructor): JsonResponse
    {
        $instructor->delete();

        return response()->json(null, 204);
    }
}
