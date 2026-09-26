<?php

declare(strict_types=1);

namespace App\Publication\Controller\Admin;

use App\Publication\DTO\PostInput;
use App\Publication\Exception\PostSlugAlreadyTaken;
use App\Publication\Exception\PostSlugIsLocked;
use App\Publication\Exception\PostWasModified;
use App\Publication\Form\PostType;
use App\Publication\Query\PostEditData\PostEditDataQuery;
use App\Publication\Query\PostStatusActionOptions;
use App\Publication\Service\PostEditor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class PostEditController extends AbstractController
{
    #[Route('/admin/posts/{id}/edit', name: 'admin_post_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function __invoke(int $id, Request $request, PostEditDataQuery $query, PostEditor $editor): Response
    {
        $post = $query->get($id);
        $form = $this->createForm(PostType::class, PostInput::fromEditData($post), [
            'slug_locked' => $post->isSlugLocked(),
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PostInput $input */
            $input = $form->getData();

            // Ожидаемые ошибки ввода показываем в форме; остальное -- DomainExceptionListener.
            // После ошибки flush EntityManager закрыт: в catch-ветках только рендер, без запросов в БД.
            try {
                $editor->edit($id, $input);
                $this->addFlash('success', 'Изменения сохранены.');

                return $this->redirectToRoute('admin_post_edit', ['id' => $id]);
            } catch (PostSlugAlreadyTaken) {
                $form->get('slug')->addError(new FormError('Этот адрес уже занят другой статьёй.'));
            } catch (PostSlugIsLocked) {
                $form->get('slug')->addError(new FormError('Статья публиковалась: адрес больше не меняется.'));
            } catch (PostWasModified) {
                $form->addError(new FormError('Статью изменили в другом окне. Скопируйте свои правки и обновите страницу.'));
            }
        }

        return $this->render('admin/posts/form.html.twig', [
            'form' => $form,
            'post' => $post,
            'status_actions' => PostStatusActionOptions::forStatus($post->status),
        ]);
    }
}
