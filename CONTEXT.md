# pokefolder — Domain context

Single source of truth for the **domain language** used across `api/` (Symfony) and `app/` (React). Both sides MUST use the terms below as defined here. Drift to synonyms (e.g. "deck" instead of "binder") is a smell — push back or open an issue to evolve the glossary.

This document defines **what things are** and **how they relate**. Architectural decisions live in `docs/adr/`.

---

## Card

A reference entry from the Pokémon TCG catalog, sourced from [TCGdex](https://tcgdex.dev). A `Card` is **catalog data**, not a physical object you own — it is the description of a card that exists in the world.

**Functional identity** (uniqueness key): the tuple `(setId, numberInSet, variant, language)`. Two `Card` rows are the same card iff all four match.

**Properties**:

- `setId` — the TCGdex set identifier (e.g. `swsh1`, `base1`)
- `numberInSet` — the printed number within the set (e.g. `4` for Charizard in Base Set)
- `variant` — the variant identifier (e.g. `holo`, `reverse`, `full-art`, `1st-edition`, `promo`); a "variant" here is whatever TCGdex models as a distinct printing
- `language` — ISO language code (`fr`, `en`, `ja`, `de`, `it`, `es`, …)
- `name`, `rarity`, `imageUrl`, plus other catalog metadata mirrored from TCGdex

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

## Set

A Pokémon TCG set (e.g. "Base Set", "Sword & Shield", "Scarlet & Violet"). Used as a foreign concept (`Card.setId` references a TCGdex set ID). Sets themselves are not modelled as a first-class entity in the local schema — the catalog lives in TCGdex.

---

## Catalog synchronization

The act of pulling Pokémon TCG data from TCGdex into the local database, mapping it to local `Card` rows. Synchronization is:

- **Manual**: triggered by the user (CLI command or UI action), never automatic
- **Asynchronous**: dispatched per set onto a RabbitMQ queue, processed by a worker (see [ADR-0001](docs/adr/0001-rabbitmq-over-doctrine-transport.md))
- **Idempotent**: re-syncing a set updates existing `Card` rows in place, never duplicates

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
