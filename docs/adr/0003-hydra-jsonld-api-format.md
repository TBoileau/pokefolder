# ADR-0003 — Hydra / JSON-LD comme format API par défaut

- **Status**: Accepted (à réévaluer à l'usage)
- **Date**: 2026-05-06
- **Related**: PRD #1, slices #5 / #9-#29 (toutes les slices touchant l'API)
- **Supersedes**: —

## Context

API Platform supporte plusieurs formats de réponse out-of-the-box :

- **Hydra / JSON-LD** — défaut historique d'API Platform, hypermedia-driven, riche en metadata (vocabulaire, IRI, opérations exposées par ressource)
- **JSON:API** — standard multi-écosystème, conventions strictes sur les enveloppes (`data`/`included`/`relationships`), bonne intégration avec des clients tiers
- **Plain JSON** — format brut, sans hypermedia ; perd la majorité des features d'API Platform

Le client front est en interne (TanStack Query, généré depuis le schéma exposé). Aucun client tiers à supporter au MVP.

## Decision

Utiliser **Hydra / JSON-LD** comme format de réponse par défaut.

## Rationale

- C'est le défaut d'API Platform : zéro configuration, zéro friction.
- Le navigateur Hydra exposé par API Platform en mode dev (`/api`) donne immédiatement une UI exploratoire complète. Précieux pour un projet solo qui itère vite.
- TanStack Query consomme parfaitement les réponses Hydra (les `hydra:member` se déballent en une ligne dans un select).
- Les types peuvent être générés côté front depuis le schéma OpenAPI exposé en parallèle par API Platform, sans dépendre du parsing Hydra runtime.
- Pas de besoin métier qui pousse vers JSON:API (pas de client tiers à standardiser).

## Consequences

### Positive

- Setup nul : on consomme le défaut sans configuration.
- Documentation API et navigateur exploratoire offerts.
- Migration vers JSON:API ou JSON brut possible plus tard via la config `format` d'API Platform, sans toucher aux entités ni aux tests métier.

### Negative

- Réponses verbeuses (champs `@context`, `@id`, `@type`, `hydra:member`, etc.) si on consomme l'API depuis un environnement où chaque KB compte. Non-bloquant en localhost.
- Un futur client tiers JS/Python pourrait préférer JSON:API. Acceptable : on adaptera si le besoin se concrétise.

### Reversal cost

Faible : changer le `format` par défaut dans `config/packages/api_platform.yaml` ou par opération. Les tests E2E API Platform peuvent rester sur Hydra ou être adaptés.

## Re-evaluation triggers

Re-ouvrir cette décision si :

- Un client tiers entre en jeu et préfère un format standardisé.
- Le volume des réponses devient un problème (peu probable en localhost / single-user).
- Un besoin de pagination cursor-based qu'Hydra ne couvre pas naturellement émerge.
