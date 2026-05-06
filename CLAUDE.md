# pokefolder

Monorepo Pokémon TCG : API Symfony/API Platform/Doctrine (`api/`) + front React/TypeScript/Vite (`app/`). Données cartes via TCGdex SDK.

## Agent skills

### Issue tracker

Issues tracked on GitHub (`TBoileau/pokefolder`) via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Five canonical triage roles, default label strings (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context repo: one `CONTEXT.md` + `docs/adr/` at the root, shared across `api/` and `app/`. See `docs/agents/domain.md`.

## Workflow conventions

- **Branches** : une branche par issue, format `issue/<numero>-<slug>`.
- **Merges** : toujours en **rebase and merge** (`gh pr merge <n> --rebase --delete-branch`), jamais de merge commit ni de squash. Garde un historique linéaire propre.
- **Commits** : créer de NOUVEAUX commits sur la branche pendant la review plutôt que d'amender (les commits sont visibles dans la PR).
- **PRs** : la description ferme l'issue via `Closes #<numero>`.
- **Backend layout** : Symfony conventionnel (`src/Controller/`, `src/Entity/`, `src/Repository/`, `src/Message/`, `src/MessageHandler/`, etc.). Pas de DDD/hexa : on revisitera si la codebase grossit.
