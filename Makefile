# Convenience wrappers around docker compose for the NFE.io PHP SDK.
#
# All targets run inside the docker container, so you do NOT need PHP or
# Composer installed on the host. The first run will build the image
# (~2 minutes); subsequent runs reuse it.

DC := docker compose
RUN := $(DC) run --rm
PHP ?= php

.PHONY: help build install update test test-matrix stan cs cs-fix generate generate-check shell clean

help:
	@echo "NFE.io PHP SDK — dev shortcuts (all run via docker compose)"
	@echo ""
	@echo "  make build           Build all matrix images (PHP 8.2 / 8.3 / 8.4)"
	@echo "  make install         composer install"
	@echo "  make update          composer update"
	@echo "  make test            Pest under PHP 8.2 (primary)"
	@echo "  make test-matrix     Pest under PHP 8.2, 8.3, AND 8.4"
	@echo "  make stan            PHPStan analyse"
	@echo "  make cs              php-cs-fixer dry-run"
	@echo "  make cs-fix          php-cs-fixer fix (writes changes)"
	@echo "  make generate        Regenerate src/Generated/ from openapi/*.yaml"
	@echo "  make generate-check  Fail if src/Generated/ is out of sync with specs"
	@echo "  make shell           Drop into a bash shell inside the PHP 8.2 container"
	@echo "  make clean           Remove images and the composer-cache volume"
	@echo ""
	@echo "  Override the active service: 'make test PHP=php83'"

build:
	$(DC) build

install:
	$(RUN) $(PHP) composer install

update:
	$(RUN) $(PHP) composer update

test:
	$(RUN) $(PHP) composer test

test-matrix:
	$(RUN) php composer test
	$(RUN) php83 composer test
	$(RUN) php84 composer test

stan:
	$(RUN) $(PHP) composer stan

cs:
	$(RUN) $(PHP) composer cs

cs-fix:
	$(RUN) $(PHP) composer cs:fix

generate:
	$(RUN) $(PHP) composer generate

generate-check:
	$(RUN) $(PHP) composer generate:check

shell:
	$(RUN) $(PHP) bash

clean:
	$(DC) down --volumes --rmi local
