# DT bet-backend — Multi-Gateway Payment API

API RESTful em Laravel 12 para gerenciamento de pagamentos com múltiplos gateways. 
Autenticação via Sanctum, RBAC (Controle de acesso baseado em perfis) com 4 roles e dois ambientes Docker (dev com hot-reload, prod otimizado).



## Requisitos do DT

- [BeMobile](https://github.com/BeMobile/teste-pratico-backend/tree/main)

## Tecnologias Utilizadas

| Tecnologia | Versão |
|---|---|
| PHP | 8.3 |
| Laravel | 12 |
| MySQL | 8.4 (LTS) |
| Laravel Sanctum | ^4.0 |
| GuzzleHTTP | ^7.0 |
| PHPUnit | ^11.0 |
| Scalar (laravel-scribe) | 1.0 |
| Viu-laravel | 0.2.0 |

## Instalação e execução

<details>
  <summary>Ambiente de Desenvolvimento</summary>

    ```bash
        
    # 1º Clone o repositório
    git clone <repo-url> bet-backend
    cd bet-backend

    # 2º Copie o .env
    cp src/.env.example src/.env

    # 3º Execute os containers ambiente dev - com hot reload
    docker compose -f docker-compose.dev.yml up -d --build

    # 4º Gerar chave da aplicação
    docker compose -f docker-compose.dev.yml exec app php artisan key:generate

    # 5º Executar migrations e seeders
    docker compose -f docker-compose.dev.yml exec app php artisan migrate:fresh --seed

    # 6º Execute os testes
    # Os testes usam **SQLite em memória** e não afetam o banco principal.
    docker compose -f docker-compose.dev.yml exec app php artisan test

    # ou use makefile
    make dev-setup

    ```
</details>
<details>
  <summary>Ambiente de Produção</summary>

    ```bash

    # 1º Clone o repositório
    git clone <repo-url> bet-backend
    cd bet-backend

    # 2º Configure as variáveis de ambiente em src/.env
    # Edite src/.env com os valores de produção
    cp src/.env.example src/.env

    # 3º Execute os containers otimizados
    docker compose -f docker-compose.prod.yml up -d --build

    # 4º Execute migrations e seeders
    docker compose -f docker-compose.prod.yml exec app php artisan migrate --force --seed

    # 5º Otimize o Laravel
    docker compose -f docker-compose.prod.yml exec app php artisan config:cache
    docker compose -f docker-compose.prod.yml exec app php artisan route:cache
    docker compose -f docker-compose.prod.yml exec app php artisan view:cache

    # 6º Executar migrations e seeders
    docker compose -f docker-compose.dev.yml exec app php artisan migrate:fresh --seed

    # ou use makefile
    make prod-setup

    ```
</details>

## API Health Check
Endpoint para verificação de integridade da API.

- `Ambiente Local` - [http://localhost:8000/up](http://localhost:8000/up)
- `Ambiente de Produção` - [http://91.99.238.75/up](http://91.99.238.75/up)



## Documentação da API - [Laravel Scribe](https://scalar.com/products/api-references/integrations/laravel-scribe)
O Laravel Scribe é um pacote incrível para gerar arquivos OpenAPI a partir do código existente. Não são necessárias anotações complexas, o pacote simplesmente analisa o código.

- `Ambiente Local` - [http://localhost:8000/docs](http://localhost:8000/docs)
- `Ambiente de Produção` - [http://91.99.238.75/docs/](http://91.99.238.75/docs/)

  > Credenciais para testar na documentação
  >>| Email | Senha | Role |
  >>|---|---|---|
  >>| `admin@bet.com` | `password` | Admin |
  >>| `manager@bet.com` | `password` | Manager |
  >>| `finance@bet.com` | `password` | Finance |
  >>| `user@bet.com` | `password` | User |

---

## Rotas da API

Todas as rotas têm o prefixo `/api`.

| Método | Rota | Auth | Roles permitidas |
|---|---|---|---|
| `POST` | `/auth/login` | — | — |
| `POST` | `/auth/logout` | Sanctum | todos |
| `POST` | `/purchases` | — | — |
| `GET` | `/gateways` | Sanctum | admin |
| `PATCH` | `/gateways/{id}/toggle` | Sanctum | admin |
| `PATCH` | `/gateways/{id}/priority` | Sanctum | admin |
| `GET` | `/users` | Sanctum | admin, manager |
| `POST` | `/users` | Sanctum | admin, manager |
| `GET` | `/users/{id}` | Sanctum | admin, manager |
| `PUT` | `/users/{id}` | Sanctum | admin, manager |
| `DELETE` | `/users/{id}` | Sanctum | admin, manager |
| `GET` | `/products` | Sanctum | todos |
| `GET` | `/products/{id}` | Sanctum | todos |
| `POST` | `/products` | Sanctum | admin, manager, finance |
| `PUT/PATCH` | `/products/{id}` | Sanctum | admin, manager, finance |
| `DELETE` | `/products/{id}` | Sanctum | admin, manager |
| `GET` | `/clients` | Sanctum | admin, manager, finance |
| `GET` | `/clients/{id}` | Sanctum | admin, manager, finance |
| `GET` | `/transactions` | Sanctum | admin, finance |
| `GET` | `/transactions/{id}` | Sanctum | admin, finance |
| `POST` | `/transactions/{id}/refund` | Sanctum | admin, finance |

---

## Arquitetura de gateways

O sistema usa o **Adapter Pattern** para abstrair os gateways de pagamento:

```
GatewayInterface
  ├── Gateway1Adapter   (autenticação via Bearer token com cache)
  └── Gateway2Adapter   (autenticação via headers estáticos)
```

O `PaymentGatewayService` busca os gateways ativos ordenados por prioridade e tenta cada um em sequência (**failover automático**). Se o gateway de maior prioridade falhar, a API tenta o próximo automaticamente. Para adicionar um novo gateway, basta:

1. Criar uma nova classe implementando `GatewayInterface`
2. Registrá-la em `config/gateways.php`
3. Cadastrar o gateway no banco via seeder ou interface admin

---

## Segurança

- Credenciais dos gateways armazenadas criptografadas no banco (cast `encrypted` do Laravel)
- Apenas os últimos 4 dígitos do cartão são persistidos; CVV nunca é armazenado
- Tokens Sanctum são revogados no logout
- RBAC granular por role em cada endpoint

---


## Futuras Implementações

`CI/CD` - Implementação fluxo de Integração e Deploy contínuos para automatização do processo.

`Repository Patterns` - Sugestão de implementação se a API crescer, as regras de negócios ficarem complexas e o desenvolvimento também for orientado a testes.

Segue os prós e contras que norteará a decisão quanto a futura implementação ou não.

#### Prós
- **Testabilidade real** - Mockar o repositório nos testes unitários sem tocar no banco.

- **Alternar fonte de dados** - Buscar dados de uma API externa, Redis ou outro apenas mudando a implementação, sem alterar o restante do código.

- **Centralizar queries complexas** - centralizar queries com muitos join, with, filter para melhor manutenibilidade.

####  Contras
- **Overhead em pequenas APIs/MVP** - Um CRUD básico com 5 models aumenta para 10+ arquivos extras sem necessidade.

- **Mais arquivos, menos direção** - Mais camadas para debbug, maior curva de entendimento do projeto para os iniciantes.

- **Flaso "clean architecture"** - Injeção do Model em vários lugares do repository patterns, tornando a abstração inútil.

`Observabilidade` ✅ **Implementada** — integração com a plataforma [Viu](http://91.99.238.75:3333/) via SDK [`viu/viu-laravel`](https://github.com/amarjr-dev/viu-laravel) (Monolog Handler + HTTP transport).

- **Logs centralizados**: todos os eventos de negócio (login, compra, reembolso, alteração de gateway) são enviados ao Viu com `correlation_id`, `trace_id`, `span_id`, `file` e `line` automaticamente populados.
- **Métricas**: com que frequência/intensidade que acontece.
- **Traces**: por onde o request passou e quanto tempo levou.

- **Visualizar Logs:**

  `url`: [http://91.99.238.75:3333/login](http://91.99.238.75:3333/login)

  `e-mail`: mario@betalent.com

  `Senha`: be@T2025


## Dificuldates Encontradas

O Laravel 12 é um framework apaixonante e não apresenta muitos desafios, pois tem baterias inclusas como o poderoso Eloquent ORM. Entretanto fiquei tentato a implementar o Repository Pattern, mas no cenário de um MVP seria complicar demais algo simple. 

E durante o desenvolvimento encontrei, fora do escopo dos requisitos solicitados, dificuldades para adicionar observabilidade em uma plataforma que estou desenvolvendo para uso pessoal, a princípio.

Por isso foi um desafio criar uma lib php compatível com Laravel 12+ e usá-la no projeto. Vibe Coding me ajudou nesse sentido, especialmente tirando algumas dúvidas técnicas e acelerando essa parte do desenvolvimento. 

Passado os obstáculos, consegui publicar a lib no [packagist.org](https://packagist.org/packages/viu/viu-laravel) e a API, está enviando os logs para a plataforma Viu com riqueza de informações. 

É possível visualizar e testar o envio de logs por meio do passo a passo e credenciais da sessão `Observabilidade` anterior a essa. 
