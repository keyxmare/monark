DC_EXEC = docker compose exec -T backend

.DEFAULT_GOAL := help

.PHONY: install test test-coverage test-mutation lint lint-fix phpstan rector rector-dry deptrac audit outdated quality db-migrate db-fixtures db-reset help

install: ## Install dependencies
	$(DC_EXEC) composer install --no-interaction --prefer-dist

test: ## Run tests
	$(DC_EXEC) vendor/bin/pest

test-coverage: ## Run tests with coverage
	$(DC_EXEC) vendor/bin/pest --coverage --min=80

test-mutation: ## Run mutation testing
	$(DC_EXEC) vendor/bin/infection --min-msi=60 --min-covered-msi=80 --threads=4

lint: ## Run PHP-CS-Fixer (dry-run)
	$(DC_EXEC) vendor/bin/php-cs-fixer fix --dry-run --diff

lint-fix: ## Run PHP-CS-Fixer (fix)
	$(DC_EXEC) vendor/bin/php-cs-fixer fix

phpstan: ## Run PHPStan
	$(DC_EXEC) vendor/bin/phpstan analyse

rector: ## Run Rector
	$(DC_EXEC) vendor/bin/rector process

rector-dry: ## Run Rector (dry-run)
	$(DC_EXEC) vendor/bin/rector process --dry-run

deptrac: ## Run Deptrac
	$(DC_EXEC) vendor/bin/deptrac analyse

audit: ## Run security audit
	$(DC_EXEC) composer audit

outdated: ## Check outdated packages
	$(DC_EXEC) composer outdated --direct

quality: lint phpstan deptrac test ## Run all quality checks

db-migrate: ## Run database migrations
	$(DC_EXEC) bin/console doctrine:migrations:migrate --no-interaction

db-fixtures: ## Load fixtures
	$(DC_EXEC) bin/console doctrine:fixtures:load --no-interaction

db-reset: ## Reset database (drop + create + migrate + fixtures)
	$(DC_EXEC) bin/console doctrine:database:drop --force --if-exists
	$(DC_EXEC) bin/console doctrine:database:create
	$(DC_EXEC) bin/console doctrine:migrations:migrate --no-interaction
	$(DC_EXEC) bin/console doctrine:fixtures:load --no-interaction

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
