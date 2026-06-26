# FuelChef Subscription Box

WordPress/WooCommerce plugin for meal delivery subscription boxes. PHP 8.1+, strict types.

## First: read AGENTS.md

This file is the startup guide. AGENTS.md is the canonical AI agent rulebook — read it before any task.

## Key conventions

- Namespace: `FuelChefSubsBox\`, PSR-4 autoloaded from `src/`
- Global prefix: `fuelchef_` for functions, hooks, options, DB tables (enforced by phpcs `PrefixAllGlobals`)
- `declare(strict_types=1)` in every PHP file
- camelCase for all internal code (methods, vars, props)
- No inline comments; docblocks only where WPCS requires

## Architecture (see AI/architecture.md)

`src/` is layered: Frontend/Admin → Services → Repositories. Templates (`templates/`) are presentation-only. No reverse dependencies.

## Commands

| Action | Command |
|--------|---------|
| Lint (all) | `./scripts/docker composer lint` |
| PHPCS only | `./scripts/docker composer phpcs` |
| PHPCBF fix | `./scripts/docker composer phpcbf` |
| PHPStan | `./scripts/docker composer phpstan` |

## Local dev

Fully Dockerized. `./scripts/docker up` — no PHP/Node/Composer on host. See `AI/development.md`.
