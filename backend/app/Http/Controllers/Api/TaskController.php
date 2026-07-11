<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Queries\TaskIndexQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $taskIndex = new TaskIndexQuery($request->user(), $request->validated());

        return TaskResource::collection($taskIndex->paginate())->additional([
            'summary' => $taskIndex->summary(),
            'filter_options' => [
                'users' => $taskIndex->filterUsers(),
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
