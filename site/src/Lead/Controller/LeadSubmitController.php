<?php

declare(strict_types=1);

namespace App\Lead\Controller;

use App\Lead\DTO\LeadSubmission;
use App\Lead\Service\LeadRegistrar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Приём обращений со всех форм сайта. JS получает JSON, браузер без JS -- страницу результата.
 */
final class LeadSubmitController extends AbstractController
{
    private const array FIELD_NAMES = [
        'submissionId' => 'submission_id',
        'fillMs' => 'fill_ms',
        'pageUrl' => 'page_url',
    ];

    #[Route('/lead', name: 'lead_submit', methods: ['POST'])]
    public function __invoke(
        Request $request,
        ValidatorInterface $validator,
        LeadRegistrar $registrar,
        #[Autowire(service: 'limiter.lead_submit')] RateLimiterFactoryInterface $leadSubmitLimiter,
    ): Response {
        // Stateless CSRF: чужой Origin/Referer -- 403. Сессия не нужна.
        if (!$this->isCsrfTokenValid('lead_submit', $request->getPayload()->getString('_token'))) {
            return $this->reply($request, Response::HTTP_FORBIDDEN, ['error' => 'Отправка отклонена. Обновите страницу и попробуйте ещё раз.']);
        }

        $submission = LeadSubmission::fromFormData($request->getPayload()->all());

        $errors = [];
        foreach ($validator->validate($submission) as $violation) {
            $field = self::FIELD_NAMES[$violation->getPropertyPath()] ?? $violation->getPropertyPath();
            $errors[$field] ??= (string) $violation->getMessage();
        }
        if ([] !== $errors) {
            return $this->reply($request, Response::HTTP_UNPROCESSABLE_ENTITY, ['errors' => $errors]);
        }

        // Лимит тратят только валидные заявки: человек, исправляющий ошибки в форме, его не исчерпает.
        if (!$leadSubmitLimiter->create((string) $request->getClientIp())->consume()->isAccepted()) {
            return $this->reply($request, Response::HTTP_TOO_MANY_REQUESTS, ['error' => 'Слишком много заявок подряд. Попробуйте через несколько минут или напишите нам в Telegram.']);
        }

        $registrar->register($submission);

        return $this->reply($request, Response::HTTP_CREATED, ['ok' => true], $submission->pageUrl);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function reply(Request $request, int $status, array $payload, ?string $backUrl = null): Response
    {
        if ('json' === $request->getPreferredFormat()) {
            return new JsonResponse($payload, $status);
        }

        $response = $this->render('website/pages/lead/lead_result.html.twig', [
            'success' => Response::HTTP_CREATED === $status,
            'errors' => $payload['errors'] ?? (isset($payload['error']) ? [$payload['error']] : []),
            // Только свой адрес: чужой URL из формы не превращается в ссылку.
            'back_url' => null !== $backUrl && 1 === preg_match(LeadSubmission::LOCAL_PATH, $backUrl) ? $backUrl : '/',
        ]);
        $response->setStatusCode($status);

        return $response;
    }
}
