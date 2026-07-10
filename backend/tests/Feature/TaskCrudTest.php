<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_task_with_pending_as_the_default_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Prepare the report',
            'description' => 'Include the current quarter.',
            'due_date' => '2026-08-10',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Prepare the report')
            ->assertJsonPath('data.status', TaskStatus::Pending->value)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.can.update', true)
            ->assertJsonPath('data.can.delete', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
            'title' => 'Prepare the report',
            'status' => TaskStatus::Pending->value,
        ]);
    }

    public function test_task_payload_is_validated_by_form_request(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/tasks', [
            'title' => 'No',
            'due_date' => 'tomorrow-ish',
            'status' => 'blocked',
        ])
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => ['title', 'due_date', 'status'],
            ]);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_database_rejects_an_unknown_task_status(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('tasks')->insert([
            'user_id' => $user->id,
            'title' => 'Invalid status task',
            'status' => 'blocked',
        ]);
    }

    public function test_user_can_view_update_and_delete_their_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create([
            'title' => 'Original title',
            'status' => TaskStatus::Pending,
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.user.email', $user->email);

        $this->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Updated title',
            'description' => null,
            'due_date' => null,
            'status' => TaskStatus::Completed->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.due_date', null)
            ->assertJsonPath('data.status', TaskStatus::Completed->value);

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_missing_task_returns_the_uniform_json_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/tasks/999999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Resource not found.']);
    }
}
