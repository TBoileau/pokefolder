# ADR-0001 — RabbitMQ pour la queue de synchronisation TCGdex (plutôt que le transport Doctrine)

- **Status**: Accepted
- **Date**: 2026-05-06
- **Related**: PRD #1, slices #6 / #7 / #8
- **Supersedes**: —

## Context

La synchronisation du catalogue Pokémon TCG depuis [TCGdex](https://tcgdex.dev) vers la base locale est asynchrone : un message par set est dispatché sur une queue, traité par un worker Symfony Messenger (voir `Catalogue synchronisation` dans `CONTEXT.md`).

Symfony Messenger supporte plusieurs transports out-of-the-box :

- **Doctrine transport** — la queue est une table Postgres ; aucune dépendance d'infra supplémentaire
- **AMQP transport (RabbitMQ)** — broker dédié ; nécessite un service RabbitMQ déclaré dans `compose.yaml`

Pour un MVP single-user déployé en localhost, le Doctrine transport suffit techniquement à 100% des besoins fonctionnels actuels.

## Decision

Utiliser le **transport AMQP RabbitMQ** dès la première mise en place de l'asynchronisme.

## Rationale

- **Objectif d'apprentissage explicite** : pokefolder est aussi un projet pour s'exercer sur RabbitMQ (le mainteneur en a fait un critère lors du grill du PRD). Cet objectif d'apprentissage prévaut sur l'argument de simplicité du Doctrine transport.
- **Découplage du transport vs. tables applicatives** : la queue n'est pas mêlée aux tables métier ; les opérations Doctrine (migrations, sauvegardes, restaurations) restent indépendantes du contenu de la queue.
- **Coût marginal acceptable** : RabbitMQ ajoute une seule entrée dans `compose.yaml`. La complexité d'usage côté code Symfony Messenger est identique au Doctrine transport (changement de DSN seulement).

## Consequences

### Positive

- Le mainteneur progresse sur RabbitMQ (objectif d'apprentissage atteint).
- Le découplage transport / DB applicative reste propre.
- Migration vers une infra de prod (cluster RabbitMQ, queues prioritaires, dead-letter queues) directe sans réécriture.

### Negative

- Une dépendance d'infra de plus à démarrer en local (`docker compose up` doit lever RabbitMQ avant que la sync fonctionne). Mitigation : healthcheck `GET /health` de la slice #3 vérifie la connectivité AMQP, l'erreur de démarrage est explicite.
- Légère friction onboarding (un nouveau contributeur doit avoir RabbitMQ qui tourne). Acceptable vu le caractère solo du projet.

### Reversal cost

Faible : changer le DSN du transport dans `config/packages/messenger.yaml` et créer la table `messenger_messages` via une migration. Aucun changement de code applicatif.
