<?php

declare(strict_types=1);

namespace App\Publication\Controller\Admin;

use App\Publication\DTO\PostInput;
use App\Publication\Exception\PostSlugAlreadyTaken;
use App\Publication\Form\PostType;
use App\Publication\Service\PostCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class PostCreateController extends AbstractController
{
    #[Route('/admin/posts/new', name: 'admin_post_new', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, PostCreator $creator): Response
    {
        $input = new PostInput();
        $form = $this->createForm(PostType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $id = $creator->create($input);
                $this->addFlash('success', 'Черновик создан.');

                return $this->redirectToRoute('admin_post_edit', ['id' => $id]);
            } catch (PostSlugAlreadyTaken) {
                // Ожидаемая ошибка ввода -- показываем у поля, а не страницей 409.
                $form->get('slug')->addError(new FormError('Этот адрес уже занят другой статьёй.'));
            }
        }

        // render() сам отдаёт 422, если форма отправлена с ошибками.
        return $this->render('admin/posts/form.html.twig', [
            'form' => $form,
            'post' => null,
        ]);
    }
}
