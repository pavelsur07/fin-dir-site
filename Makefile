DC=docker compose
CLI=$(DC) --profile cli run --rm site-php-cli

UID := $(shell id -u)
GID := $(shell id -g)

.PHONY: init prepare build rebuild install update up down restart check console shell logs cache-clear clean-cache clean-local wp-up wp-down wp-logs ps

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

# WordPress / MariaDB / phpMyAdmin — только когда явно нужны
wp-up:
	$(DC) --profile wp up -d db wordpress phpmyadmin

wp-down:
	$(DC) stop db wordpress phpmyadmin

wp-logs:
	$(DC) logs -f db wordpress phpmyadmin