<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Tasks", description="Task management endpoints")
 */
class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/projects/{project}/tasks",
     *     summary="List tasks in a project",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"todo","in_progress","in_review","done","cancelled"})),
     *     @OA\Parameter(name="priority", in="query", @OA\Schema(type="string", enum={"low","medium","high","urgent"})),
     *     @OA\Parameter(name="assignee_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="overdue", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Paginated list of tasks"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        // Authorization: user must be member of the team that owns this project
        $this->authorize('view', $project);

        $tasks = $this->taskService->getProjectTasks(
            $project->id,
            $request->only(['status', 'priority', 'assignee_id', 'overdue', 'sort', 'direction']),
            $request->user()
        );

        return response()->json(TaskResource::collection($tasks)->response()->getData(true));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/projects/{project}/tasks",
     *     summary="Create a new task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateTaskRequest")),
     *     @OA\Response(response=201, description="Task created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('createTask', $project);

        $task = $this->taskService->createTask(
            [...$request->validated(), 'project_id' => $project->id],
            $request->user()
        );

        return response()->json(new TaskResource($task), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tasks/{task}",
     *     summary="Get task details",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Task details"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json(new TaskResource($task->load(['comments.author', 'attachments', 'auditLogs'])));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/tasks/{task}",
     *     summary="Update a task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Task updated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $updatedTask = $this->taskService->updateTask($task, $request->validated(), $request->user());

        return response()->json(new TaskResource($updatedTask));
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/tasks/{task}/status",
     *     summary="Update task status",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Status updated")
     * )
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $updatedTask = $this->taskService->updateTaskStatus(
            $task,
            $request->validated('status'),
            $request->user()
        );

        return response()->json(new TaskResource($updatedTask));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tasks/{task}",
     *     summary="Delete a task",
     *     tags={"Tasks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Task deleted"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->deleteTask($task, $request->user());

        return response()->json(null, 204);
    }
}
