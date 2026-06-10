<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTeamWithProject(): array
{
    $owner   = User::factory()->create();
    $member  = User::factory()->create();
    $team    = Team::factory()->create(['owner_id' => $owner->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    $team->members()->attach($owner->id, ['role' => TeamRole::OWNER->value]);
    $team->members()->attach($member->id, ['role' => TeamRole::MEMBER->value]);

    return compact('owner', 'member', 'team', 'project');
}

describe('Task Index', function () {
    it('returns tasks for a project the user belongs to', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();
        Task::factory(5)->create(['project_id' => $project->id, 'creator_id' => $owner->id]);

        $this->actingAs($owner)
            ->getJson("/api/v1/projects/{$project->id}/tasks")
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    it('denies access to tasks for non-members', function () {
        ['project' => $project] = createTeamWithProject();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/v1/projects/{$project->id}/tasks")
            ->assertForbidden();
    });

    it('can filter tasks by status', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();
        Task::factory(3)->todo()->create(['project_id' => $project->id, 'creator_id' => $owner->id]);
        Task::factory(2)->done()->create(['project_id' => $project->id, 'creator_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->getJson("/api/v1/projects/{$project->id}/tasks?status=todo");

        $response->assertOk()->assertJsonCount(3, 'data');
    });
});

describe('Task Store', function () {
    it('creates a task with valid data', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();

        $response = $this->actingAs($owner)->postJson(
            "/api/v1/projects/{$project->id}/tasks",
            [
                'title'    => 'Implement authentication',
                'priority' => 'high',
                'status'   => 'todo',
            ]
        );

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Implement authentication')
            ->assertJsonPath('priority.value', 'high');

        $this->assertDatabaseHas('tasks', [
            'title'      => 'Implement authentication',
            'project_id' => $project->id,
            'creator_id' => $owner->id,
        ]);
    });

    it('creates an audit log entry when task is created', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();

        $this->actingAs($owner)->postJson(
            "/api/v1/projects/{$project->id}/tasks",
            ['title' => 'Audited task', 'priority' => 'medium']
        );

        $this->assertDatabaseHas('audit_logs', ['event' => 'created']);
    });

    it('rejects task creation with missing title', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();

        $this->actingAs($owner)
            ->postJson("/api/v1/projects/{$project->id}/tasks", ['priority' => 'high'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('rejects task creation for viewers', function () {
        ['project' => $project, 'team' => $team] = createTeamWithProject();
        $viewer = User::factory()->create();
        $team->members()->attach($viewer->id, ['role' => TeamRole::VIEWER->value]);

        $this->actingAs($viewer)
            ->postJson("/api/v1/projects/{$project->id}/tasks", ['title' => 'Forbidden task'])
            ->assertForbidden();
    });
});

describe('Task Status Update', function () {
    it('updates task status', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();
        $task = Task::factory()->todo()->create([
            'project_id' => $project->id,
            'creator_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/tasks/{$task->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('status.value', 'in_progress');
    });

    it('rejects invalid status value', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/tasks/{$task->id}/status", ['status' => 'invalid-status'])
            ->assertStatus(422);
    });
});

describe('Task Delete', function () {
    it('soft deletes a task', function () {
        ['owner' => $owner, 'project' => $project] = createTeamWithProject();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'creator_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    });

    it('prevents non-owner from deleting task', function () {
        ['project' => $project, 'team' => $team] = createTeamWithProject();
        $task  = Task::factory()->create(['project_id' => $project->id]);
        $viewer = User::factory()->create();
        $team->members()->attach($viewer->id, ['role' => TeamRole::VIEWER->value]);

        $this->actingAs($viewer)
            ->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertForbidden();
    });
});

describe('Task Authorization', function () {
    it('requires authentication for all task endpoints', function () {
        $task = Task::factory()->create();

        $this->getJson("/api/v1/tasks/{$task->id}")->assertStatus(401);
        $this->putJson("/api/v1/tasks/{$task->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/tasks/{$task->id}")->assertStatus(401);
    });
});
