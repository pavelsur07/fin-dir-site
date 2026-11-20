<?php

declare(strict_types=1);

namespace App\Documentation\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class DocAdminResponseListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if (str_starts_with($event->getRequest()->getPathInfo(), '/admin/docs')) {
            $event->getResponse()->headers->set('Cache-Control', 'private, no-store');
            $event->getResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
    }
}
