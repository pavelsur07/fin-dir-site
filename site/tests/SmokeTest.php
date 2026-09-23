<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Один проход по роутеру: каждая GET-страница без параметров должна отдавать 200.
 * Ловит то, чего не видит ни один линтер -- ошибки Twig во время рендера,
 * незарегистрированные сервисы и любые 500-е.
 */
final class SmokeTest extends WebTestCase
{
    public function testEveryStaticRouteRespondsOk(): void
    {
        $client = static::createClient();
        // Без перехвата исключений падение показывает настоящую ошибку и стек,
        // а не безликое "ожидали 200, получили 500".
        $client->catchExceptions(false);

        $router = static::getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        $checked = 0;

        foreach ($router->getRouteCollection() as $name => $route) {
            $methods = $route->getMethods();

            // Маршруты с параметрами требуют фикстур -- проверяются отдельными тестами.
            if (str_contains($route->getPath(), '{') || ($methods && !\in_array('GET', $methods, true))) {
                continue;
            }

            // Redirect-маршруты (старые URL) отвечают 301 -- их проверяет PublicBlogTest.
            if (RedirectController::class === $route->getDefault('_controller')) {
                continue;
            }

            // Админка закрыта логином -- её проверяет Admin\AdminAuthTest.
            if (str_starts_with($route->getPath(), '/admin')) {
                continue;
            }

            $client->request('GET', $route->getPath());

            self::assertSame(
                200,
                $client->getResponse()->getStatusCode(),
                \sprintf('Маршрут %s (%s)', $name, $route->getPath()),
            );
            // Сессия есть только у /admin: публичная страница с cookie ломает HTTP-кеш.
            self::assertSame(
                [],
                $client->getResponse()->headers->getCookies(),
                \sprintf('Маршрут %s (%s) ставит cookie', $name, $route->getPath()),
            );

            ++$checked;
        }

        self::assertGreaterThan(0, $checked, 'Роутер пуст -- тест ничего не проверил');
    }
}
