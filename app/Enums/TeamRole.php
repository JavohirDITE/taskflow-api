<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamRole: string
{
    case OWNER  = 'owner';
    case ADMIN  = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::OWNER  => 'Owner',
            self::ADMIN  => 'Admin',
            self::MEMBER => 'Member',
            self::VIEWER => 'Viewer',
        };
    }

    public function canManageTeam(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    public function canCreateTasks(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MEMBER]);
    }

    public function canDeleteTasks(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    public function canInviteMembers(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
