<?php

declare(strict_types=1);

namespace App\Documentation\Security;

use App\Documentation\DTO\DocMcpHttpSettings;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class DocMcpSecurityResponses implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface, AccessDeniedHandlerInterface
{
    public function __construct(private readonly TokenStorageInterface $tokens)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->challenge(401);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof DocMcpAuthenticationUnavailable) {
            return new JsonResponse(['error' => 'temporarily_unavailable'], 503, ['Retry-After' => '10', 'Cache-Control' => 'private, no-store']);
        }

        return $this->challenge(401, 'invalid_token');
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $user = $this->tokens->getToken()?->getUser();

        return $this->challenge(403, $user instanceof DocMcpPrincipal && !in_array('docs:read', $user->identity->scopes, true) ? 'insufficient_scope' : 'access_denied');
    }

    private function challenge(int $status, ?string $error = null): Response
    {
        $header = 'Bearer resource_metadata="'.DocMcpHttpSettings::METADATA_URL.'", scope="docs:read"';
        if (null !== $error && 'access_denied' !== $error) {
            $header .= ', error="'.$error.'"';
        }

        return new JsonResponse(['error' => $error ?? 'unauthorized'], $status, ['WWW-Authenticate' => $header, 'Cache-Control' => 'private, no-store', 'Vary' => 'Authorization']);
    }
}
