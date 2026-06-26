# Architecture

## Purpose

This document defines the actual architecture of the FuelChef Subscription Box plugin.

It describes:
- directory structure
- system layers
- dependency flow
- responsibilities of each layer
- data access rules
- architectural constraints

It does NOT define:
- coding standards → `AI/standards.md`
- development workflow → `AI/development.md`
- version control rules → `version-control-guidelines.md`
- CI/CD or release process → GitHub workflows
- commit conventions → `version-control-guidelines.md`

---

## System Overview

The plugin is a modular WordPress/WooCommerce extension built for PHP 8.1+.

It follows a **layered, feature-based architecture** designed around WordPress execution flow.

Goals of this architecture:
- separate UI flow from business logic
- isolate data access logic
- keep templates presentation-only
- ensure WordPress integration is controlled and predictable
- avoid framework dependency

---

## Directory Structure

```

fuelchef-subscription-box/
├── AI/                         # AI knowledge base (domain rules and system context)
├── scripts/                    # build, tooling, automation scripts
├── docker/                     # local development environment
│
├── src/                        # application source (PSR-4: FuelChefSubsBox)
│   ├── Frontend/              # frontend feature modules (UI + hooks)
│   ├── Admin/                 # admin feature modules (UI + hooks)
│   ├── Services/              # business logic layer
│   ├── Repositories/          # data access layer
│   └── Plugin.php             # bootstrap / dependency wiring layer
│
├── templates/                 # presentation layer (no logic)
├── assets/                    # frontend assets (JS/CSS)
│
├── src-scoped/                # scoped + formatted source (generated, git-ignored)
├── vendor-prefixed/           # scoped production dependencies (generated)
├── vendor/                    # development dependencies (ignored in production)
├── node_modules/              # node dependencies (ignored)
│
├── .github/                   # CI/CD workflows only
├── fuelchef-subscription-box.php
├── scoper.inc.php             # php-scoper config (dependency prefixing)
├── .php-cs-fixer.dist.php      # release formatting rules (see scripts/scope)
└── composer.json

```

---

## Architectural Layers

### 1. Plugin Entry Point

- loads the plugin
- defines constants
- initializes autoloader
- delegates execution to `Plugin.php`

Responsibilities:
- minimal bootstrap logic only
- no feature logic
- no business rules

---

### 2. Bootstrap Layer (`Plugin.php`)

Central wiring layer of the system.

Responsibilities:
- build and configure the **PSR‑11 dependency injection container**
- register all services, controllers, and repositories in the container
- wire system components to WordPress lifecycle
- act as dependency composition root

Must NOT:
- contain business logic
- contain UI rendering logic
- implement feature behavior

All class wiring **must** go through the container; direct `new` instantiation of
services, controllers, or repositories is forbidden (see `AI/standards.md` §6).

---

### 3. Frontend / Admin Feature Modules

Located in:

```

src/Frontend/
src/Admin/

```

These are **feature-based modules acting as controllers**.

Each file represents a feature boundary (not a controller class).

Responsibilities:
- register WordPress hooks (`add_action`, `add_filter`)
- handle request flow for a feature
- call services for business/data processing
- pass prepared data to templates
- render templates

Allowed:
- calling Services (primary dependency)
- interacting with WordPress lifecycle
- coordinating UI flow for a feature

Must NOT:
- contain business logic
- perform database queries directly
- duplicate reusable domain logic

---

### 4. Services Layer

Located in:

```

src/Services/

```

This is the **business logic layer**.

Responsibilities:
- implement domain rules
- handle WooCommerce and subscription logic
- transform and validate data from repositories
- orchestrate multiple repositories
- define business-level workflows

Allowed:
- calling Repositories
- using WordPress filters/actions when part of business rules
- being reused across Frontend/Admin modules

Must NOT:
- handle rendering or templates
- decide UI flow or presentation logic
- perform raw database queries

---

### 5. Repositories Layer

Located in:

```

src/Repositories/

```

This is the **data access layer**.

Responsibilities:
- all data retrieval and persistence
- abstract WordPress, WooCommerce, and custom data sources
- normalize data structures for services

---

## Data Access Rules

Repositories must follow this priority order:

### 1. Prefer WordPress / WooCommerce APIs (default)
Use official APIs when available:
- `get_post()`
- `get_user_meta()`
- `get_term()`
- WooCommerce CRUD objects (`WC_Product`, `WC_Order`, etc.)
- WooCommerce helper functions

This is always the preferred approach.

---

### 2. Use `$wpdb` only for custom data needs
`$wpdb` is allowed only for:

- custom plugin tables
- performance-critical queries not supported by APIs
- complex queries not exposed by WordPress/WooCommerce APIs

When using `$wpdb`:
- must use `$wpdb->prepare()`
- must return structured data only
- must not bypass security practices
- Cache results when possible and invalidate them.

---

### 3. Forbidden usage of `$wpdb`

Repositories MUST NOT:
- query core tables directly (`wp_posts`, `wp_postmeta`, `wp_users`)
- reimplement WooCommerce or WordPress internal logic
- bypass available APIs for core entities

If data exists in WordPress/WooCommerce APIs, those APIs must be used instead of raw SQL.

---

### Repository Responsibilities Summary

Repositories:
- isolate data source complexity
- return structured, normalized data
- never contain business logic
- never handle hooks or filters
- never handle presentation logic

---

### 6. Templates Layer

Located in:

```

templates/

```

This is the **presentation layer**.

Responsibilities:
- render HTML output
- display data provided by controllers
- remain logic-free

Must NOT:
- access database
- call services or repositories
- contain business logic
- perform transformations

Templates only display prepared data.

---

### 7. Assets Layer

Located in:

```

assets/

```

Responsibilities:
- JavaScript
- CSS
- admin/frontend UI behavior

Rules:
- no direct dependency on PHP logic
- interacts via localized data or endpoints only
- must remain decoupled from backend structure

---

## Dependency Flow

All dependencies MUST follow this direction:

```

Plugin Entry
↓
Bootstrap (Plugin.php) — PSR‑11 container
↓
Frontend/Admin Modules
↓
Services
↓
Repositories
↓
WordPress / WooCommerce Core

```

### Container-based wiring

All dependencies are resolved through the PSR‑11 container built in `Plugin.php`.
The composition root is the **single place** where the wiring graph is defined;
controllers, services, and repositories never instantiate their own dependencies.

### Strict rules:
- no reverse dependency is allowed
- lower layers must never depend on higher layers

---

## WordPress Integration Rules

- WordPress hooks are registered only in Frontend/Admin modules or Bootstrap
- Services may trigger hooks when part of business logic
- Repositories must never interact with hooks
- Templates must never call WordPress APIs where avoidable

---

## Data Flow Principle

All data passed to templates must be:
- fully prepared
- validated
- sanitized

Templates are strictly responsible for:
- rendering
- formatting markup

---

## Separation of Concerns

| Layer | Responsibility |
|------|----------------|
| Plugin Entry | initialization |
| Plugin.php | PSR‑11 container wiring |
| Frontend/Admin | feature orchestration + hooks |
| Services | business logic |
| Repositories | data access |
| Templates | presentation |
| Assets | UI behavior |

---

## Design Principles

This architecture is based on:

- feature-based modular design
- WordPress hook-driven execution model
- strict separation of concerns
- explicit dependency direction
- minimal global state usage
- API-first data access (WordPress/WooCommerce preferred)

---

## System Constraints

The system MUST:
- support PHP 8.1+
- remain WordPress compatible
- remain modular and extensible
