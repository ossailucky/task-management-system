<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    public function test_user_can_create_task(): void
    {
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'description', 'status', 'user_id', 'created_at', 'updated_at'],
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_task_without_title(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'description' => 'Test Description',
            'status' => 'pending',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_can_list_their_tasks(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_only_sees_their_own_tasks(): void
    {
        $user = $this->actingAsUser();
        $otherUser = User::factory()->create();

        Task::factory()->count(2)->create(['user_id' => $user->id]);
        Task::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_filter_tasks_by_status(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->getJson('/api/tasks?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $task) {
            $this->assertEquals('pending', $task['status']);
        }
    }

    public function test_user_cannot_filter_with_invalid_status(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/tasks?status=invalid-status');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_view_single_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title,
                ],
            ]);
    }

    public function test_user_cannot_view_another_users_task(): void
    {
        $this->actingAsUser();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_update_their_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title',
            'status' => 'in-progress',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Updated Title',
                    'status' => 'in-progress',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_user_can_partially_update_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Original Title',
                    'status' => 'completed',
                ],
            ]);
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        $this->actingAsUser();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_delete_their_task(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_task(): void
    {
        $this->actingAsUser();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_task_validation_fails_with_invalid_status(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['status']);
    }

    public function test_tasks_are_paginated(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/tasks?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data',
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'links',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
        
        $this->assertEquals(5, $response->json('per_page'));
        $this->assertEquals(20, $response->json('total'));
    }

    public function test_tasks_pagination_respects_max_limit(): void
    {
        $user = $this->actingAsUser();
        Task::factory()->count(150)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/tasks?per_page=200');

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_tasks_are_ordered_by_latest(): void
    {
        $user = $this->actingAsUser();
        $oldTask = Task::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);
        $newTask = Task::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
        $tasks = $response->json('data');
        
        $this->assertEquals($newTask->id, $tasks[0]['id']);
        $this->assertEquals($oldTask->id, $tasks[1]['id']);
    }
}