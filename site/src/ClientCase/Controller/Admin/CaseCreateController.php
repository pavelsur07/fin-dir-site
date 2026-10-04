<?php

declare(strict_types=1);

namespace App\ClientCase\Controller\Admin;

use App\ClientCase\DTO\ClientCaseInput;
use App\ClientCase\Exception\CaseSlugAlreadyTaken;
use App\ClientCase\Form\ClientCaseType;
use App\ClientCase\Service\ClientCaseSaver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CaseCreateController extends AbstractController
{
    #[Route('/admin/cases/new', name: 'admin_case_new', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, ClientCaseSaver $saver): Response
    {
        $input = new ClientCaseInput();
        $form = $this->createForm(ClientCaseType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $id = $saver->create($input);
                $this->addFlash('success', 'Черновик создан.');

                return $this->redirectToRoute('admin_case_edit', ['id' => $id]);
            } catch (CaseSlugAlreadyTaken) {
                // Ожидаемая ошибка ввода -- показываем у поля, а не страницей 409.
                $form->get('slug')->addError(new FormError('Этот адрес уже занят другим кейсом.'));
            }
        }

        // render() сам отдаёт 422, если форма отправлена с ошибками.
        return $this->render('admin/cases/form.html.twig', [
            'form' => $form,
            'case' => null,
        ]);
    }
}
