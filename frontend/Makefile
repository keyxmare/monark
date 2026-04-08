DC_EXEC = docker compose exec -T frontend

.DEFAULT_GOAL := help

.PHONY: install dev build test test-coverage lint lint-fix format outdated quality help

install:
	$(DC_EXEC) pnpm install

dev:
	$(DC_EXEC) pnpm dev

build:
	$(DC_EXEC) pnpm build

test:
	$(DC_EXEC) pnpm test

test-coverage:
	$(DC_EXEC) pnpm test:coverage

lint:
	$(DC_EXEC) pnpm lint

lint-fix:
	$(DC_EXEC) pnpm lint:fix

format:
	$(DC_EXEC) pnpm format

outdated:
	$(DC_EXEC) pnpm outdated

quality: lint test
	@echo "Quality checks passed"

help:
	@echo "Monark Frontend"
	@echo ""
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@echo "  install        Install dependencies"
	@echo "  dev            Start development server"
	@echo "  build          Build for production"
	@echo "  test           Run tests"
	@echo "  test-coverage  Run tests with coverage"
	@echo "  lint           Run ESLint"
	@echo "  lint-fix       Run ESLint with auto-fix"
	@echo "  format         Format code with Prettier"
	@echo "  outdated       Check outdated dependencies"
	@echo "  quality        Run lint + tests"
	@echo "  help           Show this help"
