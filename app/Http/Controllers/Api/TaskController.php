<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    use ApiResponse;
    
     // Display a listing of the user's tasks.
     
    public function index(Request $request): JsonResponse
    {
        try {
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
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Invalid filter parameters');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while retrieving tasks');
        }
    }

    
     // Store a newly created task.
     
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['required', 'string', Rule::in(['pending', 'in-progress', 'completed'])],
            ]);

            $task = $request->user()->tasks()->create($validated);

            return $this->createdResponse($task, 'Task created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Task creation failed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while creating the task');
        }
    }

    
     // Display the specified task.
     
    public function show(Request $request, Task $task): JsonResponse
    {
        try {
            // Check if task belongs to authenticated user
            if ($task->user_id !== $request->user()->id) {
                return $this->forbiddenResponse('You do not have permission to access this task');
            }

            return $this->successResponse($task, 'Task retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Task not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while retrieving the task');
        }
    }

    
     // Update the specified task.
     
    public function update(Request $request, Task $task): JsonResponse
    {
        try {
            // Check if task belongs to authenticated user
            if ($task->user_id !== $request->user()->id) {
                return $this->forbiddenResponse('You do not have permission to update this task');
            }

            $validated = $request->validate([
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'status' => ['sometimes', 'required', 'string', Rule::in(['pending', 'in-progress', 'completed'])],
            ]);

            $task->update($validated);

            return $this->successResponse($task, 'Task updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Task update failed');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Task not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while updating the task');
        }
    }

    
     // Remove the specified task.
     
    public function destroy(Request $request, Task $task): JsonResponse
    {
        try {
            // Check if task belongs to authenticated user
            if ($task->user_id !== $request->user()->id) {
                return $this->forbiddenResponse('You do not have permission to delete this task');
            }

            $task->delete();

            return $this->successResponse(null, 'Task deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Task not found');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while deleting the task');
        }
    }
}