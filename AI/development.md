# Development Environment

This project is fully containerized.  
No PHP, Node.js, Composer, or WP-CLI are required on the host machine.

All commands must be executed inside Docker via `./scripts/docker`.

---

## First-Run Setup

```bash
git clone <repo-url>
cd fuelchef-subscription-box

cp docker/.env.example docker/.env   # optional override of defaults

DOCKER_BUILDKIT=1 ./scripts/docker up

./scripts/docker composer install
./scripts/docker npm install
```

Visit:

```
http://localhost:8220
```

to complete WordPress installation.

---

## Quick Start

```bash
./scripts/docker up
./scripts/docker down
```

---

## Core Rule

All development commands MUST run inside the container.

Never run:

* php locally
* composer locally
* npm locally
* wp-cli locally

This ensures environment consistency across all developers and CI.

---

## Running Commands

```bash
./scripts/docker composer install
./scripts/docker composer phpcs
./scripts/docker composer phpcbf
./scripts/docker composer lint
./scripts/docker composer phpstan
./scripts/docker composer scope

./scripts/docker npm install
./scripts/docker npm run commitlint

./scripts/docker wp plugin list --allow-root

./scripts/docker bash
```

---

## Shorthand Scripts

| Command              | Equivalent                         |
| -------------------- | ---------------------------------- |
| `./scripts/composer` | `./scripts/docker composer`        |
| `./scripts/wp`       | `./scripts/docker wp --allow-root` |

---

## Container Management

```bash
./scripts/docker up
./scripts/docker down
./scripts/docker build
./scripts/docker restart
./scripts/docker logs
./scripts/docker exec db bash
```

---

## Services & Ports

| Service    | Port | Purpose          |
| ---------- | ---- | ---------------- |
| WordPress  | 8220 | Frontend         |
| MySQL      | 3445 | Database         |
| phpMyAdmin | 8221 | DB UI            |
| Mailpit    | 8222 | Email testing UI |
| SMTP       | 1031 | Mailpit SMTP     |

---

## Configuration Strategy

This project uses `.dist` based configuration.

| Tracked             | Local override |
| ------------------- | -------------- |
| `phpcs.xml.dist`    | `phpcs.xml`    |
| `phpstan.neon.dist` | `phpstan.neon` |

Rules:

* Never modify `.dist` files for local personal overrides
* Copy `.dist` → local file for local overrides (these are gitignored)
* Edit the `.dist` file directly when making project-wide changes that should be tracked

---

## Inside the Container

The WordPress container includes:

* PHP 8.3
* Composer 2
* WP-CLI
* Node.js 20 + npm
* Git, curl, zip, unzip
* Apache with mod_rewrite enabled

---

## Performance Notes

* `/tmp` uses tmpfs (fast transient storage)
* OPcache enabled with timestamp validation
* BuildKit cache enabled for faster rebuilds
* Apache logs streamed to stdout

---

## Project Mount Point

Plugin is mounted at:

```
/var/www/html/wp-content/plugins/fuelchef-subscription-box
```

---

## Healthchecks

* Database must be healthy before WordPress starts
* WordPress has HTTP healthcheck enabled

---

## Git Hooks

Husky runs automatically:

### Pre-commit — `phpcbf` auto-fix (fast)

When PHP files are staged:

* Runs `phpcbf` auto-fixer
* Re-stages auto-fixed files
* No validation — keeps commits snappy

### Pre-push — `phpcs` + `phpstan` (thorough)

On every push:

* Runs `phpcs` — full coding standards validation
* Runs `phpstan` — static analysis (level 8)
* Blocks push if either fails

### Commit messages

* Validated via commitlint
* Must follow Conventional Commits

---

## Important Rule: Git Discipline

Even in local development:

* Avoid random commits
* Avoid WIP commits pushed to main branch
* Keep commits atomic and meaningful
* Use feature branches for all work

---

## Release & scoping

Releases are cut by `release-please`; the `build-artifact` job then assembles
the distributable zip. The plugin's PHP dependencies are namespaced ("scoped")
under a private prefix so they cannot collide with other plugins' copies.

All of this runs through `scripts/scope` (invoked as `composer run-script
scope`). Crucially, **formatting is owned by the scope script, not the release
workflow** — running scope locally produces byte-identical, release-quality
`src-scoped/`. The pipeline, in order:

1. **php-scoper** — prefixes `src/` + production `vendor/` into `_deps/build/`.
   Its reprint (nikic/php-parser) strips *all* blank lines, leaving dense code.
2. **php-cs-fixer** (`.php-cs-fixer.dist.php`) — restores blank-line
   readability. A narrow `@PSR12` + blank-line rule set; deliberately **not**
   `@Symfony` (which would drop imports and rewrite docblocks).
3. **phpcbf** (`phpcs.xml.dist`) — always runs *after* php-cs-fixer, applying
   the project's WPCS/PSR-12 ruleset (Yoda, array bracket spacing, quotes) so
   scoped code matches the hand-written `src/` style exactly.
4. **`scripts/normalize-header.php`** — runs last; hugs the file docblock to
   `<?php` (no php-cs-fixer/phpcbf rule can express this) and guarantees one
   blank line after it.

Outputs: `vendor-prefixed/` (scoped deps) and `src-scoped/` (scoped, formatted
source) — both generated, both git-ignored. The release workflow only packages
them; it runs a PHPStan smoke test on the artifact but no longer reformats it.

Tool versions (php-scoper, php-cs-fixer, WP excludes) are pinned + SHA256-checked
inside `scripts/scope` and cached in `scripts/.cache`.

---

## Troubleshooting

| Issue                   | Cause                 | Fix                     |
| ----------------------- | --------------------- | ----------------------- |
| Docker not starting     | daemon not running    | start Docker            |
| slow builds             | BuildKit off          | set `DOCKER_BUILDKIT=1` |
| DB connection fails     | DB not healthy        | check `docker logs db`  |
| composer issues         | missing deps          | run `composer install`  |
| pre-commit does nothing | no PHP changes staged | expected                |
| PHPCS failure           | style violation       | run `composer lint`     |
| scope fails             | php-scoper/excludes download blocked | ensure internet access; `scripts/scope` caches them in `scripts/.cache` |
| port conflict           | port in use           | change docker port      |

---

## Dependency Updates (Dependabot)

Automated updates run weekly:

* Composer (Monday)
* npm (Tuesday)
* GitHub Actions (Wednesday)
* Docker (Thursday)

Rules:

* All PRs must pass CI
* Minor/patch updates can be merged safely after review
* Major updates require manual verification
* Do not mix dependency PRs with feature work

---

## Recap — Core Rules

- **Docker-first** — all tooling runs in containers (see above)
- **CI is the final authority** — failing CI = invalid code
- **Git discipline** — atomic commits, feature branches, PRs only, Conventional Commits
