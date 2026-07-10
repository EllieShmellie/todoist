<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_view_update_or_delete_another_users_task(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->for($owner)->create(['title' => 'Private task']);
        Sanctum::actingAs($otherUser);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertForbidden()
            ->assertJsonStructure(['message']);

        $this->patchJson("/api/tasks/{$task->id}", ['title' => 'Stolen task'])
            ->assertForbidden()
            ->assertJsonStructure(['message']);

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertForbidden()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Private task',
        ]);
    }

    public function test_admin_can_view_update_and_delete_any_task(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $task = Task::factory()->for($owner)->create([
            'status' => TaskStatus::Pending,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.user.id', $owner->id)
            ->assertJsonPath('data.can.update', true)
            ->assertJsonPath('data.can.delete', true);

        $this->patchJson("/api/tasks/{$task->id}", [
            'status' => TaskStatus::Completed->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', TaskStatus::Completed->value);

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
