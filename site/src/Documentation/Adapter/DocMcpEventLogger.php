<?php

declare(strict_types=1);

namespace App\Documentation\Adapter;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final class DocMcpEventLogger
{
    public function __construct(private readonly RequestStack $requests, #[Autowire(service: 'documentation_mcp.http_logger')] private readonly LoggerInterface $logger)
    {
    }

    /** @param array<string,mixed> $event */
    public function record(array $event): void
    {
        $request = $this->requests->getMainRequest();
        if (null !== $request) {
            $event += ['requestId' => $request->attributes->get('_doc_mcp_request_id')];
            $this->logger->info('documentation.mcp '.json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

            return;
        }
        $stream = fopen('php://stderr', 'wb');
        if (false !== $stream) {
            fwrite($stream, json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n");
            fclose($stream);
        }
    }
}
