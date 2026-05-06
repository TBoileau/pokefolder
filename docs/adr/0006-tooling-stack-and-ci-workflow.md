# ADR-0006 — Tooling stack & CI workflow

- **Status**: Accepted
- **Date**: 2026-05-06
- **Related**: PRD #1 ; tracking issue #35 ; supersedes the implicit "no-tooling" status quo
- **Affects**: every code change going forward (CI gating + local pre-commit workflow)

## Context

Until this ADR, the project had no static analysis, no CI, no formalised local task runner. Each tool invocation was ad-hoc (`vendor/bin/phpunit`, `pnpm dev`, `docker compose up`, etc.). This ADR consolidates the decisions made during the `/grill-with-docs` session on issue #35 and pins the conventions every future contributor (human or AFK agent) follows.

The project is monorepo (`api/` Symfony 8 + API Platform 4 + Doctrine ; `app/` React 19 + Vite + TypeScript), single-developer, deployed in localhost. The MVP target is 3 months. Tooling decisions therefore favour: low maintenance overhead, fast feedback, and tight strictness from day one (no legacy debt to pay later).

## Decision

### 1. Task runner: Castor

[Castor](https://castor.jolicode.com) is the single orchestrator for every command in the monorepo. Symfony commands, Composer, pnpm, docker, static analysers, tests — all wrapped as Castor tasks.

**Layout**:

```
pokefolder/
├── castor.php              ← entry point: imports .castor/, declares contexts
└── .castor/
    ├── api.php             ← namespace api      (api:* tasks)
    ├── app.php             ← namespace app      (app:* tasks)
    ├── docker.php          ← namespace docker   (docker:* tasks)
    ├── qa.php              ← namespace qa       (qa:* aggregates)
    └── test.php            ← namespace test     (test:* aggregates)
```

Castor task names mirror the file's PHP namespace (e.g. `api/install` → `api:install` at the CLI).

**Contexts**:

- `dev` (default) — `APP_ENV=dev`, working directory at the repo root.
- `ci` — `APP_ENV=test`, `CI=true`, working directory at the repo root, no-interaction.

CI invocations use `castor --context=ci <task>`. Local dev invocations use `castor <task>` (defaults to `dev`).

**Installation**:

- Local: globally installed (no Composer dep). The maintainer's machine and any contributor's machine has `castor` on the PATH.
- CI: installed via the official [`castor-php/setup-castor@v1.0.0`](https://github.com/castor-php/setup-castor) GitHub Action — static binary, no PHP setup required for Castor itself.

**Explicitly NOT installed via Composer.** Doing so would push it under `vendor/bin/castor` and force every CI invocation through Composer, defeating the speed of the static binary.

### 2. Front linter / formatter: Biome

[Biome](https://biomejs.dev) v2 replaces ESLint + Prettier on the front. Installed via `pnpm add -D @biomejs/biome`. Configured in `app/biome.json` with:

- `recommended: true` baseline.
- React rules: `useExhaustiveDependencies`, `useHookAtTopLevel`.
- Type-aware rules (Biome v2 GA): `noFloatingPromises`, `noMisusedPromises`.
- Import discipline: `useImportType`, `useExportType`.
- Tailwind class sorting: `useSortedClasses` configured for the helpers `clsx`, `cn`, `cva`.

ESLint deps (`eslint`, `@eslint/js`, `typescript-eslint`, `eslint-plugin-react-hooks`, `eslint-plugin-react-refresh`, `globals`) and `app/eslint.config.js` are removed.

### 3. PHP static analysis: PHPStan max + Rector + PHP CS Fixer

#### PHPStan

`api/phpstan.dist.neon` is the strictest reasonable configuration:

```neon
includes:
    - vendor/phpstan/phpstan/conf/bleedingEdge.neon
    - vendor/phpstan/phpstan-strict-rules/rules.neon
    - vendor/phpstan/phpstan-deprecation-rules/rules.neon

parameters:
    level: max
    paths: [src, tests]

    checkImplicitMixed: true
    checkUninitializedProperties: true

    symfony:
        container_xml_path: var/cache/dev/App_KernelDevDebugContainer.xml
```

Composer dev deps: `phpstan/phpstan` + `phpstan/extension-installer` + `phpstan/phpstan-symfony` + `phpstan/phpstan-doctrine` + `phpstan/phpstan-strict-rules` + `phpstan/phpstan-deprecation-rules`.

#### Rector

`api/rector.php` enables comprehensive sets:

- PHP 8.4 idioms.
- Prepared sets: `deadCode`, `codeQuality`, `codingStyle`, `typeDeclarations`, `privatization`, `naming`, `instanceOf`, `earlyReturn`, `strictBooleans`.
- `SymfonySetList::SYMFONY_80`, `DoctrineSetList::DOCTRINE_CODE_QUALITY`.
- `withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')`.

Two Castor tasks: `api:rector` (apply, dev workflow) and `api:rector:check` (`--dry-run`, CI workflow).

#### PHP CS Fixer

`api/.php-cs-fixer.dist.php` ruleset:

- `@PER-CS` baseline.
- `@Symfony` + `@Symfony:risky` (with `setRiskyAllowed(true)`).
- `declare_strict_types: true`.
- `global_namespace_import: { import_classes: true, import_functions: true, import_constants: true }`.

Two Castor tasks: `api:cs-fixer` (apply) and `api:cs-fixer:check` (`--dry-run --diff`).

### 4. TypeScript strict mode

`app/tsconfig.app.json` enables the full strict family plus extra knobs:

```jsonc
{
  "compilerOptions": {
    "strict": true,
    "noUncheckedIndexedAccess": true,
    "noImplicitOverride": true,
    "exactOptionalPropertyTypes": true,
    "noImplicitReturns": true,
    "noPropertyAccessFromIndexSignature": true
  }
}
```

### 5. Cross-cutting checks

Beyond the per-language linters, the following Symfony/Composer/pnpm checks are wired as Castor tasks and gated in CI:

- `composer validate --strict` (`api:composer:validate`)
- `composer audit` (`api:composer:audit`)
- `bin/console lint:yaml config/ --parse-tags` (`api:lint:yaml`)
- `bin/console lint:container` (`api:lint:container`)
- `bin/console doctrine:schema:validate` (`api:doctrine:validate`)
- `pnpm audit --audit-level=moderate` (`app:audit`)

`lint:twig` is omitted: the project has no Twig templates.

### 6. Aggregate tasks

- `qa:all` — runs every check above (api + app static analysis + cross-cutting) **in parallel** via Castor's `parallel()`. Read-only, parallelisable.
- `qa:fix` — runs the **applying** versions in **sequence**: `api:rector` → `api:cs-fixer` → `app:format`. Order matters because Rector can introduce code that CS Fixer needs to reformat.
- `test:all` — runs `api:test` (PHPUnit). Will gain `app:test` (Vitest) once front tests exist.

### 7. CI: GitHub Actions

A single workflow `.github/workflows/ci.yml` triggered on `push` to main and `pull_request`. Path filters skip doc-only changes (`*.md`, `docs/**`, `.gitignore`) **except** `CONTEXT.md` and `CLAUDE.md` (load-bearing for downstream code).

**Services**: PostgreSQL and RabbitMQ injected via the GitHub Actions **native `services:`** block (faster than `docker compose up` because they boot in parallel with job setup, and the runner waits for healthcheck automatically).

**Steps** (in order):

1. `actions/checkout@v5`
2. `shivammathur/setup-php@v2` (PHP 8.4)
3. `actions/setup-node@v5` + pnpm setup
4. `castor-php/setup-castor@v1.0.0`
5. Restore caches (composer keyed on `composer.lock`, pnpm keyed on `pnpm-lock.yaml`, biome keyed on `biome.json`)
6. `castor api:install` + `castor app:install`
7. `castor api:migrate:test`
8. `castor --context=ci qa:all`
9. `castor --context=ci test:all`
10. `castor app:build`

Branch protection on `main` makes this workflow a required check (configured manually in GitHub Settings).

## Rationale

### Why Castor over Make / composer scripts / package.json scripts / just

- **Stack alignment**: tasks are PHP functions with attributes. The same language as the back, with first-class type hints and IDE support.
- **Monorepo orchestration**: a single tool spans `composer`, `pnpm`, `docker compose`, `bin/console` invocations cleanly. Make can do this but with awkward syntax; composer/package.json scripts are stuck inside their own ecosystem.
- **Native parallel + dependencies**: `parallel()`, `notify()`, `run(... timeout: ...)`, contexts. Out of the box, no shell tricks.
- **Discoverability**: `castor list` is structured. AFK agents reading `CLAUDE.md` know the canonical entry point.

### Why Biome over ESLint + Prettier

- **Speed**: ~10–25× faster than ESLint + Prettier on the same codebase. Local feedback under 300 ms ; CI cache hits under 1 s.
- **Single config**: `biome.json` covers linting and formatting. No `.prettierrc`, no `eslint-config-prettier` to disable conflicting rules.
- **Type-aware rules at parity in v2**: `noFloatingPromises`, `noMisusedPromises`, `noUnnecessaryConditions` — the rules that pushed teams toward `typescript-eslint strictTypeChecked` are now in Biome.

Trade-offs assumed:

- **No React Compiler-specific ESLint rules** in Biome. The babel plugin still runs at build time and catches violations there, so we accept the loss of editor-time feedback on those specific rules.
- **No TanStack-specific lint plugins**. The TanStack libraries are heavily typed, so the typecheck (`tsc --noEmit`) catches the bulk of misuses.
- **No `react-refresh/only-export-components` equivalent**. The historical exception we had on `src/components/ui/**` becomes moot.

### Why `level: max` PHPStan with `checkImplicitMixed`

The codebase is fresh and small. Starting strict means zero baseline debt. The marginal cost of being aggressive *now* is a one-time 30–60 minutes of baseline-fixing in the API tooling slice ; the marginal cost of *not* being aggressive now is years of "we'll level up later" that never happens.

`checkImplicitMixed` is the killer flag — it forces every `mixed` to be explicit. Combined with `bleedingEdge.neon`'s `treatPhpDocTypesAsCertain: false`, the type system becomes genuinely useful.

### Why GitHub Actions native services over `docker compose up`

The CI loop is run dozens of times per week. Saving 5–10 seconds per run by avoiding the `docker compose up` overhead (image pulls coordinated with the runner cache, healthcheck managed by the action runner) compounds.

Local development still uses `compose.yaml` (CI is the only place we differ).

## Consequences

### Positive

- One canonical entry point for every operation: `castor <task>`. Documentation is simple.
- Strict from day one: refactor friction declines as the codebase grows because debt never accumulated.
- Fast CI: parallel `qa:all` + cached deps + native services keeps the loop under 2 minutes for typical PRs.
- Front and back share the same orchestration philosophy (Castor wraps both).

### Negative

- Castor is unusual outside the JoliCode ecosystem. Onboarding a new contributor adds one tool to learn — mitigated by `CLAUDE.md` pointing at this ADR.
- Biome lacks plugin maturity. If we hit a use case where ESLint has a niche plugin Biome doesn't cover, we'll have to forgo that check — accepted trade-off.
- `level: max` + `checkImplicitMixed` will require periodic `@phpstan-ignore` comments on integration with libraries that lack proper generics. Cost is per-instance and small.

### Reversal cost

- **Castor → Make**: moderate. Each `.castor/<area>.php` becomes a `Makefile.<area>` or section of `Makefile`. Tasks are ~1:1 mappable.
- **Biome → ESLint+Prettier**: low–moderate. Reinstall deps, reintroduce `eslint.config.js`, `.prettierrc`, `eslint-config-prettier`. The `biome.json` rules have direct ESLint equivalents.
- **PHPStan max → level 8**: trivial (one config line). Re-tightening later is much harder than loosening, which is why we start strict.
