<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $filters = $request->validated();
        $user = $request->user();

        $visibleTasks = Task::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->whereBelongsTo($user));

        $counts = (clone $visibleTasks)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN due_date < ? AND status != 'completed' THEN 1 ELSE 0 END) as overdue", [now()->toDateString()])
            ->first();

        $summary = [
            'total' => (int) $counts->total,
            'pending' => (int) $counts->pending,
            'in_progress' => (int) $counts->in_progress,
            'completed' => (int) $counts->completed,
            'overdue' => (int) $counts->overdue,
        ];

        $query = (clone $visibleTasks)
            ->with('user')
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status));

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        if ($sort === 'status') {
            $query->orderByRaw(
                "CASE status WHEN 'pending' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END {$direction}"
            );
        } elseif ($sort === 'user') {
            $query
                ->orderBy(
                    User::query()->select('name')->whereColumn('users.id', 'tasks.user_id'),
                    $direction,
                )
                ->orderBy(
                    User::query()->select('email')->whereColumn('users.id', 'tasks.user_id'),
                    $direction,
                );
        } else {
            if ($sort === 'due_date') {
                $query->orderByRaw('due_date IS NULL ASC');
            }

            $query->orderBy($sort, $direction);
        }

        $tasks = $query
            ->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return TaskResource::collection($tasks)->additional([
            'summary' => $summary,
            'filter_options' => [
                'users' => $user->isAdmin()
                    ? User::query()
                        ->orderBy('name')
                        ->orderBy('email')
                        ->get(['id', 'name', 'email'])
                        ->map(fn (User $owner): array => [
                            'id' => $owner->id,
                            'name' => $owner->name,
                            'email' => $owner->email,
                        ])
                        ->all()
                    : [],
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $request->user()->tasks()->create($request->validated());

        return new TaskResource($task->load('user'));
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load('user'));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->refresh()->load('user'));
    }

    public function destroy(Task $task): Response
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->noContent();
    }
}
