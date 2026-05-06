# pokefolder

Monorepo Pokémon TCG : API Symfony/API Platform/Doctrine (`api/`) + front React/TypeScript/Vite (`app/`). Données cartes via TCGdex SDK.

## Agent skills

### Issue tracker

Issues tracked on GitHub (`TBoileau/pokefolder`) via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Five canonical triage roles, default label strings (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context repo: one `CONTEXT.md` + `docs/adr/` at the root, shared across `api/` and `app/`. See `docs/agents/domain.md`.

## Tooling

Le monorepo utilise **Castor** comme task runner unique. Toute commande (composer, pnpm, docker, bin/console, static analysis, tests…) passe par `castor <task>`. Voir [ADR-0006](docs/adr/0006-tooling-stack-and-ci-workflow.md) pour le détail des choix (Castor vs Make/composer scripts ; Biome vs ESLint+Prettier ; PHPStan max ; etc.).

- **Layout** : `castor.php` racine + `.castor/{api,app,docker,qa,test}.php` (split par domaine, namespace PHP → préfixe `:` au CLI, ex. `castor api:phpstan`).
- **Contextes** : `dev` (default, `APP_ENV=dev`) et `ci` (`APP_ENV=test`, `CI=true`). En CI : `castor --context=ci <task>`.
- **Install** : Castor est installé globalement en local (binary `castor` sur le PATH) ; en CI via [`castor-php/setup-castor@v1.0.0`](https://github.com/castor-php/setup-castor). **Pas de dep Composer**.
- **Convention pour les agents** : toujours invoquer `castor <task>` plutôt que les outils sous-jacents en direct, pour rester cohérent avec la CI.

## Workflow conventions

- **Branches** : une branche par issue, format `issue/<numero>-<slug>`.
- **Merges** : toujours en **rebase and merge** (`gh pr merge <n> --rebase --delete-branch`), jamais de merge commit ni de squash. Garde un historique linéaire propre.
- **Commits** : créer de NOUVEAUX commits sur la branche pendant la review plutôt que d'amender (les commits sont visibles dans la PR).
- **PRs** : la description ferme l'issue via `Closes #<numero>`.
- **Backend layout** : feature-folder + `UseCase/` (voir [ADR-0005](docs/adr/0005-feature-folder-layout.md)).
  - `src/Command/`, `src/Controller/`, `src/Entity/`, `src/Repository/` : conventionnels Symfony, à la racine.
  - `src/Service/<Area>/` : utilities réutilisables (DTOs, providers, factories, value objects), groupées par domaine.
  - `src/Exception/<Area>/` : exceptions métier, groupées par domaine (partagées par les use cases qui les lèvent / catchent).
  - `src/UseCase/<Area>/<Verb>/` : opérations métier non triviales — chacune a `Input.php` + `Handler.php` (+ `Output.php` quand le handler retourne un objet riche). Le `Handler` est registered via `#[AsMessageHandler]` pour les use cases async.
  - Critère pour créer un UseCase plutôt que CRUD direct API Platform : multi-aggregate, side effects, invariants non triviaux, async/queue, ou heuristique/logique de query. Sinon CRUD direct via API Platform default operations + Symfony Validator.
  - Tests miroir la structure prod (`tests/UseCase/<Area>/<Verb>/HandlerTest.php`, `tests/Service/<Area>/...`).
