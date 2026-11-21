<?php

declare(strict_types=1);

namespace App\Documentation\Mcp;

use Mcp\Server\ClientGateway;
use Mcp\Server\Handler\ToolHandlerInterface;

final readonly class DocMcpToolHandler implements ToolHandlerInterface
{
    public function __construct(private \Closure $handler)
    {
    }

    public function execute(array $arguments, ClientGateway $gateway): mixed
    {
        // The SDK preserves nested JSON objects as stdClass after schema validation.
        if (($arguments['page'] ?? null) instanceof \stdClass) {
            $arguments['page'] = (array) $arguments['page'];
        }

        return ($this->handler)($arguments);
    }
}
