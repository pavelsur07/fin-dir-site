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

.PHONY: website-seo-browser-check

.PHONY: init prepare build rebuild install update up down restart check console migrate diff shell logs cache-clear clean-cache clean-local ps deptrac \
        assets assets-watch asset-version assets-check website-assets admin-assets website-assets-watch admin-assets-watch website-assets-check admin-assets-check admin-asset-version admin-browser-fixtures admin-browser-check admin-browser-run ds-check lint cs cs-fix phpstan test test-db ci \
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
	$(CLI) vendor/bin/deptrac analyse --config-file=deptrac.documentation.yaml --no-progress

# Изолированные bundles Public Website и временного Twig Admin.
assets: website-assets admin-assets

website-assets:
	rm -rf -- site/public/assets/website
	mkdir -p site/public/assets/website
	./scripts/tailwindcss.sh -i site/assets/styles/website/app.css -o site/public/assets/website/app.css --minify
	cp site/assets/scripts/website/*.js site/public/assets/website/

admin-assets:
	rm -rf -- site/public/assets/admin
	mkdir -p site/public/assets/admin
	./scripts/tailwindcss.sh -i site/assets/styles/admin/app.css -o site/public/assets/admin/app.css --minify
	cp site/assets/scripts/admin/*.js site/public/assets/admin/

assets-watch: website-assets-watch

website-assets-watch:
	mkdir -p site/public/assets/website
	cp site/assets/scripts/website/*.js site/public/assets/website/
	./scripts/tailwindcss.sh -i site/assets/styles/website/app.css -o site/public/assets/website/app.css --watch

admin-assets-watch:
	mkdir -p site/public/assets/admin
	cp site/assets/scripts/admin/*.js site/public/assets/admin/
	./scripts/tailwindcss.sh -i site/assets/styles/admin/app.css -o site/public/assets/admin/app.css --watch

asset-version:
	@cat site/public/assets/website/app.css site/public/assets/website/analytics.js site/public/assets/website/navigation.js site/public/assets/website/metrika.js site/public/assets/website/article-toc.js site/public/assets/website/bitrix24.js site/public/assets/website/tabs.js site/public/assets/website/lead-modal.js | sha256sum | cut -c1-12

admin-asset-version:
	@cat site/public/assets/admin/app.css site/public/assets/admin/row-menus.js site/public/assets/admin/tabs.js site/public/assets/admin/docs-editor.js | sha256sum | cut -c1-12

# Только тестовая БД: PHPUnit/DAMA откатывает синтетические записи.
admin-browser-fixtures: test-db
	$(TEST_CLI) env ADMIN_BROWSER_FIXTURES=/app/var/tests/admin-browser vendor/bin/phpunit tests/Admin/AdminBrowserFixturesTest.php

admin-browser-check: admin-assets-check admin-browser-fixtures
	$(MAKE) admin-browser-run

admin-browser-run:
	docker run --rm --network host -v "$(CURDIR):/workspace:ro" -v "$(CURDIR)/site/var/tests/admin-browser:/artifacts" -w /workspace mcr.microsoft.com/playwright:v1.62.1-noble sh -c 'npm install --prefix /tmp/admin-browser --no-audit --no-fund playwright@1.62.1 >/dev/null && NODE_PATH=/tmp/admin-browser/node_modules node scripts/check-admin-browser.cjs'

website-seo-browser-check:
	docker run --rm --network host -v "$(CURDIR):/workspace:ro" -w /workspace --entrypoint node zenika/alpine-chrome:with-puppeteer scripts/check-website-seo-browser.cjs

assets-check: website-assets-check admin-assets-check

website-assets-check admin-assets-check:
	@set -eu; temporary=$$(mktemp -d); \
	trap 'rm -rf -- "$$temporary"' EXIT HUP INT TERM; \
	bundle=$$(echo "$@" | cut -d- -f1); \
	./scripts/tailwindcss.sh -i "site/assets/styles/$$bundle/app.css" -o "$$temporary/app.css" --minify; \
	cp site/assets/scripts/$$bundle/*.js "$$temporary/"; \
	diff --brief --recursive "$$temporary" "site/public/assets/$$bundle" >/dev/null || { \
		echo "Compiled $$bundle assets are stale. Run make $$bundle-assets." >&2; \
		exit 1; \
	}
	@if grep -Eiq 'Inter Variable|inter-[[:alnum:]-]+[.]woff2|--font-(sans|display|mono):[^;]*Inter|font-family:[^;]*Inter' site/assets/styles/shared/*.css site/public/assets/website/app.css site/public/assets/admin/app.css; then \
		echo 'Inter font fallback found in CSS.' >&2; \
		exit 1; \
	fi
	@if find site/public/assets/fonts -maxdepth 1 -type f -iname 'inter-*.woff2' -print -quit | grep -q .; then \
		echo 'Inter font file found in assets.' >&2; \
		exit 1; \
	fi

ds-check: assets-check
	docker run --rm -v "$(CURDIR):/workspace:ro" -w /workspace node:24-alpine node scripts/check-templates.mjs site/public/assets/website/app.css site/templates/website site/assets/scripts/website site/src/Publication/Adapter site/src/Shared/Adapter site/src/Documentation/Adapter
	docker run --rm -v "$(CURDIR):/workspace:ro" -w /workspace node:24-alpine node scripts/check-templates.mjs site/public/assets/admin/app.css site/templates/admin site/assets/scripts/admin site/src/Publication/Adapter site/src/Shared/Adapter site/src/Documentation/Adapter

# --- Проверки. Порядок тот же, что в .github/workflows/ci.yml: от дешёвых к дорогим ---

lint: assets-check
	$(CLI) composer validate --strict
	$(CLI) composer audit
	$(CLI) php bin/console lint:yaml config
	$(CLI) php bin/console lint:twig templates
	$(CLI) php bin/console lint:container

# Проверить стиль. Починить: make cs-fix
cs:
	$(CLI) vendor/bin/php-cs-fixer check --diff --using-cache=no

cs-fix:
	$(CLI) vendor/bin/php-cs-fixer fix --using-cache=no

# cache:warmup обязателен: phpstan-symfony читает контейнер из var/cache/dev
phpstan:
	$(CLI) sh -lc 'php bin/console cache:warmup && vendor/bin/phpstan analyse --no-progress'

# Отдельная база site_test (dbname_suffix в doctrine.yaml when@test): тесты
# не трогают dev-данные, DAMA откатывает каждый тест транзакцией.
test-db:
	$(CLI) sh -c 'APP_ENV=test php bin/console doctrine:database:create --if-not-exists && APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration'

test: test-db
	$(TEST_CLI) vendor/bin/phpunit $(CMD)

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

# PHP release checks. Production artifacts use separate tags and an isolated DB.
PHP_FPM_IMAGE ?= vf-check/php-fpm:8.4.27
PHP_CLI_IMAGE ?= vf-check/php-cli:8.4.27
export PHP_FPM_IMAGE PHP_CLI_IMAGE
PHP_SMOKE = $(DC) -p vf-php-upgrade-smoke -f docker-compose.php-smoke.yml
PROD_DC = $(DC) -p vashfindir -f docker-compose.prod.yml
PHP_RUNTIME = -v "$(CURDIR)/scripts/check-php-runtime.php:/check-php-runtime.php:ro"

.PHONY: php-runtime-check php-production-build php-production-check php-production-smoke php-deployed-check php-production-rollback

php-runtime-check:
	docker run --rm $(PHP_RUNTIME) vashfindir-site-php-cli:dev php /check-php-runtime.php
	docker run --rm $(PHP_RUNTIME) vashfindir-site-php-fpm:dev php /check-php-runtime.php
	docker run --rm vashfindir-site-php-fpm:dev sh -ec 'php-fpm -v | grep -F "PHP 8.4.27 (fpm-fcgi)"; php-fpm -t'
	$(CLI) composer check-platform-reqs

php-production-build:
	docker build --pull -f site/docker/production/php-fpm/Dockerfile -t $(PHP_FPM_IMAGE) site
	docker build --pull -f site/docker/production/php-cli/Dockerfile -t $(PHP_CLI_IMAGE) site

php-production-check:
	docker run --rm $(PHP_RUNTIME) $(PHP_FPM_IMAGE) php /check-php-runtime.php
	docker run --rm $(PHP_RUNTIME) $(PHP_CLI_IMAGE) php /check-php-runtime.php
	docker run --rm $(PHP_RUNTIME) $(PHP_FPM_IMAGE) php /check-php-runtime.php production-fpm
	docker run --rm $(PHP_FPM_IMAGE) sh -ec 'php-fpm -v | grep -F "PHP 8.4.27 (fpm-fcgi)"; php-fpm -t'

# Always clean up this dedicated project, including on a failed smoke test.
php-production-smoke: php-production-check documentation-mcp-http-check
	@set -eu; trap '$(PHP_SMOKE) down --volumes' EXIT HUP INT TERM; \
	$(PHP_SMOKE) up -d --wait site-postgres; \
	$(PHP_SMOKE) run --rm site-php-cli php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; \
	$(PHP_SMOKE) run --rm site-php-cli php bin/console cache:warmup; \
	$(PHP_SMOKE) run --rm -T site-php-cli php < scripts/check-documentation-mcp.php; \
	$(PHP_SMOKE) up -d --wait --scale site-php-fpm=2 site; \
	python3 scripts/check-php-http.py

# Deployed version check covers every replica, not only Compose exec's default.
php-deployed-check:
	@set -eu; ids=$$($(PROD_DC) ps -q site-php-fpm); test "$$(echo "$$ids" | wc -w)" -eq 2; \
	for container in $$ids; do \
		docker exec -i "$$container" php < scripts/check-php-runtime.php; \
		docker exec "$$container" sh -ec 'php-fpm -v | grep -F "PHP 8.4.27 (fpm-fcgi)"; php-fpm -t'; \
	done
	$(PROD_DC) run --rm -T site-php-cli php < scripts/check-php-runtime.php
	$(PROD_DC) run --rm -T site-php-cli php < scripts/check-documentation-mcp.php

# Emergency use with compatible checkout and exported production environment.
# Both recorded image digests required. May briefly interrupt PHP requests.
export PHP_ROLLBACK_FPM_IMAGE PHP_ROLLBACK_CLI_IMAGE
ROLLBACK_DC = $(PROD_DC) -f docker-compose.php-rollback.yml
php-production-rollback:
	@printf '%s\n' "$(PHP_ROLLBACK_FPM_IMAGE)" | grep -Eq '^ghcr[.]io/[^/]+/vashfindir-site-php-fpm@sha256:[0-9a-f]{64}$$' || { echo 'Recorded FPM image@sha256 digest is required' >&2; exit 1; }
	@printf '%s\n' "$(PHP_ROLLBACK_CLI_IMAGE)" | grep -Eq '^ghcr[.]io/[^/]+/vashfindir-site-php-cli@sha256:[0-9a-f]{64}$$' || { echo 'Recorded CLI image@sha256 digest is required' >&2; exit 1; }
	$(ROLLBACK_DC) pull site-php-fpm site-php-cli
	$(ROLLBACK_DC) up -d --no-deps --wait --scale site-php-fpm=2 site-php-fpm
	$(ROLLBACK_DC) exec -T site sh -ec 'nginx -t; nginx -s reload; wget -qO- http://127.0.0.1/health | grep -qx alive'
	$(ROLLBACK_DC) run --rm site-php-cli php bin/console dbal:run-sql 'select 1'

# Documentation: browser uses HTML from rolled-back test fixtures, never production.
.PHONY: documentation-browser-fixtures documentation-browser-check documentation-http-check
documentation-browser-fixtures: test-db
	$(TEST_CLI) env DOCS_BROWSER_FIXTURES=/app/var/tests/docs-browser vendor/bin/phpunit tests/Documentation/Controller/DocumentationBrowserFixturesTest.php

documentation-browser-check: assets-check documentation-browser-fixtures
	docker run --rm --network host -v "$(CURDIR):/workspace:ro" -v "$(CURDIR)/site/var/tests/docs-browser:/artifacts" -w /workspace mcr.microsoft.com/playwright:v1.62.1-noble sh -c 'npm install --prefix /tmp/docs-browser --no-audit --no-fund playwright@1.62.1 >/dev/null && NODE_PATH=/tmp/docs-browser/node_modules node scripts/check-documentation-browser.cjs'

documentation-http-check:
	python3 scripts/check-documentation-http.py

# Backup is mandatory before production migrations. The directory is outside the checkout.
.PHONY: site-database-backup site-database-backup-check site-database-backup-script
site-database-backup:
	./scripts/backup-site-database.sh

site-database-backup-script:
	@cat scripts/backup-site-database.sh

site-database-backup-check:
	@set -eu; backup_check_dir=$$(mktemp -d); backup_check_container="vf-backup-smoke-$$$$"; \
	trap 'docker rm -f "$$backup_check_container" >/dev/null 2>&1 || true; rm -rf -- "$$backup_check_dir"' EXIT HUP INT TERM; \
	docker run -d --network none --name "$$backup_check_container" --label "com.docker.compose.project=$$backup_check_container" --label com.docker.compose.service=site-postgres --tmpfs /var/lib/postgresql/data -e POSTGRES_USER=smoke -e POSTGRES_PASSWORD=smoke -e POSTGRES_DB=smoke postgres:17-alpine >/dev/null; \
	backup_wait=0; until docker exec "$$backup_check_container" pg_isready -h 127.0.0.1 -U smoke -d smoke >/dev/null 2>&1; do backup_wait=$$((backup_wait+1)); test "$$backup_wait" -lt 30; sleep 1; done; \
	docker exec "$$backup_check_container" psql -U smoke -d smoke -v ON_ERROR_STOP=1 -c "CREATE TABLE backup_probe (marker text NOT NULL); INSERT INTO backup_probe VALUES ('backup-roundtrip');" >/dev/null; \
	VF_BACKUP_DIR="$$backup_check_dir" VF_BACKUP_PROJECT="$$backup_check_container" VF_BACKUP_RELEASE=smoke ./scripts/backup-site-database.sh; \
	docker exec "$$backup_check_container" createdb -U smoke backup_restore; \
	docker exec -i "$$backup_check_container" pg_restore --exit-on-error -U smoke -d backup_restore < "$$(find "$$backup_check_dir" -name '*.dump' -type f)"; \
	test "$$(docker exec "$$backup_check_container" psql -U smoke -d backup_restore -Atc 'SELECT marker FROM backup_probe')" = backup-roundtrip; \
	test "$$(stat -c '%a' "$$(find "$$backup_check_dir" -name '*.dump' -type f)")" = 600; \
	if VF_BACKUP_DIR="$$backup_check_dir" VF_BACKUP_PROJECT=nonexistent-backup-project ./scripts/backup-site-database.sh; then echo 'Missing PostgreSQL must fail the backup' >&2; exit 1; fi; \
	echo 'Database backup and real restore passed; missing database rejected.'

# Trusted local stdio: stdout is reserved for MCP frames. No TTY, stdin stays open.
.PHONY: documentation-mcp
documentation-mcp:
	@$(DC) --profile cli run --rm -T --interactive -e DOCS_MCP_ENABLED -e DOCS_MCP_ACTOR -e DOCS_MCP_PROFILE site-php-cli php bin/console app:docs:mcp --no-interaction --no-ansi --no-debug

.PHONY: documentation-mcp-production
documentation-mcp-production:
	@printf '%s\n' "$(IMAGE_TAG)" | grep -Eq '^sha-[0-9a-f]{40}$$' || { echo 'IMAGE_TAG must be the deployed sha-<40-character commit SHA> tag.' >&2; exit 1; }
	@ids=$$($(PROD_DC) ps -q site-php-fpm); test -n "$$ids" || { echo 'No running production PHP replicas.' >&2; exit 1; }; for container in $$ids; do docker inspect --format '{{.Config.Image}}' "$$container" | grep -Eq ':$(IMAGE_TAG)$$' || { echo 'IMAGE_TAG differs from a running production PHP replica.' >&2; exit 1; }; done
	@$(PROD_DC) run --rm -T --interactive --no-deps --pull never -e DOCS_MCP_ENABLED -e DOCS_MCP_ACTOR -e DOCS_MCP_PROFILE site-php-cli php bin/console app:docs:mcp --no-interaction --no-ansi --no-debug

# Isolated, no-dev HTTP SDK check with ephemeral OAuth issuer and two FPM replicas.
MCP_HTTP_SMOKE_EXTRA ?=
MCP_HTTP_SMOKE = $(DC) -p vf-mcp-http-smoke -f docker-compose.php-smoke.yml -f docker-compose.mcp-http-smoke.yml $(MCP_HTTP_SMOKE_EXTRA)
.PHONY: documentation-mcp-http-check
documentation-mcp-http-check: php-production-check
	@set -eu; \
	MCP_SMOKE_FIXTURES=$$(mktemp -d /tmp/vf-mcp-http.XXXXXX); export MCP_SMOKE_FIXTURES; \
	trap '$(MCP_HTTP_SMOKE) down --volumes; rm -rf "$$MCP_SMOKE_FIXTURES"' EXIT HUP INT TERM; \
	chmod 755 "$$MCP_SMOKE_FIXTURES"; \
	docker run --rm --user "$(UID):$(GID)" -v "$$MCP_SMOKE_FIXTURES:/fixtures" -v "$(CURDIR)/scripts/mcp-http-smoke/generate-fixtures.php:/generate-fixtures.php:ro" -v "$(CURDIR)/site/docker/common/nginx/default.conf:/smoke-nginx.conf:ro" $(PHP_CLI_IMAGE) php /generate-fixtures.php /fixtures; \
	$(MCP_HTTP_SMOKE) up -d --wait site-postgres mcp-issuer; \
	$(MCP_HTTP_SMOKE) run --rm site-php-cli php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; \
	$(MCP_HTTP_SMOKE) up -d --wait --scale site-php-fpm=2 site; \
	$(MCP_HTTP_SMOKE) run --rm site-php-cli php /check-mcp-http.php
