<?php

declare(strict_types=1);

namespace App\Documentation\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class DocMcpAuthenticationUnavailable extends AuthenticationException
{
}
