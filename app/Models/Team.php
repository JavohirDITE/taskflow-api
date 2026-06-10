<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // =================== Relationships ===================

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // =================== Scopes ===================

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    // =================== Helpers ===================

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('users.id', $userId)->exists();
    }

    public function getMemberRole(int $userId): ?TeamRole
    {
        $member = $this->members()->where('users.id', $userId)->first();
        if (! $member) {
            return null;
        }

        return TeamRole::from($member->pivot->role);
    }

    public function userCanManage(int $userId): bool
    {
        $role = $this->getMemberRole($userId);

        return $role?->canManageTeam() ?? false;
    }
}
