SHELL := /bin/sh

DC := docker compose
SITE_CLI := $(DC) run --rm site-php-cli
PROD_DC := docker compose -f docker-compose.prod.yml
PROD_SITE_CLI := $(PROD_DC) run --rm site-php-cli

.PHONY: init up down down-clear pull build build-pull ps logs \
        site-init site-clear site-composer-install site-composer-update site-composer-validate \
        site-console site-cache-clear site-cache-warmup site-router site-lint site-lint-twig site-lint-container \
        site-shell site-logs site-health wp-health check \
        prod-config prod-build prod-up prod-down prod-ps prod-logs \
        prod-site-console prod-site-cache-clear prod-site-cache-warmup prod-site-router \
        prod-site-lint-container prod-site-composer-validate prod-site-composer-show

init: down-clear build site-init up site-health wp-health

up:
	$(DC) up -d

down:
	$(DC) down --remove-orphans

down-clear:
	$(DC) down -v --remove-orphans

pull:
	$(DC) pull

build:
	$(DC) build

build-pull:
	$(DC) build --pull

ps:
	$(DC) ps

logs:
	$(DC) logs -f

site-init: site-composer-install site-cache-clear site-cache-warmup site-lint

site-clear:
	$(SITE_CLI) sh -c 'rm -rf var/cache/* var/log/*'

site-composer-install:
	$(SITE_CLI) composer install

site-composer-update:
	$(SITE_CLI) composer update --with-all-dependencies

site-composer-validate:
	$(SITE_CLI) composer validate

site-console:
	$(SITE_CLI) php bin/console

site-cache-clear:
	$(SITE_CLI) php bin/console cache:clear

site-cache-warmup:
	$(SITE_CLI) php bin/console cache:warmup

site-router:
	$(SITE_CLI) php bin/console debug:router

site-lint: site-composer-validate site-lint-twig site-lint-container

site-lint-twig:
	$(SITE_CLI) php bin/console lint:twig templates

site-lint-container:
	$(SITE_CLI) php bin/console lint:container

site-shell:
	$(DC) run --rm site-php-cli sh

site-logs:
	$(DC) logs -f site

site-health:
	curl -fsS http://localhost:8001 | grep -q "Ваш Финдир"
	@echo "✓ Symfony site is available at http://localhost:8001"

wp-health:
	curl -fsSI http://localhost:8000 > /dev/null
	@echo "✓ WordPress is available at http://localhost:8000"

check: site-lint site-health wp-health

prod-config:
	$(PROD_DC) config

prod-build:
	$(PROD_DC) build --pull

prod-up:
	$(PROD_DC) up -d

prod-down:
	$(PROD_DC) down --remove-orphans

prod-ps:
	$(PROD_DC) ps

prod-logs:
	$(PROD_DC) logs -f

prod-site-console:
	$(PROD_SITE_CLI) php bin/console

prod-site-cache-clear:
	$(PROD_SITE_CLI) php bin/console cache:clear --env=prod --no-debug

prod-site-cache-warmup:
	$(PROD_SITE_CLI) php bin/console cache:warmup --env=prod --no-debug

prod-site-router:
	$(PROD_SITE_CLI) php bin/console debug:router --env=prod --no-debug

prod-site-lint-container:
	$(PROD_SITE_CLI) php bin/console lint:container --env=prod --no-debug

prod-site-composer-validate:
	$(PROD_SITE_CLI) composer validate

prod-site-composer-show:
	$(PROD_SITE_CLI) composer show
