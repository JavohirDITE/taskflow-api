<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id'      => Project::factory(),
            'creator_id'      => User::factory(),
            'assignee_id'     => null,
            'title'           => $this->faker->sentence(5),
            'description'     => $this->faker->paragraph(3),
            'status'          => $this->faker->randomElement(TaskStatus::values()),
            'priority'        => $this->faker->randomElement(Priority::values()),
            'due_date'        => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'estimated_hours' => $this->faker->optional()->randomFloat(1, 0.5, 40),
            'is_archived'     => false,
        ];
    }

    public function todo(): static
    {
        return $this->state(['status' => TaskStatus::TODO->value]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => TaskStatus::IN_PROGRESS->value]);
    }

    public function done(): static
    {
        return $this->state(['status' => TaskStatus::DONE->value]);
    }

    public function urgent(): static
    {
        return $this->state(['priority' => Priority::URGENT->value]);
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date' => now()->subDays(3),
            'status'   => TaskStatus::TODO->value,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assignee_id' => $user->id]);
    }
}
