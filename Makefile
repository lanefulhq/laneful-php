# Laneful PHP SDK Docker Makefile

.PHONY: help build up down shell logs clean test phpstan cs-check cs-fix web prod

# Default target
help: ## Show this help message
	@echo "Laneful PHP SDK Docker Commands"
	@echo "================================"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

# Development Environment
build: ## Build Docker images
	docker-compose build

up: ## Start development environment
	docker-compose up -d laneful-php

down: ## Stop all containers
	docker-compose down

shell: ## Open bash shell in development container
	docker-compose exec laneful-php bash

logs: ## Show container logs
	docker-compose logs -f laneful-php

# Code Quality
test: ## Run PHPUnit tests in container
	docker-compose exec laneful-php composer test

phpstan: ## Run PHPStan static analysis in container
	docker-compose exec laneful-php composer phpstan

cs-check: ## Check code style in container
	docker-compose exec laneful-php composer cs-check

cs-fix: ## Fix code style in container
	docker-compose exec laneful-php composer cs-fix

quality: test phpstan cs-check ## Run all quality checks

# Examples and Web Interface
web: ## Start web server for examples (nginx + php-fpm)
	docker-compose --profile web up -d

web-logs: ## Show web server logs
	docker-compose --profile web logs -f nginx php-fpm

# Production
prod: ## Start production-like environment
	docker-compose --profile production up -d laneful-php-prod

prod-logs: ## Show production container logs
	docker-compose --profile production logs -f laneful-php-prod

# Utilities
clean: ## Clean up containers, images, and volumes
	docker-compose down -v --remove-orphans
	docker system prune -f

rebuild: clean build up ## Clean rebuild of all containers

install: ## Install Composer dependencies in container
	docker-compose exec laneful-php composer install

update: ## Update Composer dependencies in container
	docker-compose exec laneful-php composer update

# Example commands
run-example: ## Run the send_email.php example
	docker-compose exec laneful-php php examples/send_email.php

# Status
ps: ## Show running containers
	docker-compose ps

# Quick development workflow
dev: build up shell ## Quick start development environment and open shell
