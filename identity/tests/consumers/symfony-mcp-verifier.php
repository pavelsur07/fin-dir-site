<?php

declare(strict_types=1);

require '/app/vendor/autoload.php';

use App\Documentation\Adapter\DocMcpIssuerHttpClient;
use App\Documentation\Adapter\DocMcpOAuthVerifier;
use App\Documentation\DTO\DocMcpHttpSettings;
use App\Documentation\Exception\DocMcpOAuthFailure;
use Firebase\JWT\JWT;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpClient\HttpClient;

try {
    $fixture = is_file('/runtime/consumer-settings.json') ? json_decode(file_get_contents('/runtime/consumer-settings.json'), true, flags: JSON_THROW_ON_ERROR) : ['base' => 'https://identity.test', 'clientId' => 'docs-mcp-reader-smoke'];
    $settings = new DocMcpHttpSettings(true, $fixture['base'].'/realms/projects', [], [], '/runtime/ca.crt');
    $verify = new DocMcpOAuthVerifier($settings, new DocMcpIssuerHttpClient(HttpClient::create(), $settings), new ArrayAdapter(), new NativeClock());
    $token = trim(file_get_contents('/runtime/consumer-token'));
    if (in_array('--expect-unavailable', $argv, true)) {
        try {
            $verify->verify($token);
            throw new RuntimeException('Unavailable issuer was accepted.');
        } catch (DocMcpOAuthFailure $error) {
            if (!$error->unavailable) {
                throw new RuntimeException('Issuer outage was not classified as unavailable.');
            }
        }
        echo "Actual Symfony verifier fails closed during issuer outage.\n";
        exit(0);
    }
    $identity = $verify->verify($token);
    if ($fixture['clientId'] !== $identity->clientId || !in_array('docs:read', $identity->scopes, true)) {
        throw new RuntimeException('Real issuer returned an unexpected identity.');
    }
    $parts = explode('.', $token);
    $parts[2] = ('a' === $parts[2][0] ? 'b' : 'a').substr($parts[2], 1);
    try {
        $verify->verify(implode('.', $parts));
        throw new RuntimeException('Corrupted token was accepted.');
    } catch (DocMcpOAuthFailure) {
    }
    JWT::$timestamp = time() + 600;
    try {
        $verify->verify($token);
        throw new RuntimeException('Expired token was accepted.');
    } catch (DocMcpOAuthFailure) {
    } finally {
        JWT::$timestamp = null;
    }
    if (is_file('/runtime/consumer-other-token')) {
        try {
            $verify->verify(trim(file_get_contents('/runtime/consumer-other-token')));
            throw new RuntimeException('Other API token was accepted.');
        } catch (DocMcpOAuthFailure) {
        }
    }
    echo "Actual Symfony MCP verifier: Keycloak identity, signature, expiry and audience passed.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Symfony consumer probe failed: '.$error::class.".\n");
    exit(1);
}
