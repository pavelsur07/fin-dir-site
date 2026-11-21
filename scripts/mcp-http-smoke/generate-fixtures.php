<?php

declare(strict_types=1);

require '/app/vendor/autoload.php';

use Firebase\JWT\JWT;

$dir = $argv[1] ?? throw new RuntimeException('Fixture directory required.');
foreach (['public/.well-known', 'tls'] as $child) {
    if (!is_dir($dir.'/'.$child) && !mkdir($dir.'/'.$child, 0755, true)) {
        throw new RuntimeException('Cannot create fixture directory.');
    }
}
$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
if (false === $key) {
    throw new RuntimeException('Cannot generate ephemeral signing key.');
}
$details = openssl_pkey_get_details($key);
if (false === $details) {
    throw new RuntimeException('Cannot inspect ephemeral signing key.');
}
$b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
$jwk = ['kty' => 'RSA', 'kid' => 'smoke-key', 'use' => 'sig', 'alg' => 'RS256', 'n' => $b64($details['rsa']['n']), 'e' => $b64($details['rsa']['e'])];
file_put_contents($dir.'/public/jwks', json_encode(['keys' => [$jwk]], JSON_THROW_ON_ERROR));
file_put_contents($dir.'/public/.well-known/oauth-authorization-server', json_encode(['issuer' => 'https://mcp-issuer', 'jwks_uri' => 'https://mcp-issuer/jwks'], JSON_THROW_ON_ERROR));
// Synthetic credentials last only for this isolated smoke. Never print them.
$claims = ['iss' => 'https://mcp-issuer', 'aud' => 'https://vashfindir.ru/mcp/docs', 'iat' => time() - 1, 'exp' => time() + 299, 'client_id' => 'smoke-reader', 'scope' => 'docs:read'];
$tokens = ['valid' => JWT::encode($claims, $key, 'RS256', 'smoke-key', ['typ' => 'at+jwt']), 'wrong_audience' => JWT::encode(array_replace($claims, ['aud' => 'https://other.example']), $key, 'RS256', 'smoke-key', ['typ' => 'at+jwt']), 'no_scope' => JWT::encode(array_replace($claims, ['scope' => 'admin']), $key, 'RS256', 'smoke-key', ['typ' => 'at+jwt'])];
file_put_contents($dir.'/tokens.json', json_encode($tokens, JSON_THROW_ON_ERROR));
// Separate TLS key. The self-signed smoke CA is trusted only via the explicit CA mount.
$tls = openssl_pkey_new(['private_key_bits' => 2048]);
if (false === $tls) {
    throw new RuntimeException('Cannot generate ephemeral TLS key.');
}
$config = $dir.'/openssl.cnf';
file_put_contents($config, "[req]\ndistinguished_name=dn\n[dn]\n[extensions]\nsubjectAltName=DNS:mcp-issuer\nbasicConstraints=critical,CA:TRUE\nkeyUsage=critical,keyCertSign,digitalSignature,keyEncipherment\n");
$options = ['config' => $config, 'x509_extensions' => 'extensions', 'digest_alg' => 'sha256'];
$csr = openssl_csr_new(['commonName' => 'mcp-issuer'], $tls, $options);
$cert = false === $csr ? false : openssl_csr_sign($csr, null, $tls, 1, $options);
if (false === $cert || !openssl_x509_export_to_file($cert, $dir.'/tls/issuer.crt') || !openssl_pkey_export_to_file($tls, $dir.'/tls/issuer.key')) {
    throw new RuntimeException('Cannot create ephemeral TLS certificate.');
}
$nginx = file_get_contents('/smoke-nginx.conf');
if (false === $nginx) {
    throw new RuntimeException('Cannot read smoke nginx template.');
}
file_put_contents($dir.'/nginx.conf', str_replace('fastcgi_pass $fpm_upstream;', "fastcgi_pass \$fpm_upstream;\n        add_header X-Smoke-Upstream \$upstream_addr always;", $nginx));
echo "Ephemeral OAuth/TLS smoke fixtures generated.\n";
