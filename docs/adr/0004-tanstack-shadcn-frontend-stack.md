# ADR-0004 — Stack frontend : TanStack (Query + Router + Form) + ShadCN UI + Zod + React Compiler

- **Status**: Accepted
- **Date**: 2026-05-06
- **Related**: PRD #1, slice #4 (bootstrap front) ; toutes les slices UI suivantes
- **Supersedes**: —

## Context

Le frontend pokefolder est en React + TypeScript + Vite (décidé en amont du PRD). Il faut choisir :

- une bibliothèque UI (composants, design system)
- un client de gestion d'état serveur (cache de fetch + mutations)
- un router
- une bibliothèque de formulaires
- un schéma de validation
- la stratégie de mémoïsation (React Compiler ou pas)

Contraintes : single-user, localhost, MVP 3 mois weekend, pas de besoin SSR.

## Decision

| Couche | Choix |
|---|---|
| UI | **ShadCN UI** (Radix + Tailwind, components copy-paste, pas de dépendance lib) |
| State serveur | **TanStack Query** |
| Routing | **TanStack Router** (type-safe, file-based ou code-based selon préférence) |
| Forms | **TanStack Form** |
| Validation | **Zod** |
| Mémoïsation | **React Compiler** activé via plugin Vite (`babel-plugin-react-compiler`) |

## Rationale

### ShadCN UI

- Pas une lib qu'on installe : on copie les composants dans `src/components/ui`, on les modifie librement.
- Aucune dette de version : pas de breaking change subi à chaque mise à jour.
- Esthétique propre, accessibilité Radix gratuite.
- Communauté solide (templates, exemples, blocks).

### TanStack Query / Router / Form

- Stack cohérente d'un même éditeur, primitives composables.
- TanStack Query est le standard de facto pour gérer le cache d'API REST/Hydra.
- TanStack Router apporte un routing **type-safe** (params, search params, loaders typés), ce que React Router v6 ne fait pas nativement.
- TanStack Form gère bien les forms complexes avec validation async, et compose élégamment avec Zod.

### Zod

- Single source of truth pour la validation client + (potentiellement) les types générés.
- Composition simple, inférence TypeScript native.
- Compatible TanStack Form, TanStack Router (search params validation), formulaires simples.

### React Compiler

- Mémoïsation automatique : moins de `useMemo` / `useCallback` à écrire, code plus lisible.
- React 19 stable au moment de la décision, compiler en RC mature, intégration Vite triviale.
- Coût acceptable : si un bug du compiler se manifeste, on désactive le plugin Vite par fichier et on continue.

## Consequences

### Positive

- Stack cohérente, type-safe de bout en bout.
- ShadCN garantit l'absence de lock-in UI.
- Toute la stack TanStack partage les mêmes idiomes (queryClient, signals, etc.) — courbe d'apprentissage capitalisée.
- React Compiler élimine la majorité des micro-optimisations manuelles.

### Negative

- TanStack Router est plus jeune que React Router et expose une API encore en évolution. Risque de breaking change sur une montée de version. Mitigation : verrouiller la version dans `package.json` et réévaluer à chaque montée.
- TanStack Form a moins de patterns documentés que React Hook Form. Mitigation : si un besoin spécifique bloque, fallback possible sur React Hook Form pour un formulaire isolé.
- React Compiler peut générer des warnings ESLint sur du code mal écrit (eg. mutations dans le rendu). Bénéfique à long terme mais demande de l'attention au début.

### Reversal cost

- ShadCN : nul (les composants sont copiés, les remplacer par MUI / Mantine / Headless UI est un travail de remplacement dans le dossier `components/ui`).
- TanStack Query : moyen (les hooks sont disséminés dans les pages, mais l'API est très standard et facilement remplaçable par SWR ou autre).
- TanStack Router : élevé (le routing est structurel, changer demande de réécrire les `Route` et les loaders).
- TanStack Form / Zod : faible (les formulaires sont localisés).
- React Compiler : nul (désactivation = retirer le plugin Vite).
