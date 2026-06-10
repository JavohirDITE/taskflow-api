<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case TODO       = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW  = 'in_review';
    case DONE       = 'done';
    case CANCELLED  = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::TODO        => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::IN_REVIEW   => 'In Review',
            self::DONE        => 'Done',
            self::CANCELLED   => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::DONE, self::CANCELLED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
