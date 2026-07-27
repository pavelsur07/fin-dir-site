<?php

declare(strict_types=1);

namespace App\Tests;

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

            $client->request('GET', $route->getPath());

            self::assertSame(
                200,
                $client->getResponse()->getStatusCode(),
                \sprintf('Маршрут %s (%s)', $name, $route->getPath()),
            );

            ++$checked;
        }

        self::assertGreaterThan(0, $checked, 'Роутер пуст -- тест ничего не проверил');
    }
}
