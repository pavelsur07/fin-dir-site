<?php

declare(strict_types=1);

namespace App\Tests\Publication\Query;

use App\Publication\Query\PostStatusActionOptions;
use App\Publication\ValueObject\PostStatus;
use PHPUnit\Framework\TestCase;

final class PostStatusActionOptionsTest extends TestCase
{
    public function testActionsMatchAllowedTransitions(): void
    {
        $targets = ['publish' => PostStatus::PUBLISHED, 'unpublish' => PostStatus::DRAFT, 'archive' => PostStatus::ARCHIVED, 'restore' => PostStatus::DRAFT];
        $expected = [
            'draft' => ['publish', 'archive'],
            'published' => ['unpublish', 'archive'],
            'archived' => ['restore'],
        ];

        foreach (PostStatus::cases() as $status) {
            $actions = array_column(PostStatusActionOptions::forStatus($status), 'action');
            self::assertSame($expected[$status->value], $actions);
            foreach ($actions as $action) {
                self::assertTrue($status->canTransitionTo($targets[$action]));
            }
        }
    }
}
