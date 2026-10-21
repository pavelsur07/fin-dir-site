<?php

declare(strict_types=1);

// Runs in CI, dev images, production images and each deployed FPM replica.
if (PHP_VERSION !== '8.4.27') {
    fwrite(STDERR, 'Expected PHP 8.4.27, got '.PHP_VERSION.PHP_EOL);
    exit(1);
}

foreach (['pdo_pgsql', 'pgsql', 'zip', 'bcmath', 'dom', 'xml', 'mbstring', 'session', 'Zend OPcache'] as $extension) {
    if (!extension_loaded($extension)) {
        fwrite(STDERR, 'Missing extension: '.$extension.PHP_EOL);
        exit(1);
    }
}

if (($argv[1] ?? '') === 'production-fpm') {
    if (posix_geteuid() !== 1000 || !is_writable('/app/var/cache') || !is_writable('/app/public/uploads')) {
        fwrite(STDERR, 'Production app user cannot write cache/uploads'.PHP_EOL);
        exit(1);
    }
    if (!ini_get('opcache.enable') || ini_get('opcache.validate_timestamps')) {
        fwrite(STDERR, 'Unexpected production OPcache configuration'.PHP_EOL);
        exit(1);
    }
}

echo 'PHP '.PHP_VERSION.': required extensions loaded'.PHP_EOL;
