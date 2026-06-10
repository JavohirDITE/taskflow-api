<?php

declare(strict_types=1);

namespace App\Enums;

enum Priority: string
{
    case LOW    = 'low';
    case MEDIUM = 'medium';
    case HIGH   = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::LOW    => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH   => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function weight(): int
    {
        return match($this) {
            self::LOW    => 1,
            self::MEDIUM => 2,
            self::HIGH   => 3,
            self::URGENT => 4,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
