<?php

declare(strict_types=1);

namespace App\Documentation\Controller\Mcp;

use App\Documentation\Mcp\Http\DocMcpHttpGateway;
use App\Documentation\Security\DocMcpPrincipal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_DOCS_MCP')]
final class DocMcpHttpController extends AbstractController
{
    #[Route('/mcp/docs', name: 'docs_mcp_http', host: 'vashfindir.ru', methods: ['POST', 'GET', 'DELETE', 'OPTIONS'], priority: 400)]
    public function __invoke(Request $request, DocMcpHttpGateway $gateway): Response
    {
        $user = $this->getUser();
        if (!$user instanceof DocMcpPrincipal) {
            throw $this->createAccessDeniedException();
        }

        return $gateway->handle($request, $user->identity);
    }
}
