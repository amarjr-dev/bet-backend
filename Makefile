APP_PROD  = bet_app_prod
APP_DEV   = bet_app_dev

COMPOSE_PROD = docker compose -f docker-compose.prod.yml
COMPOSE_DEV  = docker compose -f docker-compose.dev.yml

.DEFAULT_GOAL := help


.PHONY: help
help: # Exibe esta ajuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-28s\033[0m %s\n", $$1, $$2}'


# Ambiente Prod
.PHONY: prod-up
prod-up: # Sobe os containers de produção (build)
	$(COMPOSE_PROD) up -d --build

.PHONY: prod-down
prod-down: # Derruba os containers de produção
	$(COMPOSE_PROD) down

.PHONY: prod-key
prod-key: # Gera APP_KEY (produção)
	docker exec $(APP_PROD) php artisan key:generate --force

.PHONY: prod-migrate
prod-migrate: ## Executa as migrations (produção)
	docker exec $(APP_PROD) php artisan migrate --force

.PHONY: prod-seed
prod-seed: ## Executa os seeders (produção)
	docker exec $(APP_PROD) php artisan db:seed --force

.PHONY: prod-cache
prod-cache: ## Otimiza config/route/view cache (produção)
	docker exec $(APP_PROD) php artisan config:cache
	docker exec $(APP_PROD) php artisan route:cache
	docker exec $(APP_PROD) php artisan view:cache
	docker exec $(APP_PROD) php artisan event:cache

.PHONY: prod-setup
prod-setup: prod-key prod-migrate prod-seed prod-cache # Setup completo de produção (composer roda no docker build)

.PHONY: prod-docs
prod-docs: ## Rebuild imagem e regera documentação Scribe (produção)
	$(COMPOSE_PROD) build app
	$(COMPOSE_PROD) up -d --no-build app

.PHONY: prod-logs
prod-logs: ## Exibe logs do container app (produção)
	docker logs -f $(APP_PROD)

# Ambiente Dev
.PHONY: dev-up
dev-up: # Sobe os containers de desenvolvimento (build)
	$(COMPOSE_DEV) up -d --build

.PHONY: dev-down
dev-down: # Derruba os containers de desenvolvimento
	$(COMPOSE_DEV) down

.PHONY: dev-composer
dev-composer: # composer install com dev dependencies
	docker exec $(APP_DEV) composer install --optimize-autoloader --no-interaction

.PHONY: dev-key
dev-key: ## Gera APP_KEY (desenvolvimento)
	docker exec $(APP_DEV) php artisan key:generate

.PHONY: dev-migrate
dev-migrate: ## Executa as migrations (desenvolvimento)
	docker exec $(APP_DEV) php artisan migrate

.PHONY: dev-seed
dev-seed: ## Executa os seeders (desenvolvimento)
	docker exec $(APP_DEV) php artisan db:seed

.PHONY: dev-fresh
dev-fresh: ## migrate:fresh + seed (desenvolvimento)
	docker exec $(APP_DEV) php artisan migrate:fresh --seed

.PHONY: dev-cache-clear
dev-cache-clear: ## Limpa todos os caches (desenvolvimento)
	docker exec $(APP_DEV) php artisan cache:clear
	docker exec $(APP_DEV) php artisan config:clear
	docker exec $(APP_DEV) php artisan route:clear
	docker exec $(APP_DEV) php artisan view:clear

.PHONY: dev-setup
dev-setup: dev-composer dev-key dev-migrate dev-seed # Setup completo de desenvolvimento

.PHONY: dev-test
dev-test: ## Executa os testes (desenvolvimento)
	docker exec $(APP_DEV) php artisan test

.PHONY: dev-logs
dev-logs: ## Exibe logs do container app (desenvolvimento)
	docker logs -f $(APP_DEV)

.PHONY: dev-shell
dev-shell: ## Abre shell no container app (desenvolvimento)
	docker exec -it $(APP_DEV) sh

.PHONY: prod-shell
prod-shell: ## Abre shell no container app (produção)
	docker exec -it $(APP_PROD) sh
