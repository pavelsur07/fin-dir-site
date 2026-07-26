DC=docker compose
CLI=$(DC) --profile cli run --rm site-php-cli

# -p traefik закрепляет имя проекта, чтобы метаданные контейнера не зависели
# от имени каталога, из которого запускают make
TRAEFIK=$(DC) -p traefik -f infra/traefik/docker-compose.yml

UID := $(shell id -u)
GID := $(shell id -g)

.PHONY: init prepare build rebuild install update up down restart check console shell logs cache-clear clean-cache clean-local ps \
        traefik-config traefik-network traefik-up traefik-logs traefik-ps

# Первый запуск Symfony dev после clone
init: prepare build install cache-clear up check

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

shell:
	$(CLI) sh

logs:
	$(DC) logs -f site-nginx site-php-fpm

cache-clear:
	$(CLI) php bin/console cache:clear

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
