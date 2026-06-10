.PHONY: help up down build restart logs shell test migrate seed fresh lint

# Default target
help: ## Show this help
	@echo ""
	@echo "  TaskFlow API — Development Commands"
	@echo "  ====================================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""

up: ## Start all containers
	docker compose up -d

down: ## Stop all containers
	docker compose down

build: ## Build containers from scratch
	docker compose build --no-cache

restart: ## Restart containers
	docker compose restart

logs: ## Follow container logs
	docker compose logs -f app

shell: ## Enter PHP app container
	docker compose exec app sh

test: ## Run all tests with coverage
	docker compose exec app php artisan test --coverage

test-unit: ## Run only Unit tests
	docker compose exec app php artisan test --filter=Unit

test-feature: ## Run only Feature tests
	docker compose exec app php artisan test --filter=Feature

migrate: ## Run database migrations
	docker compose exec app php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations with seeds
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker compose exec app php artisan db:seed

lint: ## Run PHP code style fixer (Laravel Pint)
	docker compose exec app vendor/bin/pint

lint-test: ## Check code style without fixing
	docker compose exec app vendor/bin/pint --test

routes: ## List all API routes
	docker compose exec app php artisan route:list --path=api

queue: ## Start queue worker manually
	docker compose exec app php artisan queue:work --tries=3

tinker: ## Open Laravel Tinker REPL
	docker compose exec app php artisan tinker

swagger: ## Regenerate OpenAPI documentation
	docker compose exec app php artisan l5-swagger:generate

setup: ## Full initial setup (build, migrate, seed)
	make build
	make up
	sleep 5
	make migrate-fresh
	@echo ""
	@echo "  ✅ TaskFlow API is ready at http://localhost:8000"
	@echo "  📚 API Docs available at http://localhost:8000/api/documentation"
	@echo ""
