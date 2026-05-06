# ADR-0002 — Une entrée `OwnedCard` par exemplaire physique (plutôt qu'une quantité agrégée)

- **Status**: Accepted
- **Date**: 2026-05-06
- **Related**: PRD #1, slice #12 ; `CONTEXT.md` (`OwnedCard`)
- **Supersedes**: —

## Context

Modéliser la collection demande de choisir comment représenter le fait que l'utilisateur possède N exemplaires d'une carte. Deux options principales :

### Option A — Quantité agrégée

Une seule ligne `OwnedCard` par `(card, condition)`, avec un champ `quantity`. Si l'utilisateur a 4 Charizard NM, c'est une ligne avec `quantity = 4`.

Avantages : moins de lignes en DB, agrégats triviaux (`SUM(quantity)`).

Inconvénients :

- Un classeur ne peut pas référencer "le 2ème exemplaire de mes 4 Charizard NM" — on perd l'identité de chaque copie physique.
- Le placement carte-par-carte dans des slots distincts impose de réintroduire une notion d'identité d'exemplaire (donc de revenir vers l'option B).
- Toute opération « je place un Charizard dans le classeur A et un autre dans le classeur B » exige de splitter une ligne en deux, ce qui est contre-intuitif et bug-prone.

### Option B — Une ligne par exemplaire physique

Chaque carte physique est une ligne `OwnedCard` distincte. 4 Charizard NM = 4 lignes. Chacune peut référencer son propre `BinderSlot` (ou aucun).

Avantages :

- Identité physique préservée — chaque copie est une entité métier de premier ordre.
- Le placement dans les classeurs devient trivial : un `BinderSlot.ownedCard` est une simple FK vers une ligne `OwnedCard` unique.
- Conditions hétérogènes au sein d'un même groupe (3 NM + 1 LP) sont naturellement représentées sans champ ad-hoc.

Inconvénients :

- Plus de lignes en DB (proportionnel à la taille de la collection physique).
- Vue agrégée (« j'ai N cartes au total ») demande un `GROUP BY` ou `COUNT`.

## Decision

**Option B** : une ligne `OwnedCard` par exemplaire physique.

## Rationale

- Le placement par classeur est central au MVP (slices #21 → #29). Toute solution qui complique ce placement est disqualifiée.
- Symfony / Doctrine / Postgres supportent sans souci des dizaines de milliers de lignes ; le coût en stockage est négligeable face au gain de clarté du modèle.
- L'agrégation côté lecture (vue `/collection` agrégée par carte) est traitée via une projection en lecture, pas via une dénormalisation côté écriture.
- L'invariant "1 carte physique = 1 entité" colle à la réalité du domaine : si je sors une carte de mon binder pour la jouer ou la trader, je manipule **une** carte précise, pas un compteur abstrait.

## Consequences

### Positive

- Modèle de données aligné sur la réalité physique du domaine.
- Placement / déplacement / retrait dans les classeurs sont des opérations triviales sur une seule entité.
- Possibilité future d'ajouter des attributs par exemplaire (date d'acquisition, prix payé, provenance) sans refactoring.

### Negative

- La vue agrégée du `/collection` (PRD slice #14) demande un `GROUP BY` côté API. Coût marginal, supporté par Postgres + index sur `(card_id, condition)`.
- Le formulaire d'ajout (slice #13) ne peut pas dire "ajouter 4 Charizard NM en une fois" tel quel — soit on ajoute un champ `quantity` qui crée N rows en backend, soit on assume une UX d'ajout unitaire. À trancher en cours d'implémentation de #13.

### Reversal cost

Élevé : passer en mode agrégé exigerait de recréer le schéma `BinderSlot` et de migrer toute relation `OwnedCard ↔ BinderSlot`. À éviter sauf changement de paradigme du PRD.
