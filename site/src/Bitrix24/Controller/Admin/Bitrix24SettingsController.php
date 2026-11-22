<?php

declare(strict_types=1);

namespace App\Bitrix24\Controller\Admin;

use App\Bitrix24\Exception\Bitrix24RequestFailed;
use App\Bitrix24\Exception\InvalidWebhookUrl;
use App\Bitrix24\Query\Bitrix24Settings\Bitrix24SettingsQuery;
use App\Bitrix24\Service\Bitrix24ConnectionUpdater;
use App\Bitrix24\ValueObject\Bitrix24FailureReason;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * «Настройки → Битрикс24». Адрес вебхука принимается, но никогда не выводится обратно.
 */
#[IsGranted('ROLE_ADMIN')]
final class Bitrix24SettingsController extends AbstractController
{
    private const string CSRF = 'bitrix24-settings';

    #[Route('/admin/settings/bitrix24', name: 'admin_settings_bitrix24', methods: ['GET'])]
    public function show(Bitrix24SettingsQuery $query): Response
    {
        return $this->render('admin/settings/bitrix24.html.twig', ['settings' => $query->get()]);
    }

    #[Route('/admin/settings/bitrix24/connect', name: 'admin_settings_bitrix24_connect', methods: ['POST'])]
    public function connect(Request $request, Bitrix24ConnectionUpdater $updater): Response
    {
        $this->guard($request);

        try {
            $updater->connect($request->getPayload()->getString('webhook'));
            $this->addFlash('success', 'Битрикс24 подключён, новые обращения будут создаваться лидами.');
        } catch (InvalidWebhookUrl) {
            $this->addFlash('error', 'Это не адрес входящего вебхука. Нужен вид https://портал.bitrix24.ru/rest/1/код/');
        } catch (Bitrix24RequestFailed $e) {
            $this->addFlash('error', 'Подключение не сохранено: '.Bitrix24FailureReason::label($e->reason).'.');
        }

        return $this->redirectToRoute('admin_settings_bitrix24');
    }

    #[Route('/admin/settings/bitrix24/check', name: 'admin_settings_bitrix24_check', methods: ['POST'])]
    public function check(Request $request, Bitrix24ConnectionUpdater $updater): Response
    {
        $this->guard($request);
        $error = $updater->check();

        null === $error
            ? $this->addFlash('success', 'Портал отвечает, подключение работает.')
            : $this->addFlash('error', 'Проверка не прошла: '.Bitrix24FailureReason::label($error).'.');

        return $this->redirectToRoute('admin_settings_bitrix24');
    }

    #[Route('/admin/settings/bitrix24/enabled', name: 'admin_settings_bitrix24_enabled', methods: ['POST'])]
    public function enabled(Request $request, Bitrix24ConnectionUpdater $updater): Response
    {
        $this->guard($request);
        $enabled = '1' === $request->getPayload()->getString('enabled');
        $updater->changeEnabled($enabled);
        $this->addFlash('success', $enabled ? 'Отправка обращений в Битрикс24 включена.' : 'Отправка обращений в Битрикс24 выключена.');

        return $this->redirectToRoute('admin_settings_bitrix24');
    }

    #[Route('/admin/settings/bitrix24/disconnect', name: 'admin_settings_bitrix24_disconnect', methods: ['POST'])]
    public function disconnect(Request $request, Bitrix24ConnectionUpdater $updater): Response
    {
        $this->guard($request);
        $updater->disconnect();
        $this->addFlash('success', 'Подключение удалено. Сам вебхук удалите в Битрикс24: Разработчикам → Другое → Входящий вебхук.');

        return $this->redirectToRoute('admin_settings_bitrix24');
    }

    private function guard(Request $request): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
