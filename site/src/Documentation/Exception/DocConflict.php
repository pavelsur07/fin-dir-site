<?php

declare(strict_types=1);

namespace App\Documentation\Exception;

use App\Shared\Exception\Conflict;

final class DocConflict extends \RuntimeException implements Conflict
{
}
