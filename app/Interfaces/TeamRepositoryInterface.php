<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeamRepositoryInterface
{
    public function findById(int $id): ?Team;

    public function findByIdOrFail(int $id): Team;

    public function getUserTeams(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data, User $owner): Team;

    public function update(Team $team, array $data): Team;

    public function delete(Team $team): bool;

    public function addMember(Team $team, int $userId, string $role): void;

    public function removeMember(Team $team, int $userId): void;

    public function updateMemberRole(Team $team, int $userId, string $role): void;
}
