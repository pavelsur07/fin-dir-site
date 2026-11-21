<?php

declare(strict_types=1);

namespace App\Documentation\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

final class DocMcpTokenExtractor implements AccessTokenExtractorInterface
{
    public function extractAccessToken(Request $request): ?string
    {
        $values = $request->headers->all('Authorization');
        if (1 !== count($values) || !preg_match('/^Bearer +([A-Za-z0-9._~+\/-]+=*) *$/iD', $values[0] ?? '', $match)) {
            return null;
        }

        return $match[1];
    }
}
