<?php

declare(strict_types=1);

namespace App\ClientCase\Controller\Admin;

use App\ClientCase\DTO\ClientCaseInput;
use App\ClientCase\Exception\CaseSlugAlreadyTaken;
use App\ClientCase\Exception\CaseSlugIsLocked;
use App\ClientCase\Form\ClientCaseType;
use App\ClientCase\Query\CaseEditData\CaseEditDataQuery;
use App\ClientCase\Service\ClientCaseSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CaseEditController extends AbstractController
{
    #[Route('/admin/cases/{id}/edit', name: 'admin_case_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function __invoke(int $id, Request $request, CaseEditDataQuery $query, ClientCaseSaver $saver): Response
    {
        $case = $query->get($id);
        $form = $this->createForm(ClientCaseType::class, ClientCaseInput::fromEditData($case), [
            'slug_locked' => $case->isSlugLocked(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ClientCaseInput $input */
            $input = $form->getData();

            // Ожидаемые ошибки ввода показываем в форме; остальное -- DomainExceptionListener.
            // После ошибки flush EntityManager закрыт: в catch-ветках только рендер, без запросов в БД.
            try {
                $saver->edit($id, $input);
                $this->addFlash('success', 'Изменения сохранены.');

                return $this->redirectToRoute('admin_case_edit', ['id' => $id]);
            } catch (CaseSlugAlreadyTaken) {
                $form->get('slug')->addError(new FormError('Этот адрес уже занят другим кейсом.'));
            } catch (CaseSlugIsLocked) {
                $form->get('slug')->addError(new FormError('Кейс публиковался: адрес больше не меняется.'));
            }
        }

        return $this->render('admin/cases/form.html.twig', [
            'form' => $form,
            'case' => $case,
        ]);
    }
}
