<?php

namespace App\Queries;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskIndexQuery
{
    /**
     * @param  array{
     *     search?: string|null,
     *     status?: string|null,
     *     user_id?: int|null,
     *     sort?: string|null,
     *     direction?: string|null,
     *     per_page?: int|null,
     *     page?: int|null
     * }  $filters
     */
    public function __construct(
        private readonly User $user,
        private readonly array $filters,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginate(): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->visibleTasks()->with('user'));

        $this->applySorting($query);

        return $query
            ->orderBy('id', $this->direction())
            ->paginate($this->filters['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @return array{total: int, pending: int, in_progress: int, completed: int, overdue: int}
     */
    public function summary(): array
    {
        $pending = TaskStatus::Pending->value;
        $inProgress = TaskStatus::InProgress->value;
        $completed = TaskStatus::Completed->value;

        $counts = $this->visibleTasks()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', [$pending])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress', [$inProgress])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [$completed])
            ->selectRaw(
                'SUM(CASE WHEN due_date < ? AND status != ? THEN 1 ELSE 0 END) as overdue',
                [now()->toDateString(), $completed],
            )
            ->firstOrFail();

        return [
            'total' => (int) $counts->total,
            'pending' => (int) $counts->pending,
            'in_progress' => (int) $counts->in_progress,
            'completed' => (int) $counts->completed,
            'overdue' => (int) $counts->overdue,
        ];
    }

    /**
     * @return list<array{id: int, name: string, email: string}>
     */
    public function filterUsers(): array
    {
        if (! $this->user->isAdmin()) {
            return [];
        }

        return User::query()
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email'])
            ->map(static fn (User $owner): array => [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
            ])
            ->all();
    }

    /**
     * @return Builder<Task>
     */
    private function visibleTasks(): Builder
    {
        return Task::query()
            ->when(! $this->user->isAdmin(), fn (Builder $query) => $query->whereBelongsTo($this->user));
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    private function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($this->filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($this->filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status));
    }

    /**
     * @param  Builder<Task>  $query
     */
    private function applySorting(Builder $query): void
    {
        $sort = $this->filters['sort'] ?? 'created_at';
        $direction = $this->direction();

        if ($sort === 'status') {
            $query->orderByRaw(
                "CASE status WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END {$direction}",
                array_map(static fn (TaskStatus $status): string => $status->value, TaskStatus::cases()),
            );

            return;
        }

        if ($sort === 'user') {
            $query
                ->orderBy(
                    User::query()->select('name')->whereColumn('users.id', 'tasks.user_id'),
                    $direction,
                )
                ->orderBy(
                    User::query()->select('email')->whereColumn('users.id', 'tasks.user_id'),
                    $direction,
                );

            return;
        }

        if ($sort === 'due_date') {
            $query->orderByRaw('due_date IS NULL ASC');
        }

        $query->orderBy($sort, $direction);
    }

    private function direction(): string
    {
        return $this->filters['direction'] ?? 'desc';
    }
}
