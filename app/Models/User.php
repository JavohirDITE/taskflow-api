<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * Security: explicit allowlist, not fillable = ['*']
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'timezone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Security: never expose password hash or remember token in API responses.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Security: password is automatically hashed via Laravel's built-in hashing
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // =================== Relationships ===================

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'creator_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // =================== Scopes ===================

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    // =================== Helpers ===================

    public function isTeamMember(int $teamId): bool
    {
        return $this->teams()->where('teams.id', $teamId)->exists();
    }

    public function getTeamRole(int $teamId): ?string
    {
        $team = $this->teams()->where('teams.id', $teamId)->first();

        return $team?->pivot?->role;
    }
}
