<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Display a listing of the user's tasks.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['pending', 'in-progress', 'completed'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->user()->tasks()->latest();

        // Filter by status if provided
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Get per_page value with default
        $perPage = $validated['per_page'] ?? 15;

        // Paginate results
        $tasks = $query->paginate($perPage);

        return response()->json($tasks);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['pending', 'in-progress', 'completed'])],
        ]);

        $task = $request->user()->tasks()->create($validated);

        return response()->json($task, 201);
    }

    /**
     * Display the specified task.
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        // Check if task belongs to authenticated user
        if ($task->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'This action is unauthorized.'
            ], 403);
        }

        return response()->json($task);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        // Check if task belongs to authenticated user
        if ($task->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'This action is unauthorized.'
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['pending', 'in-progress', 'completed'])],
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        // Check if task belongs to authenticated user
        if ($task->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'This action is unauthorized.'
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully'
        ]);
    }
}