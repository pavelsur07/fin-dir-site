DC=docker compose
CLI=$(DC) --profile cli run --rm site-php-cli
TEST_CLI=$(DC) --profile cli run --rm \
	-v $(CURDIR)/SITE_RULES.md:/workspace/SITE_RULES.md:ro \
	-v $(CURDIR)/scripts:/workspace/scripts:ro \
	site-php-cli

# -p traefik закрепляет имя проекта, чтобы метаданные контейнера не зависели
# от имени каталога, из которого запускают make
TRAEFIK=$(DC) -p traefik -f infra/traefik/docker-compose.yml

UID := $(shell id -u)
GID := $(shell id -g)

.PHONY: init prepare build rebuild install update up down restart check console migrate diff shell logs cache-clear clean-cache clean-local ps deptrac \
        assets assets-watch asset-version assets-check lint cs cs-fix phpstan test ci \
        traefik-config traefik-network traefik-up traefik-logs traefik-ps

# Первый запуск Symfony dev после clone
init: prepare build install cache-clear up migrate check

# Подготовка локальных папок под bind mount ./site:/app
prepare:
	mkdir -p site/vendor site/var 2>/dev/null || sudo mkdir -p site/vendor site/var
	sudo chown -R $(UID):$(GID) site/vendor site/var
	chmod -R u+rwX site/vendor site/var
	mkdir -p site/vendor site/var/cache site/var/log

# Сборка только Symfony dev PHP images
build:
	$(DC) build site-php-cli site-php-fpm

# Полная пересборка, только если менялись Dockerfile или сломался Docker cache
rebuild:
	$(DC) build --no-cache site-php-cli site-php-fpm

# Composer install внутри dev CLI
install:
	$(CLI) composer install

# Composer update. Можно передать CMD="vendor/package --with-dependencies"
update:
	$(CLI) composer update $(CMD)

# DEV по умолчанию: только Symfony nginx + fpm
up:
	$(DC) up -d --remove-orphans site-php-fpm site-nginx

down:
	$(DC) down

restart:
	$(DC) restart site-nginx site-php-fpm

check:
	$(CLI) php bin/console about
	$(CLI) php bin/console dbal:run-sql 'select 1'
	$(DC) exec site-php-fpm php-fpm -t
	curl -i http://localhost:8001/health
	curl -I http://localhost:8001/

console:
	$(CLI) php bin/console $(CMD)

migrate:
	$(CLI) php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Сгенерировать миграцию по разнице между сущностями и схемой базы
diff:
	$(CLI) php bin/console doctrine:migrations:diff

shell:
	$(CLI) sh

logs:
	$(DC) logs -f site-nginx site-php-fpm

cache-clear:
	$(CLI) php bin/console cache:clear

# Границы слоёв из docs/architecture/PATTERNS.md. Конфиг: site/deptrac.yaml
deptrac:
	$(CLI) vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress

# Tailwind работает только во время сборки. Nginx раздаёт compiled website assets.
assets:
	rm -rf -- site/public/assets/website
	mkdir -p site/public/assets/website
	./scripts/tailwindcss.sh -i site/assets/styles/website/app.css -o site/public/assets/website/app.css --minify
	cp site/assets/scripts/website/navigation.js site/public/assets/website/navigation.js

assets-watch:
	mkdir -p site/public/assets/website
	cp site/assets/scripts/website/navigation.js site/public/assets/website/navigation.js
	./scripts/tailwindcss.sh -i site/assets/styles/website/app.css -o site/public/assets/website/app.css --watch

asset-version:
	@cat site/public/assets/website/app.css site/public/assets/website/navigation.js | sha256sum | cut -c1-12

assets-check:
	@temporary=$$(mktemp -d); \
	trap 'rm -rf -- "$$temporary"' EXIT HUP INT TERM; \
	./scripts/tailwindcss.sh -i site/assets/styles/website/app.css -o "$$temporary/app.css" --minify; \
	cp site/assets/scripts/website/navigation.js "$$temporary/navigation.js"; \
	diff --brief --recursive "$$temporary" site/public/assets/website >/dev/null || { \
		echo 'Compiled website assets are stale. Run make assets.' >&2; \
		exit 1; \
	}

# --- Проверки. Порядок тот же, что в .github/workflows/ci.yml: от дешёвых к дорогим ---

lint: assets-check
	$(CLI) composer validate --strict
	$(CLI) composer audit
	$(CLI) php bin/console lint:yaml config
	$(CLI) php bin/console lint:twig templates
	$(CLI) php bin/console lint:container

# Проверить стиль. Починить: make cs-fix
cs:
	$(CLI) vendor/bin/php-cs-fixer check --diff

cs-fix:
	$(CLI) vendor/bin/php-cs-fixer fix

# cache:warmup обязателен: phpstan-symfony читает контейнер из var/cache/dev
phpstan:
	$(CLI) sh -lc 'php bin/console cache:warmup && vendor/bin/phpstan analyse --no-progress'

test:
	$(TEST_CLI) vendor/bin/phpunit

# Всё, что гоняет CI
ci: lint cs phpstan deptrac test

clean-cache:
	rm -rf site/var/cache site/var/log

clean-local:
	rm -rf site/vendor site/var/cache site/var/log

ps:
	$(DC) ps

# --- Traefik (общий reverse-proxy хоста, см. infra/traefik/README.md) ---

# Проверить синтаксис и увидеть, что реально развернётся
traefik-config:
	$(TRAEFIK) config

# Общая сеть хоста. Никому не принадлежит, поэтому создаётся отдельно
traefik-network:
	docker network inspect traefik_web >/dev/null 2>&1 || docker network create traefik_web

# Применить конфиг. Том traefik_letsencrypt создаётся сам
traefik-up: traefik-network
	$(TRAEFIK) up -d

traefik-logs:
	$(TRAEFIK) logs -f traefik

traefik-ps:
	$(TRAEFIK) ps
