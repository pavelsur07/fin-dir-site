<?php

declare(strict_types=1);

namespace App\Lead\Controller\Admin;

use App\Lead\Exception\LeadExportInProgress;
use App\Lead\Exception\LeadWasModified;
use App\Lead\Service\LeadCrmExporter;
use App\Lead\Service\LeadEraser;
use App\Lead\Service\LeadNoteAdder;
use App\Lead\Service\LeadNotificationSender;
use App\Lead\Service\LeadStatusUpdater;
use App\Lead\ValueObject\LeadErasureReason;
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

        return $this->back($request, $id);
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
            : $this->addFlash('error', 'Уведомление не отправлено. Причина -- в карточке обращения.');

        return $this->back($request, $id);
    }

    #[Route('/admin/leads/{id}/crm-export', name: 'admin_lead_crm_export', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function crmExport(int $id, Request $request, LeadCrmExporter $exporter): Response
    {
        $this->guard($request);

        $exporter->resend($id)
            ? $this->addFlash('success', 'Обращение передано в Битрикс24.')
            : $this->addFlash('error', 'Обращение не передано в Битрикс24. Причина -- в карточке обращения.');

        return $this->back($request, $id);
    }

    #[Route('/admin/leads/{id}/delete', name: 'admin_lead_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, LeadEraser $eraser): Response
    {
        $this->guard($request);
        // Основание пишется в журнал уничтожения; истечение срока ставит только команда.
        $reason = LeadErasureReason::tryFrom($request->getPayload()->getString('reason'));
        if (null === $reason || !\in_array($reason, LeadErasureReason::manual(), true)) {
            throw new BadRequestHttpException('Unknown erasure reason.');
        }

        try {
            $erasure = $eraser->erase($id, $reason);
        } catch (LeadExportInProgress) {
            // Ожидаемая ситуация: обращение в эту секунду уходит в Битрикс24.
            $this->addFlash('error', 'Обращение сейчас передаётся в Битрикс24. Удалите его через минуту.');

            return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
        }

        $this->addFlash('success', \sprintf('Обращение №%d удалено вместе с заметками.', $id));
        if (null !== $erasure->crmLeadId()) {
            $this->addFlash('error', \sprintf('Удалите копию в Битрикс24 вручную: лид №%d.', $erasure->crmLeadId()));
        }

        return $this->back($request, null);
    }

    /**
     * Действие из меню списка возвращает в тот же список с фильтрами, иначе -- в карточку
     * (после удаления -- в список). Принимаем только адрес самого списка: произвольный
     * адрес из формы был бы open redirect.
     */
    private function back(Request $request, ?int $id): Response
    {
        $back = $request->getPayload()->getString('back');
        if (1 === preg_match('~^/admin/leads(?:\?[^\s#]*)?\z~', $back)) {
            return $this->redirect($back);
        }

        return null === $id
            ? $this->redirectToRoute('admin_lead_list')
            : $this->redirectToRoute('admin_lead_show', ['id' => $id]);
    }

    private function guard(Request $request): void
    {
        if (!$this->isCsrfTokenValid('lead-admin', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
