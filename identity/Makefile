SHELL := /bin/sh
IDENTITY_ENV ?= dev
ifeq ($(filter $(IDENTITY_ENV),dev smoke staging production),)
$(error IDENTITY_ENV must be dev, smoke, staging or production)
endif
IDENTITY_RUNTIME_DIR ?= $(CURDIR)/.runtime/$(IDENTITY_ENV)
IDENTITY_CONSUMER_IMAGE ?= vf-check/docs-mcp-http-cli:local
IDENTITY_KEYCLOAK_IMAGE ?= vf-check/identity-keycloak:local
IDENTITY_TOOLING_IMAGE ?= vf-check/identity-tools:local
export IDENTITY_RUNTIME_DIR
include versions.env
export IDENTITY_POSTGRES_IMAGE IDENTITY_TRAEFIK_IMAGE
DC = docker compose -p identity-$(IDENTITY_ENV) --env-file versions.env
ifeq ($(IDENTITY_ENV),production)
DC += --env-file "$(IDENTITY_RUNTIME_DIR)/release.env" -f compose.yaml -f compose.prod.yaml
else
DC += -f compose.yaml -f compose.smoke.yaml
endif
OPERATOR = $(DC) run --rm --user "$$(id -u):$$(id -g)" operator
.PHONY: init build up down validate test ci project-plan project-apply bootstrap smoke backup rotate-client contract-probe runtime-apply package restore-check deploy unit
init:
	@mkdir -p "$(IDENTITY_RUNTIME_DIR)"
	docker run --rm --user "$$(id -u):$$(id -g)" -v "$(CURDIR):/identity:ro" -v "$(IDENTITY_RUNTIME_DIR):/runtime" $(IDENTITY_TOOLING_IMAGE) /identity/scripts/init_runtime.py /runtime $(if $(filter production,$(IDENTITY_ENV)),--production,)
build:
	docker build --build-arg IDENTITY_REVISION=$(if $(IDENTITY_RELEASE),$(IDENTITY_RELEASE),local) -t $(IDENTITY_KEYCLOAK_IMAGE) -f docker/keycloak/Dockerfile .
	docker build --build-arg IDENTITY_REVISION=$(if $(IDENTITY_RELEASE),$(IDENTITY_RELEASE),local) -t $(IDENTITY_TOOLING_IMAGE) -f tooling/Dockerfile .
up: init
	@test "$(IDENTITY_ENV)" = dev || { echo 'Ordinary up is dev-only; use the release procedure.' >&2; exit 1; }
	$(DC) up -d --wait --wait-timeout 180 db keycloak ingress
down:
	@test "$(IDENTITY_ENV)" = dev || { echo 'Ordinary down is dev-only; production shutdown is an explicit incident operation.' >&2; exit 1; }
	$(DC) down
validate:
	python3 scripts/validate_topology.py
	python3 scripts/ops_host.py config --environment $(IDENTITY_ENV) --runtime "$(IDENTITY_RUNTIME_DIR)"
	docker run --rm --network none -v "$(CURDIR):/identity:ro" $(IDENTITY_TOOLING_IMAGE) scripts/validate.py
bootstrap:
	$(OPERATOR) scripts/bootstrap.py
project-plan:
	$(OPERATOR) scripts/provision.py plan --environment $(IDENTITY_ENV) --manifest config/projects/$(IDENTITY_PROJECT).json --secret-dir /runtime/clients
project-apply:
	$(OPERATOR) scripts/provision.py apply --environment $(IDENTITY_ENV) --manifest config/projects/$(IDENTITY_PROJECT).json --secret-dir /runtime/clients
rotate-client:
	$(OPERATOR) scripts/rotate_client.py --environment $(IDENTITY_ENV) --manifest config/projects/$(IDENTITY_PROJECT).json --client $(IDENTITY_CLIENT)
smoke:
	$(OPERATOR) scripts/smoke.py --private
backup:
	python3 scripts/ops_host.py backup --environment $(IDENTITY_ENV) --runtime "$(IDENTITY_RUNTIME_DIR)"
test ci:
	python3 scripts/run_checks.py --consumer-image $(IDENTITY_CONSUMER_IMAGE)
unit:
	docker run --rm --network none -v "$(CURDIR):/identity:ro" $(IDENTITY_TOOLING_IMAGE) -m pytest -p no:cacheprovider tests/test_release_package.py tests/test_deployment_boundary.py tests/test_backup_archive.py tests/test_ci_impact.py -q --tb=short --show-capture=no
contract-probe:
	docker run --rm --network identity-$(IDENTITY_ENV)_default -v "$(IDENTITY_RUNTIME_DIR):/runtime:ro" -v "$(CURDIR)/tests/consumers/symfony-mcp-verifier.php:/probe.php:ro" $(IDENTITY_CONSUMER_IMAGE) php /probe.php
runtime-apply:
	python3 scripts/ops_host.py runtime-apply --environment $(IDENTITY_ENV) --runtime "$(IDENTITY_RUNTIME_DIR)"
package:
	python3 scripts/package_release.py --revision $(IDENTITY_RELEASE) --output "$(IDENTITY_PACKAGE)"
restore-check:
	python3 scripts/restore_check.py --manifest "$(IDENTITY_BACKUP_MANIFEST)" --key-file "$(IDENTITY_BACKUP_KEY_FILE)" --consumer-image "$(IDENTITY_CONSUMER_IMAGE)"
deploy:
	@test "$(IDENTITY_ENV)" = production
	gh workflow run deploy-identity.yml --repo pavelsur07/fin-dir-site --ref master -f revision=$(IDENTITY_RELEASE)
