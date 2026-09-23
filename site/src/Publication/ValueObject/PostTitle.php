<?php

declare(strict_types=1);

namespace App\Publication\ValueObject;

/**
 * Лимит заголовка статьи: общий для Entity, колонки и валидации формы.
 */
final class PostTitle
{
    public const int MAX_LENGTH = 200;
}
