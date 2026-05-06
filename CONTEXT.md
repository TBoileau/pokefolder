# pokefolder — Domain context

Single source of truth for the **domain language** used across `api/` (Symfony) and `app/` (React). Both sides MUST use the terms below as defined here. Drift to synonyms (e.g. "deck" instead of "binder") is a smell — push back or open an issue to evolve the glossary.

This document defines **what things are** and **how they relate**. Architectural decisions live in `docs/adr/`.

---

## Card

A reference entry from the Pokémon TCG catalog, sourced from [TCGdex](https://tcgdex.dev). A `Card` is **catalog data**, not a physical object you own — it is the description of a card that exists in the world.

**Functional identity** (uniqueness key): the tuple `(set, numberInSet, variant, language)`. Two `Card` rows are the same card iff all four match.

**Properties**:

- `set` — FK to `Set` (the set this card belongs to)
- `numberInSet` — the printed number within the set (e.g. `4` for Charizard in Base Set)
- `variant` — value of the `Variant` enum
- `language` — value of the `Language` enum
- `rarity` — FK to `Rarity` (nullable; some cards have no rarity)
- `name`, `imageUrl`, plus other catalog metadata mirrored from TCGdex

**Mutability**: `Card` is read-only from the application's perspective. It only changes when a sync from TCGdex updates it.

---

## OwnedCard

A **single physical copy** of a `Card` that the user possesses. One `OwnedCard` row = one physical card you can hold in your hand. See [ADR-0002](docs/adr/0002-owned-card-per-physical-copy.md) for the rationale (vs. an aggregate "quantity" model).

**Properties**:

- `card` — the `Card` this physical copy refers to
- `condition` — the physical state of this copy, expressed via the `Condition` enum

**Invariant**: an `OwnedCard` is placed in **at most one** `BinderSlot` at any time (a physical card cannot be in two places at once). It may also be unplaced (in the collection but not in any binder).

**Why one row per copy?** Each physical copy has its own condition and may be placed in a different binder. Two NM copies of Charizard and one LP copy = three distinct `OwnedCard` rows.

---

## Condition

The physical state of an `OwnedCard`. Closed enum, English standard:

| Code | Meaning |
|------|---------|
| `M`   | Mint           |
| `NM`  | Near Mint      |
| `EX`  | Excellent      |
| `GD`  | Good           |
| `LP`  | Light Played   |
| `PL`  | Played         |
| `HP`  | Heavily Played |
| `DMG` | Damaged        |

---

## Collection

The set of all `OwnedCard` rows belonging to the user. **Implicit, not a stored entity** — the application is single-user (see PRD #1, "Out of Scope: multi-user"). The "collection" is simply "everything in the `OwnedCard` table".

When agents or skills speak of "the collection", they mean exactly this set.

---

## Binder

A **physical** card binder owned by the user. The application models binders so it can mirror how the user organises cards in real life. A binder has a fixed physical capacity derived from its dimensions.

**Properties**:

- `name`, `description`
- `pageCount` — number of physical pages
- `cols`, `rows` — slots per page side (e.g. `3 × 3 = 9 slots per side`)
- `doubleSided` — whether each page has slots on both faces (default `true`)

**Derived capacity**: `pageCount × cols × rows × (doubleSided ? 2 : 1)`.

**Free composition**: a binder may contain any mix of cards — full sets, themed selections (e.g. "151 full-arts across all sets"), promos only, etc. There is no constraint linking a binder to a single set.

---

## BinderSlot

A specific physical position inside a `Binder`. Each slot is identified by its coordinates and contains **at most one** `OwnedCard`.

**Coordinates**:

- `binder` — the parent binder
- `pageNumber` — 1-indexed page number
- `face` — `recto` or `verso`
- `row`, `col` — position within the page (1-indexed)

**Invariants**:

- A `BinderSlot` references **at most one** `OwnedCard` (1 slot ↔ ≤ 1 card)
- An `OwnedCard` is referenced by **at most one** `BinderSlot` (1 card ↔ ≤ 1 slot)
- The combination `(binder, pageNumber, face, row, col)` is unique
- Coordinates must fall within the binder's declared dimensions

These invariants are enforced by the `BinderPlacementService` (see PRD #1, slice #21) and by DB-level unique constraints.

---

## Serie

A top-level grouping of Pokémon TCG sets (e.g. "Sword & Shield", "Scarlet & Violet", "Base"). Mirrors TCGdex's serie concept and owns the `Set`s it contains.

**Functional identity**: `id` (TCGdex serie code, e.g. `swsh`, `base`).

**Properties**:

- `id` — primary key, the TCGdex serie code
- `logo` — public URL to the serie logo (nullable)
- `releaseDate` — ISO date of the serie's first release (nullable)
- `translations` — one row per configured language (see `Translation`)

---

## Set

A Pokémon TCG set (e.g. "Base Set", "Sword & Shield Base", "Scarlet & Violet Base"). Belongs to exactly one `Serie`.

The PHP class is `App\Entity\PokemonSet` (table `pokemon_set`); "Set" is the domain term used everywhere else (API path `/api/pokemon_sets`, UI labels, this glossary).

**Functional identity**: `id` (TCGdex set code, e.g. `base1`, `swsh1`, `sv01`).

**Properties**:

- `id` — primary key, the TCGdex set code
- `serie` — FK to `Serie`
- `logo`, `symbol` — public URLs (nullable)
- `releaseDate` — ISO date (nullable)
- `cardCountTotal`, `cardCountOfficial` — totals reported by TCGdex
- `legalStandard`, `legalExpanded` — booleans, tournament legality
- `tcgOnlineId` — optional ID for the historical TCG Online client
- `translations` — one row per configured language (name + abbreviations)

---

## Rarity

A card rarity (e.g. "Common", "Uncommon", "Rare Holo", "Promo"). Rarity strings come from TCGdex as raw labels per language; we deduplicate them by a stable slug.

**Functional identity**: `code` — slugified label (e.g. `common`, `rare-holo`, `promo`). Stable across languages; the human label lives in `translations`.

**Properties**:

- `code` — primary key, slug derived from the canonical English label
- `translations` — one row per configured language

---

## Variant

The printing variant of a `Card` (e.g. normal, reverse holo, holo, first edition, world promo). Closed enum, mirrors TCGdex's `Variants` model:

| Code | Meaning |
|---|---|
| `normal` | Standard printing |
| `reverse` | Reverse holofoil |
| `holo` | Holofoil |
| `firstEdition` | 1st edition |
| `wPromo` | World Promo (TCGdex's promo bucket) |

Not a Doctrine entity. Exposed read-only at `/api/variants` so the front can render selects with the right labels.

---

## Language

ISO language code that a `Card` exists in. Closed enum, restricted to the languages configured in `pokefolder.catalog.languages` (currently `en`, `fr`).

Not a Doctrine entity. Exposed read-only at `/api/languages` so the front can render selects.

---

## Translation

A row in a sister table holding the localized form of a `Serie`, `Set`, or `Rarity`. Composite primary key `(parent_id, language)`. There is one translation table per parent type (`serie_translation`, `set_translation`, `rarity_translation`). Translations are populated only for configured languages and are merged on sync (a sync in `fr` does not erase an existing `en` translation). See ADR-0007.

---

## Catalog synchronization

The act of pulling Pokémon TCG data from TCGdex into the local database, mapping it to local `Card` rows (and now `Serie`/`Set`/`Rarity` rows). Synchronization is:

- **Manual**: triggered by the user (CLI command or UI action), never automatic
- **Asynchronous**: dispatched onto a RabbitMQ queue, processed by a worker (see [ADR-0001](docs/adr/0001-rabbitmq-over-doctrine-transport.md))
- **Three-level decomposition**: `SyncSeries(language)` → `SyncSets(serieId, language)` → `SyncCards(setId, language)`. One AMQP message per unit at each level.
- **Skip-if-exists by default**: a unit (Serie/Set/Card row) already present in the configured language is skipped without re-fetching its detail. A `force` flag overrides this and refreshes upstream changes.
- **Idempotent**: any re-run is safe — no duplicates, no orphaned rows.

The synchronization is one-way: TCGdex → local DB. The app never writes back to TCGdex.

---

## Out of scope (terms NOT in this glossary)

These terms exist in adjacent products but are explicitly **not part of pokefolder's domain**:

- `User`, `Owner`, `Account` — single-user app, no multi-tenancy concept
- `Trade`, `WishList`, `Wantlist` — no exchange features
- `Price`, `Value`, `MarketValue` — no pricing
- `Deck`, `DeckList` — pokefolder is about collection, not gameplay
- `Scan`, `CardDetection` — phase 2, separate PRD

If a discussion or implementation introduces one of these, that's a signal: either it belongs in a future PRD, or it should be re-mapped onto an existing term.
