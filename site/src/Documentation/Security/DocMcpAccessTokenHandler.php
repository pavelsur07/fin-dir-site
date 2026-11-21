<?php

declare(strict_types=1);

namespace App\Documentation\Security;

use App\Documentation\Adapter\DocMcpOAuthVerifier;
use App\Documentation\Exception\DocMcpOAuthFailure;
use App\Documentation\Query\DocMcpClientGrantQuery;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class DocMcpAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly DocMcpOAuthVerifier $verifier, private readonly DocMcpClientGrantQuery $grants)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            $identity = $this->verifier->verify($accessToken);
        } catch (DocMcpOAuthFailure $e) {
            if ($e->unavailable) {
                throw new DocMcpAuthenticationUnavailable('Token verification unavailable.');
            }
            throw new BadCredentialsException('Invalid access token.');
        }
        try {
            $enabled = $this->grants->isEnabled(hash('sha256', $identity->issuer), $identity->clientId);
        } catch (\Throwable) {
            throw new DocMcpAuthenticationUnavailable('Client authorization unavailable.');
        }

        return new UserBadge($identity->actor(), static fn (): DocMcpPrincipal => new DocMcpPrincipal($identity, $enabled));
    }
}
