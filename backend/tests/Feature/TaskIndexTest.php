<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_sees_only_their_tasks_while_admin_sees_all(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Task::factory()->count(2)->for($user)->create();
        Task::factory()->count(3)->for($otherUser)->create();

        Sanctum::actingAs($user);
        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('summary.total', 2);

        Sanctum::actingAs($admin);
        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('summary.total', 5);
    }

    public function test_tasks_can_be_searched_and_filtered_by_status(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create([
            'title' => 'Write release notes',
            'description' => null,
            'status' => TaskStatus::Pending,
        ]);
        Task::factory()->for($user)->create([
            'title' => 'Review pull request',
            'description' => 'Contains release checklist details',
            'status' => TaskStatus::Completed,
        ]);
        Task::factory()->for($user)->create([
            'title' => 'Unrelated task',
            'description' => null,
            'status' => TaskStatus::Pending,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/tasks?search=release')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/tasks?status=pending')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', TaskStatus::Pending->value)
            ->assertJsonPath('data.1.status', TaskStatus::Pending->value);
    }

    public function test_status_sort_uses_business_order_and_due_date_sort_honors_direction(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create([
            'title' => 'Completed',
            'status' => TaskStatus::Completed,
            'due_date' => '2026-08-03',
        ]);
        Task::factory()->for($user)->create([
            'title' => 'Pending',
            'status' => TaskStatus::Pending,
            'due_date' => '2026-08-01',
        ]);
        Task::factory()->for($user)->create([
            'title' => 'In progress',
            'status' => TaskStatus::InProgress,
            'due_date' => '2026-08-02',
        ]);
        Task::factory()->for($user)->create([
            'title' => 'No due date',
            'status' => TaskStatus::Completed,
            'due_date' => null,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/tasks?sort=status&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.status', TaskStatus::Pending->value)
            ->assertJsonPath('data.1.status', TaskStatus::InProgress->value)
            ->assertJsonPath('data.2.status', TaskStatus::Completed->value);

        $this->getJson('/api/tasks?sort=due_date&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.0.due_date', '2026-08-03')
            ->assertJsonPath('data.2.due_date', '2026-08-01')
            ->assertJsonPath('data.3.due_date', null);

        $this->getJson('/api/tasks?sort=due_date&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.due_date', '2026-08-01')
            ->assertJsonPath('data.3.due_date', null);
    }

    public function test_summary_is_based_on_all_visible_tasks_not_the_filtered_page(): void
    {
        Carbon::setTestNow('2026-07-10 12:00:00');
        $user = User::factory()->create();
        Task::factory()->for($user)->create([
            'status' => TaskStatus::Pending,
            'due_date' => '2026-07-09',
        ]);
        Task::factory()->count(2)->for($user)->create([
            'status' => TaskStatus::InProgress,
            'due_date' => '2026-07-20',
        ]);
        Task::factory()->for($user)->create([
            'status' => TaskStatus::Completed,
            'due_date' => '2026-07-01',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/tasks?status=in_progress&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('summary.total', 4)
            ->assertJsonPath('summary.pending', 1)
            ->assertJsonPath('summary.in_progress', 2)
            ->assertJsonPath('summary.completed', 1)
            ->assertJsonPath('summary.overdue', 1);
    }

    public function test_task_list_is_paginated_and_preserves_query_parameters(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(5)->for($user)->create([
            'status' => TaskStatus::Pending,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/tasks?status=pending&per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('summary.total', 5)
            ->assertJsonPath('links.next', url('/api/tasks?status=pending&per_page=2&page=3'));
    }

    public function test_invalid_list_filters_return_422_json(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/tasks?status=blocked&sort=title&direction=sideways&per_page=101')
            ->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => ['status', 'sort', 'direction', 'per_page'],
            ]);
    }
}
