<?php

declare(strict_types=1);

namespace App\Shared\EventListener;

use App\Shared\Exception\Conflict;
use App\Shared\Exception\NotFound;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Единственное место, где доменные исключения становятся HTTP-кодами (PATTERNS.md §12).
 * Модуль помечает исключение интерфейсом из Shared\Exception, контроллеры try/catch не пишут.
 */
// Приоритет выше ErrorListener (0): исключение заменяется до логирования и рендера.
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final class DomainExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $httpException = match (true) {
            $exception instanceof NotFound => new NotFoundHttpException($exception->getMessage(), $exception),
            $exception instanceof Conflict => new ConflictHttpException($exception->getMessage(), $exception),
            default => null,
        };

        if (null !== $httpException) {
            $event->setThrowable($httpException);
        }
    }
}
