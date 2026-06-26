# AGENTS.md — Canonical AI Agent Rulebook

> **Read this before any task.** It is the single entry point for AI agents
> (Claude, Copilot, Cursor, Aider, Devin, …) and human contributors. It sets the
> non-negotiable rules and links to the detailed knowledge base. When a rule here
> is too brief, follow the linked document — but never contradict it.

FuelChef Subscription Box is a WooCommerce subscription-box plugin for meal
delivery services. It targets **PHP 8.1+** with strict types and an enterprise,
layered OOP architecture bridged onto WordPress's hook system.

## Knowledge base (read the one relevant to your task)

| Topic | Document | Use when |
| --- | --- | --- |
| Code-level rules & conventions | [`AI/standards.md`](AI/standards.md) | Writing/﻿reviewing any PHP |
| Architecture & layering | [`AI/architecture.md`](AI/architecture.md) | Adding features, deciding where code lives |
| PHPStan / type system | [`AI/phpstan.md`](AI/phpstan.md) | Fixing static-analysis errors, typing |
| Local dev environment | [`AI/development.md`](AI/development.md) | Running tooling, Docker, hooks |
| Git / GitHub / releases | [`AI/version-control-guidelines.md`](AI/version-control-guidelines.md) | Branching, commits, PRs, releases |

`AI/standards.md` is the source of truth for code rules; the others expand on
specific areas but never supersede it.

## Golden rules (do not violate)

1. **Read before write.** Search the codebase and copy existing patterns,
   naming, and structure exactly. Do not guess context.
2. **Strict types everywhere.** `declare(strict_types=1);` in every PHP file.
   Explicit parameter, return, and property types. `mixed` is banned in business
   logic — narrow it at WordPress/WooCommerce boundaries.
3. **Respect the layers.** Entry → `Plugin.php` (composition root) → Frontend/
   Admin (controllers + hooks) → Services (business logic) → Repositories (data,
   return DTOs) → WP/WC core. Templates are presentation-only. **No reverse
   dependencies.** See [`AI/architecture.md`](AI/architecture.md).
4. **Security at every entry point.** Sanitize input → verify nonce + capability
   → process → escape output (late escaping only). Every `$wpdb` query with
   variables uses `$wpdb->prepare()`.
5. **CI is the final authority.** Code must pass PHPCS (PSR-12 + WPCS) and
   PHPStan **level 8 + strict-rules** with zero errors before it is "done".

## Naming (enforced — getting these wrong fails CI)

| Scope | Rule | Example |
| --- | --- | --- |
| Internal OOP (classes, methods, vars) | StudlyCaps / camelCase, **never** snake_case | `RenewalService::calculateTotal()` |
| Root namespace | `FuelChefSubsBox\` (PSR-4 from `src/`) | `FuelChefSubsBox\Services\…` |
| Scoped vendor namespace | `FuelChef_Dependencies\` | `FuelChef_Dependencies\Stripe\…` |
| Global functions / hooks / options / meta / tables / transients | **`fuelchef_`** prefix (phpcs `PrefixAllGlobals`) | `fuelchef_get_active_subscriptions()` |
| CSS classes | `fuelchef-` prefix | `.fuelchef-subscription-card` |
| Plugin constants | `FUELCHEF_SUBSCRIPTION_BOX_*` | `FUELCHEF_SUBSCRIPTION_BOX_VERSION` |
| Text domain | `fuelchef-subscription-box` | `__('…', 'fuelchef-subscription-box')` |

> The global prefix is **`fuelchef_`**, not any abbreviation. This is enforced by
> `phpcs.xml.dist`; mismatched prefixes fail the Code Style job.

## Commands (Docker-first — nothing runs on the host)

| Action | Command |
| --- | --- |
| Lint everything | `./scripts/docker composer lint` |
| Code style (PHPCS) | `./scripts/docker composer phpcs` |
| Auto-fix style | `./scripts/docker composer phpcbf` |
| Static analysis | `./scripts/docker composer phpstan` |
| Scope dependencies | `./scripts/docker composer scope` |

See [`AI/development.md`](AI/development.md) for the full environment, ports, and
pre-commit hooks.

## Git & release discipline

- Branch from `main` using `<type>/<kebab-case>`; **never commit or push to
  `main` directly.** All change flows through a PR.
- **Conventional Commits** only. PRs are **squash-merged**, so the PR title
  becomes the commit on `main` and drives the changelog.
- **release-please owns versioning** — never hand-create tags, bump versions in
  code, or edit release commits.
- **Never** hand-edit lock files (`composer.lock`, `package-lock.json`); use the
  package manager. **Never** modify `.github/workflows/**` without explicit human
  approval. **Never** force-push or rewrite shared history.
- If you fail CI 3 times, or hit a merge conflict, or are unsure: **stop and ask.**
  Do not guess. See [`AI/version-control-guidelines.md`](AI/version-control-guidelines.md).

## Definition of done

Implemented • PHPCS clean • PHPStan level 8 clean • Commitlint passes • no debug
artifacts (`var_dump`, `error_log`, dead code) • reviewed • squash-merged.
