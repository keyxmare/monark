DC = docker compose -f docker/compose.yaml -f docker/compose.override.yaml
DC_PROD = docker compose -f docker/compose.yaml -f docker/compose.prod.yaml
EXEC_BACKEND = $(DC) exec -T backend
EXEC_FRONTEND = $(DC) exec -T frontend

.DEFAULT_GOAL := help

## —— Monark ——————————————————————————————————————
.PHONY: install up down build logs restart

install: ## Install project (first time setup)
	@test -f docker/.env || cp docker/.env.example docker/.env
	$(DC) build
	$(DC) up -d
	$(EXEC_BACKEND) composer install --no-interaction
	$(EXEC_FRONTEND) pnpm install
	$(EXEC_BACKEND) php bin/console doctrine:migrations:migrate --no-interaction

up: ## Start all containers
	$(DC) up -d

down: ## Stop all containers
	$(DC) down

build: ## Build all containers
	$(DC) build

logs: ## Tail container logs
	$(DC) logs -f

restart: ## Restart all containers
	$(DC) restart

## —— Backend —————————————————————————————————————
.PHONY: test-backend lint-backend coverage-backend mutation-backend quality-backend outdated-backend audit-backend fix-backend

test-backend: ## Run backend tests
	$(EXEC_BACKEND) sh -c 'php -d memory_limit=512M vendor/bin/pest --colors=never'

lint-backend: ## Lint backend code (cs-fixer + phpstan)
	$(EXEC_BACKEND) sh -c 'php vendor/bin/php-cs-fixer fix --dry-run --diff'
	$(EXEC_BACKEND) sh -c 'php -d memory_limit=512M vendor/bin/phpstan analyse'

fix-backend: ## Auto-fix backend code style
	$(EXEC_BACKEND) sh -c 'php vendor/bin/php-cs-fixer fix'

coverage-backend: ## Run backend tests with coverage
	$(EXEC_BACKEND) sh -c 'php -d memory_limit=512M -d xdebug.mode=coverage vendor/bin/pest --coverage'

mutation-backend: ## Run mutation testing (infection)
	@./scripts/mutation-backend.sh

quality-backend: lint-backend test-backend ## Full backend quality check

outdated-backend: ## Check outdated backend dependencies
	$(EXEC_BACKEND) composer outdated --direct

audit-backend: ## Audit backend dependencies
	$(EXEC_BACKEND) composer audit

## —— Frontend ————————————————————————————————————
.PHONY: test-frontend lint-frontend mutation-frontend quality-frontend outdated-frontend audit-frontend

test-frontend: ## Run frontend tests
	$(EXEC_FRONTEND) pnpm vitest run

lint-frontend: ## Lint frontend code
	$(EXEC_FRONTEND) pnpm lint
	$(EXEC_FRONTEND) pnpm format:check

mutation-frontend: ## Run frontend mutation testing
	$(EXEC_FRONTEND) pnpm mutation

quality-frontend: lint-frontend test-frontend ## Full frontend quality check

outdated-frontend: ## Check outdated frontend dependencies
	$(EXEC_FRONTEND) pnpm outdated

audit-frontend: ## Audit frontend dependencies
	$(EXEC_FRONTEND) pnpm audit

## —— Global ——————————————————————————————————————
.PHONY: test lint quality outdated audit ci

test: test-backend test-frontend ## Run all tests

lint: lint-backend lint-frontend ## Lint all code

quality: quality-backend quality-frontend ## Full quality check

outdated: outdated-backend outdated-frontend ## Check all outdated dependencies

audit: audit-backend audit-frontend ## Audit all dependencies

ci: ## CI dashboard — lint, tests, coverage, mutation
	@./scripts/ci-dashboard.sh

## —— Database ————————————————————————————————————
.PHONY: migration migrate seed

migration: ## Generate a migration
	$(EXEC_BACKEND) php bin/console doctrine:migrations:diff

migrate: ## Run migrations
	$(EXEC_BACKEND) php bin/console doctrine:migrations:migrate --no-interaction

seed: ## Load fixtures
	$(EXEC_BACKEND) php bin/console doctrine:fixtures:load --no-interaction

## —— Utilities ———————————————————————————————————
.PHONY: doctor shell-backend shell-frontend

doctor: ## Check development prerequisites
	@echo "Checking prerequisites..."
	@command -v docker >/dev/null 2>&1 && echo "  docker: OK" || echo "  docker: MISSING"
	@docker compose version >/dev/null 2>&1 && echo "  docker compose: OK" || echo "  docker compose: MISSING"
	@command -v make >/dev/null 2>&1 && echo "  make: OK" || echo "  make: MISSING"
	@command -v git >/dev/null 2>&1 && echo "  git: OK" || echo "  git: MISSING"

shell-backend: ## Open a shell in backend container
	$(DC) exec backend sh

shell-frontend: ## Open a shell in frontend container
	$(DC) exec frontend sh

## —— Help ————————————————————————————————————————
help: ## Show this help
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}' | sed -e 's/^## /\n/'
