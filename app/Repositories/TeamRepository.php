<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\TeamRole;
use App\Interfaces\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeamRepository implements TeamRepositoryInterface
{
    public function findById(int $id): ?Team
    {
        return Team::with(['owner', 'members'])->find($id);
    }

    public function findByIdOrFail(int $id): Team
    {
        return Team::with(['owner', 'members'])->findOrFail($id);
    }

    public function getUserTeams(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Team::whereHas('members', fn ($q) => $q->where('users.id', $userId))
            ->orWhere('owner_id', $userId)
            ->with(['owner'])
            ->withCount('members', 'projects')
            ->active()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(array $data, User $owner): Team
    {
        $team = Team::create([
            ...$data,
            'owner_id' => $owner->id,
        ]);

        // Owner is automatically added as a member with owner role
        $team->members()->attach($owner->id, ['role' => TeamRole::OWNER->value]);

        return $team->load(['owner', 'members']);
    }

    public function update(Team $team, array $data): Team
    {
        $team->update($data);

        return $team->fresh(['owner', 'members']);
    }

    public function delete(Team $team): bool
    {
        return (bool) $team->delete();
    }

    public function addMember(Team $team, int $userId, string $role): void
    {
        $team->members()->syncWithoutDetaching([
            $userId => ['role' => $role],
        ]);
    }

    public function removeMember(Team $team, int $userId): void
    {
        // Security: cannot remove the owner from their own team
        if ($team->owner_id === $userId) {
            throw new \DomainException('Cannot remove the team owner.');
        }

        $team->members()->detach($userId);
    }

    public function updateMemberRole(Team $team, int $userId, string $role): void
    {
        $team->members()->updateExistingPivot($userId, ['role' => $role]);
    }
}
