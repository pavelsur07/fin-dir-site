<?php

declare(strict_types=1);

namespace App\Documentation\Exception;

use App\Shared\Exception\NotFound;

final class DocNotFound extends \RuntimeException implements NotFound
{
}
