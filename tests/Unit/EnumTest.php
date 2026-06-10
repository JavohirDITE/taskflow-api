<?php

declare(strict_types=1);

use App\Enums\Priority;
use App\Enums\TaskStatus;

describe('TaskStatus Enum', function () {
    it('has correct values', function () {
        expect(TaskStatus::TODO->value)->toBe('todo');
        expect(TaskStatus::IN_PROGRESS->value)->toBe('in_progress');
        expect(TaskStatus::DONE->value)->toBe('done');
        expect(TaskStatus::CANCELLED->value)->toBe('cancelled');
    });

    it('correctly identifies terminal states', function () {
        expect(TaskStatus::DONE->isTerminal())->toBeTrue();
        expect(TaskStatus::CANCELLED->isTerminal())->toBeTrue();
        expect(TaskStatus::TODO->isTerminal())->toBeFalse();
        expect(TaskStatus::IN_PROGRESS->isTerminal())->toBeFalse();
        expect(TaskStatus::IN_REVIEW->isTerminal())->toBeFalse();
    });

    it('returns correct labels', function () {
        expect(TaskStatus::IN_PROGRESS->label())->toBe('In Progress');
        expect(TaskStatus::IN_REVIEW->label())->toBe('In Review');
    });

    it('returns all values as array', function () {
        $values = TaskStatus::values();
        expect($values)->toContain('todo', 'in_progress', 'in_review', 'done', 'cancelled');
        expect(count($values))->toBe(5);
    });
});

describe('Priority Enum', function () {
    it('has correct weight ordering', function () {
        expect(Priority::LOW->weight())->toBeLessThan(Priority::MEDIUM->weight());
        expect(Priority::MEDIUM->weight())->toBeLessThan(Priority::HIGH->weight());
        expect(Priority::HIGH->weight())->toBeLessThan(Priority::URGENT->weight());
    });

    it('has correct values', function () {
        expect(Priority::URGENT->value)->toBe('urgent');
        expect(Priority::HIGH->value)->toBe('high');
    });
});
