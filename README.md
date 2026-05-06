# pokefolder

Pokémon TCG collection manager — gérer ma collection physique de cartes et l'organisation dans mes classeurs. Single-user, déploiement local. Voir [`CONTEXT.md`](CONTEXT.md) pour le vocabulaire métier et [`docs/adr/`](docs/adr/) pour les décisions structurantes.

## Stack

- **Backend** (`api/`) : PHP 8.4, Symfony 8, API Platform 4.3, Doctrine ORM, Symfony Messenger + RabbitMQ, PostgreSQL 18.
- **Frontend** (`app/`) : React 19, TypeScript, Vite, React Compiler, TanStack (Query/Router/Form), ShadCN UI, Zod.

## Prérequis

- Docker (avec Compose v2)
- PHP 8.4 + Composer
- Node 22+ + pnpm

## Démarrer en local

### 1. Lancer l'infrastructure (Postgres + RabbitMQ)

À la racine du repo :

```bash
docker compose up -d
```

Services exposés :

| Service           | Host port | Container port | Notes                                  |
|-------------------|-----------|----------------|----------------------------------------|
| PostgreSQL        | `60010`   | `5432`         | user/pass/db = `pokefolder`            |
| RabbitMQ AMQP     | `60011`   | `5672`         | user/pass = `pokefolder`               |
| RabbitMQ Manager  | `60012`   | `15672`        | UI de gestion sur http://localhost:60012 |

### 2. Backend

```bash
cd api
composer install
bin/console doctrine:database:create --if-not-exists
APP_ENV=test bin/console doctrine:database:create --if-not-exists
symfony serve -d   # ou : php -S 127.0.0.1:8000 -t public
```

L'API est accessible sur http://localhost:8000.
- Healthcheck : http://localhost:8000/health → `{"db":"ok","amqp":"ok"}` quand l'infra tourne
- Documentation API Platform : http://localhost:8000/api

### 3. Frontend

```bash
cd app
pnpm install
pnpm dev
```

L'app est accessible sur http://localhost:5173 (port Vite par défaut).

## Tests

### Backend

```bash
cd api
vendor/bin/phpunit
```

Les tests utilisent une base `pokefolder_test` distincte, gérée en transactions rollback via `dama/doctrine-test-bundle`.
Le transport Messenger est forcé à `in-memory://` en environnement de test.

### Frontend

```bash
cd app
pnpm test  # à venir avec les premières slices UI
```

## Workflow de développement

Les fonctionnalités sont découpées en issues GitHub indépendantes (vertical slices). Voir [issue #1 (PRD)](https://github.com/TBoileau/pokefolder/issues/1) et la liste des issues taggées `needs-triage` pour le backlog.

Conventions :

- Une branche par issue : `issue/<numero>-<slug>`
- Une PR par issue, qui ferme l'issue via `Closes #<numero>` dans la description
- Vocabulaire métier : suivre strictement les termes définis dans [`CONTEXT.md`](CONTEXT.md)
- Décisions structurantes : tracées dans [`docs/adr/`](docs/adr/), au format Nygard
