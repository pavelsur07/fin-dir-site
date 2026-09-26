<?php

declare(strict_types=1);

namespace App\Publication\Controller\Admin;

use App\Publication\Query\AdminPostList\AdminPostListCriteria;
use App\Publication\Query\AdminPostList\AdminPostListQuery;
use App\Publication\Query\AdminPostList\AdminPostSort;
use App\Publication\Query\PostStatusActionOptions;
use App\Publication\ValueObject\PostStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class PostListController extends AbstractController
{
    #[Route('/admin/posts', name: 'admin_post_list', methods: ['GET'])]
    public function __invoke(Request $request, AdminPostListQuery $query): Response
    {
        // getEnum/getInt отвечают 400 на неизвестный статус, сортировку и нечисловую страницу.
        $criteria = new AdminPostListCriteria(
            $request->query->getEnum('status', PostStatus::class),
            $request->query->getEnum('sort', AdminPostSort::class) ?? AdminPostSort::UPDATED,
            $request->query->getInt('page', 1),
        );

        $statusActions = [];
        foreach (PostStatus::cases() as $status) {
            $statusActions[$status->value] = PostStatusActionOptions::forStatus($status);
        }

        return $this->render('admin/posts/list.html.twig', [
            'pager' => $query->paginate($criteria),
            'criteria' => $criteria,
            'statuses' => PostStatus::cases(),
            'sorts' => AdminPostSort::cases(),
            'status_actions' => $statusActions,
        ]);
    }
}
