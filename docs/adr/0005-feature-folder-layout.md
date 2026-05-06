# ADR-0005 — Feature-folder layout in `api/src/` with `UseCase/` + `Service/`

- **Status**: Accepted
- **Date**: 2026-05-06
- **Related**: PRD #1 ; ADR-0001 (RabbitMQ) ; ADR-0002 (OwnedCard per copy) ; affects every backend slice from #6 onward
- **Supersedes**: the implicit "flat Symfony layout" decision documented in `CLAUDE.md` until now

## Context

Until this ADR, `api/src/` followed the conventional flat Symfony layout: `Controller/`, `Entity/`, `Repository/`, `Message/`, `MessageHandler/`, `Command/`, plus an ad-hoc `Catalog/` folder grouping the synchroniser + DTOs + providers for the TCGdex sync feature.

After only one feature (catalog sync) the layout was already showing two pain points:

1. **`Message/SyncSetMessage.php` and `MessageHandler/SyncSetMessageHandler.php` live in two unrelated directories** — opening one to understand the other requires bouncing across the tree.
2. **`Catalog/CatalogSynchronizer.php` mixes "the use case logic" with the existing `Service/`-style helpers** (provider, factory, DTOs). The same directory plays two roles, which gets worse as more features ship.

Two future increments make this likely to compound:

- Slices #11/#16/#20–#22/#27–#29 will each introduce a piece of cross-aggregate logic (placement in a binder, suggestion, etc.).
- Slice #8 (`SyncAll`) will add a second use case in the catalog area, calling into the same providers as `SyncSet`.

## Decision

Reorganise `api/src/` around two top-level concepts: **use cases** (state-changing or query interactions with non-trivial logic) and **services** (reusable utilities consumed by use cases). The full layout:

```
api/src/
├── Command/                      ← Symfony CLI commands
├── Controller/                   ← plain Symfony controllers (e.g. healthcheck)
├── Entity/                       ← Doctrine entities
├── Repository/                   ← Doctrine repositories (root, Symfony convention)
├── Service/                      ← reusable utilities, grouped by domain area
│   └── Catalog/
│       ├── DTO/
│       └── Provider/
└── UseCase/                      ← state-changing or non-trivial query operations
    └── <Area>/
        └── <Verb>/
            ├── Input.php         ← message / DTO carrying the request
            ├── Handler.php       ← the operation logic, registered via #[AsMessageHandler] when async
            ├── Output.php        ← (optional) the operation's return shape
            └── <DomainException>.php  (optional) exceptions specific to the use case
```

### Decision rule for "use case vs default API Platform CRUD"

A `UseCase/<area>/<verb>/` directory is justified when **at least one** of these is true:

1. **Multi-aggregate** — the operation touches ≥ 2 entities
2. **Side effects** beyond ORM persistence — dispatch Messenger, call external service, send email
3. **Non-trivial business invariants** — invariants that cannot be expressed as Symfony validator constraints
4. **Async / queued** — the operation is dispatched on a Messenger transport
5. **Heuristic / query logic** — non-trivial computation with domain rules

If **none** of these apply, the operation lives as default API Platform CRUD (`Get`, `GetCollection`, `Post`, `Patch`, `Delete`) on the entity, with validation expressed via `Symfony\Component\Validator` constraints.

### Naming inside `UseCase/<area>/<verb>/`

- **`Input.php`** — the DTO/message carrying the request. Used `Input` rather than `Message` (kept generic so synchronous and async use cases share the convention) or `Command` (would clash with `src/Command/` for CLI commands).
- **`Handler.php`** — the operation logic. Decorated with `#[AsMessageHandler]` when async, otherwise simply autowired and called by the caller (Controller, State Processor, another Handler).
- **`Output.php`** — only when the handler returns a non-trivial object. Optional.
- **`<DomainException>.php`** — exceptions raised by the handler that are specific to this use case.

### Why `Service/<area>/...` and not `Service/` flat

Grouping services under their domain area (`Service/Catalog/`, future `Service/Binder/`, etc.) keeps cross-area code from accidentally coupling, and matches the domain language in `CONTEXT.md`. Symfony's autowiring handles nested namespaces transparently.

### Why `Repository/` stays at the root (not under `Service/`)

Repositories are tightly coupled to their entities (read together, evolve together) and the wider Doctrine/Symfony ecosystem — including `make:entity` tooling — assumes `src/Repository/`. Moving them under `Service/` would impose constant friction for marginal gain.

## Alternatives considered

- **Flat Symfony layout** (status quo): low onboarding cost for someone fresh from Symfony docs, but forces awkward grouping decisions as features grow (hence the existing `src/Catalog/` exception). Rejected: the pain compounds with each new feature.
- **Pure DDD layout** (`Domain/`, `Application/`, `Infrastructure/`): explicitly rejected earlier in the project (we removed exactly this layout in favour of a flatter Symfony layout). Premature for a solo MVP.
- **Use case logic stays in a dedicated service class**, handler is thin: pleasant when one use case wraps the same service, but creates indirection for everything else. Resolved by moving the logic into the `Handler` directly.

## Consequences

### Positive

- **Locality** — every use case lives in a single dedicated folder. Reading `Input.php`, `Handler.php`, `Output.php` side by side is enough to understand the contract.
- **Predictability** — every new feature follows the same file pattern. Future AFK agents grabbing slices have an unambiguous template.
- **Service reuse** — the `Service/<area>/` namespace makes it obvious where to put a utility consumed by multiple use cases.
- **Test mirror** — `tests/UseCase/<Area>/<Verb>/HandlerTest.php` mirrors prod, unambiguous test location.

### Negative

- Deviates from the doc-default Symfony layout. New contributors will need to read this ADR. Mitigated by a brief summary in `CLAUDE.md`.
- `make:entity` and similar tooling still assumes `src/Repository/`, which we keep at the root precisely for this reason. Other Symfony Maker commands (e.g. `make:message`, `make:message-handler`) generate into `src/Message/` and `src/MessageHandler/`; these will need to be moved manually after generation.

### Reversal cost

Moderate. A future flat-out reversal would require moving all `UseCase/<area>/<verb>/Handler.php` back to `MessageHandler/` (and corresponding renames), updating `messenger.yaml` routing, and the test mirror. Each mechanical, but proportional to the number of use cases. Easier to do early than late.

## Re-evaluation triggers

- If the team grows beyond one developer and onboarding friction with the non-conventional layout becomes a real cost.
- If a third domain area appears whose use cases naturally cross-cut (e.g. analytics that aggregates across `Catalog`, `Binder`, `Collection`) — at which point an `App\` cross-area shared module convention may be needed.
