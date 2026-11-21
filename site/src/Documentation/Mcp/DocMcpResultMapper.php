<?php

declare(strict_types=1);

namespace App\Documentation\Mcp;

use App\Documentation\Adapter\DocMcpEventLogger;
use App\Documentation\Exception\DocConflict;
use App\Documentation\Exception\DocNotFound;
use App\Documentation\Exception\DocToolFailure;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;

final class DocMcpResultMapper
{
    public function __construct(private readonly ?DocMcpEventLogger $events = null)
    {
    }

    /** @param array<string,mixed> $data */
    public function success(array $data): CallToolResult
    {
        $result = new CallToolResult([new TextContent('Результат доступен в structuredContent.')], structuredContent: $data);
        // Reserve space for the SDK envelope, request id and modern metadata.
        if (strlen(json_encode($result, JSON_THROW_ON_ERROR)) > 1024 * 1024 - 8192) {
            throw new DocToolFailure('LIMIT_EXCEEDED', 'Результат слишком велик для одного ответа.');
        }

        return $result;
    }

    public function error(\Throwable $error): CallToolResult
    {
        [$code, $message] = match (true) {
            $error instanceof DocToolFailure => [$error->reason, $error->getMessage()],
            $error instanceof DocNotFound, $error instanceof OutOfRangeCurrentPageException => ['NOT_FOUND', 'Страница не найдена.'],
            $error instanceof DocConflict => ['CONTENT_CONFLICT', $error->getMessage()],
            $error instanceof \InvalidArgumentException => ['VALIDATION_FAILED', 'Проверьте значения полей.'],
            default => ['INTERNAL_ERROR', 'Не удалось выполнить операцию. Повторите запрос с тем же requestKey.'],
        };
        // Avoid losing the SDK request id to the final output-frame cap.
        if (strlen($message) > 4096) {
            $code = 'LIMIT_EXCEEDED';
            $message = 'Сообщение об ошибке слишком велико. Сократите некорректную ссылку или ввод.';
        }
        $data = ['code' => $code, 'message' => $message];

        return new CallToolResult([new TextContent(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))], true, $data);
    }

    /** @param array<string,mixed> $event */
    public function log(array $event): void
    {
        $this->events?->record($event);
    }
}
