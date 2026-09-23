<?php

declare(strict_types=1);

namespace App\Lead\Controller\Admin;

use App\Lead\Exception\LeadWasModified;
use App\Lead\Service\LeadEraser;
use App\Lead\Service\LeadNoteAdder;
use App\Lead\Service\LeadNotificationSender;
use App\Lead\Service\LeadStatusUpdater;
use App\Lead\ValueObject\LeadStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * POST-действия карточки обращения. Каждое -- отдельный маршрут и один сценарий.
 */
#[IsGranted('ROLE_ADMIN')]
final class LeadActionController extends AbstractController
{
    private const int NOTE_MAX_LENGTH = 5000;

    #[Route('/admin/leads/{id}/update', name: 'admin_lead_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(int $id, Request $request, LeadStatusUpdater $updater): Response
    {
        $this->guard($request);
        $payload = $request->getPayload();

        $status = LeadStatus::tryFrom($payload->getString('status')) ?? throw new BadRequestHttpException('Unknown status.');
        $date = $payload->getString('next_contact_at');
        $nextContactAt = '' === $date ? null : (\DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: throw new BadRequestHttpException('Invalid date.'));

        try {
            $updater->update($id, $payload->getInt('version'), $status, $nextContactAt);
            $this->addFlash('success', 'Обращение обновлено.');
        } catch (LeadWasModified) {
            // Ожидаемая ситуация для формы: показываем сообщение, а не страницу 409.
            $this->addFlash('error', 'Обращение изменили в другом окне. Проверьте данные и сохраните ещё раз.');
        }

        return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
    }

    #[Route('/admin/leads/{id}/notes', name: 'admin_lead_note', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function note(int $id, Request $request, LeadNoteAdder $adder): Response
    {
        $this->guard($request);
        $text = trim($request->getPayload()->getString('text'));

        if ('' === $text || mb_strlen($text) > self::NOTE_MAX_LENGTH) {
            $this->addFlash('error', 'Заметка пустая или длиннее 5000 символов.');
        } else {
            $adder->add($id, $text);
            $this->addFlash('success', 'Заметка добавлена.');
        }

        return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
    }

    #[Route('/admin/leads/{id}/notify', name: 'admin_lead_notify', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function notify(int $id, Request $request, LeadNotificationSender $sender): Response
    {
        $this->guard($request);

        $sender->resend($id)
            ? $this->addFlash('success', 'Уведомление отправлено в Telegram.')
            : $this->addFlash('error', 'Уведомление не отправлено. Причина -- в карточке.');

        return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
    }

    #[Route('/admin/leads/{id}/delete', name: 'admin_lead_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, LeadEraser $eraser): Response
    {
        $this->guard($request);
        $eraser->erase($id);
        $this->addFlash('success', \sprintf('Обращение №%d удалено вместе с заметками.', $id));

        return $this->redirectToRoute('admin_lead_list');
    }

    private function guard(Request $request): void
    {
        if (!$this->isCsrfTokenValid('lead-admin', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
