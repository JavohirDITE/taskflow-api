<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo admin user with known credentials (for local dev only)
        $admin = User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@taskflow.test',
            // Security: known only for dev, not committed as production credentials
            'password' => Hash::make('Admin123!'),
        ]);

        // Create team members
        $members = User::factory(5)->create();

        // Create a demo team
        $team = Team::create([
            'name'     => 'Engineering Team',
            'slug'     => 'engineering-team',
            'owner_id' => $admin->id,
            'is_active' => true,
        ]);

        // Attach admin as owner
        $team->members()->attach($admin->id, ['role' => TeamRole::OWNER->value]);

        // Attach members with different roles
        $members->each(function (User $user, int $index) use ($team) {
            $role = $index === 0 ? TeamRole::ADMIN : TeamRole::MEMBER;
            $team->members()->attach($user->id, ['role' => $role->value]);
        });

        // Create projects
        $project1 = Project::create([
            'team_id'     => $team->id,
            'name'        => 'TaskFlow Backend',
            'slug'        => 'taskflow-backend',
            'description' => 'Core backend API development',
            'color'       => '#6366f1',
        ]);

        $project2 = Project::create([
            'team_id'     => $team->id,
            'name'        => 'Mobile App',
            'slug'        => 'mobile-app',
            'description' => 'React Native mobile application',
            'color'       => '#10b981',
        ]);

        // Create tasks
        Task::factory(20)->create([
            'project_id' => $project1->id,
            'creator_id' => $admin->id,
        ]);

        Task::factory(10)->create([
            'project_id'  => $project1->id,
            'creator_id'  => $admin->id,
            'assignee_id' => $members->first()->id,
        ]);

        Task::factory(15)->create([
            'project_id' => $project2->id,
            'creator_id' => $members->first()->id,
        ]);

        // Create a few overdue tasks for realism
        Task::factory(3)->overdue()->create([
            'project_id' => $project1->id,
            'creator_id' => $admin->id,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('  Login: admin@taskflow.test / Admin123!');
    }
}
