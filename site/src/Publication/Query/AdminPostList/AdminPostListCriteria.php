<?php

declare(strict_types=1);

namespace App\Publication\Query\AdminPostList;

use App\Publication\ValueObject\PostStatus;

final readonly class AdminPostListCriteria
{
    public const int PER_PAGE = 20;

    public function __construct(
        public ?PostStatus $status = null,
        public AdminPostSort $sort = AdminPostSort::UPDATED,
        public int $page = 1,
    ) {
    }
}
