<?php

declare(strict_types=1);

namespace App\Documentation\Adapter;

use Psr\Http\Client\ClientExceptionInterface;

final class DocMcpIssuerUnavailable extends \RuntimeException implements ClientExceptionInterface
{
}
