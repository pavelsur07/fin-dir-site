<?php

declare(strict_types=1);

// ponytail: без Dotenv. Файлы .env в этом репозитории игнорируются (корневой
// .gitignore), переменные приходят из compose и CI. Для тестов они заданы
// в phpunit.dist.xml -- это единственный источник.
require dirname(__DIR__).'/vendor/autoload.php';
