# FuelChef Subscription Box: Comprehensive Coding Standards

This document defines the **absolute, unyielding code-level rules, conventions, and architectural standards** for the FuelChef Subscription Box plugin. 

It serves as the definitive source of truth for:
- PHPCS (PHP CodeSniffer) rules and automated linting
- PHPStan static analysis expectations (Level 8 with strict rules)
- Peer code review standards
- AI agent behavior and code generation prompts

**Rule of Thumb:** If a rule, pattern, or convention is not explicitly codified here, it is not a standard. Supporting reference documents (e.g., `AI/*.md`) expand on specific technical implementations but never supersede this document. 

---

## 1. Core Architectural Principles

All code contributed to this plugin must strictly adhere to the following software engineering principles. We bridge the gap between modern, enterprise-grade Object-Oriented Programming (OOP) and the procedural nature of WordPress.

### 1.1 SOLID Principles
- **Single Responsibility Principle (SRP):** A class or method must have one, and only one, reason to change. Separating UI logic, database access, and business rules is mandatory.
- **Open/Closed Principle (OCP):** Code must be open for extension but closed for modification. Use interfaces, abstract classes, and WordPress hooks (`apply_filters`, `do_action`) to allow behavior modification without altering core classes.
- **Liskov Substitution Principle (LSP):** Subtypes must be completely substitutable for their base types. Never change the expected return type or throw unexpected exceptions in a child class or interface implementation.
- **Interface Segregation Principle (ISP):** Prefer small, client-specific interfaces over large, monolithic ones. Do not force classes to implement methods they do not need.
- **Dependency Inversion Principle (DIP):** High-level modules must not depend on low-level modules; both should depend on abstractions (interfaces). Inject dependencies via constructors rather than hardcoding instantiations (`new ClassName()`) inside methods.

### 1.2 General Design Philosophies
- **Clarity over Cleverness:** Readable, strictly-typed code always wins over terse, "clever" one-liners.
- **Immutability First:** State mutations are the root of many bugs. Favor `readonly` classes, properties, and Data Transfer Objects (DTOs) over mutable objects.
- **Composition over Inheritance:** Avoid deep class hierarchies. Inherit only when an "is-a" relationship is undeniable; otherwise, inject dependencies to share behavior.
- **WordPress Compatibility First:** While we use modern PHP, we do not reinvent the wheel if a robust WordPress API exists (e.g., WP_Query, Transients, Options API), provided it is wrapped in our type-safe layers.

---

## 2. PHP Version & Syntax Constraints

- **Target Environment:** PHP 8.1+
- **Strict Typing:** Strict types are strictly mandatory in *every* PHP file without exception.

```php
<?php
declare(strict_types=1);
```

### 2.1 Required Modern PHP 8.1+ Usage
- **Constructor Property Promotion:** Mandatory for all classes and DTOs to reduce boilerplate.
- **Readonly Properties/Classes:** Use `readonly` for properties and classes wherever data is immutable (e.g., DTOs, value objects).
- **Enums:** Use native PHP 8.1 Enums (preferably Backed Enums) instead of class constants for fixed sets of values (e.g., Order Statuses, Plan Types).
- **Match Expressions:** Prefer `match` over `switch` for concise, exhaustively checked return values.
- **Named Arguments:** Use named arguments for functions with more than three parameters or when boolean flags are passed, to enhance readability.

### 2.2 Class Rules
- **Final by Default:** All classes must be marked `final class` by default. Remove `final` only if inheritance is explicitly required and designed for.
- **Abstract Classes:** Use abstract classes *only* to share baseline implementations across strict child classes.
- **Utility Classes:** Classes containing only static methods (e.g., basic helpers) must have a `private function __construct() {}` to explicitly prevent instantiation.
- **One Class Per File:** Strictly enforced. The filename must exactly match the class name.
- **Traits:** Use traits sparingly. They are for horizontal code reuse only, not for sharing state or bypassing proper dependency injection.

### 2.3 Strict Return and Property Types
Every method, function, and property **MUST** declare an explicit type.

```php
// Correct
public function calculateTotal(float $basePrice, float $taxRate): float;
public function findCustomer(int $id): ?Customer;
public function processOrder(): void;

// Wrong — missing or inferred return types
public function calculateTotal($basePrice, $taxRate);
public function findCustomer(int $id);
```

### 2.4 Union Types & Banning "Mixed"
- Union types (`string|int`) are allowed only when unavoidable (often when interfacing with WP APIs).
- **`mixed` is banned** as a property type or return type in business logic. If a WP API returns `mixed`, it must be immediately validated and cast to a strict type or DTO (Detailed further in the Data Handling section).

---

## 3. File Structure & Formatting Rules

Every PHP file must adhere to a strict structural order. Blank lines must separate each section.

**The Mandatory Order:**
1. Opening `<?php` tag (no closing `?>` tag ever)
2. File-level docblock — sits **directly under `<?php`** with no blank line
   between them. A short description only; do **not** add `@package` (the
   namespace already conveys grouping, and the phpcs requirement is disabled).
3. `declare(strict_types=1);`
4. `namespace` declaration
5. `use` statements, strictly grouped and alphabetized:
    * Group 1: Native PHP classes/interfaces (e.g., `use Exception;`)
    * Group 2: Third-party/Vendor imports
    * Group 3: Project-specific imports
6. Class/Interface docblock
7. Class/Interface definition

Blank lines separate each section *except* between `<?php` and the file
docblock, which must hug. This is the exact shape the release pipeline emits for
scoped code too (see `AI/development.md` → Release & scoping).

**Example:**
```php
<?php
/**
 * Handles subscription renewals.
 */

declare(strict_types=1);

namespace FuelChefSubsBox\Services;

use InvalidArgumentException;
use FuelChef_Dependencies\SomeVendor\Library;
use FuelChefSubsBox\Repository\Subscriptions;

/**
 * Orchestrates subscription renewal workflows.
 */
final class RenewalService
{
    // ...
}
```

---

## 4. Naming Conventions

### 4.1 Internal Code (PSR-12 / CamelCase Strictly Enforced)
Even though WordPress historically uses `snake_case`, **our internal OOP code NEVER uses `snake_case`**. 

Inside the `FuelChefSubsBox\` namespace, standard modern PHP conventions apply:

| Construct | Convention | Example |
| :--- | :--- | :--- |
| Classes / DTOs | StudlyCaps | `SubscriptionPlan`, `OrderService` |
| Interfaces | StudlyCaps + `Interface` | `PaymentGatewayInterface` |
| Traits | StudlyCaps + `Trait` | `LoggerAwareTrait` |
| Abstract Classes| `Abstract` + StudlyCaps| `AbstractPaymentGateway` |
| Enums | StudlyCaps | `SubscriptionStatus` |
| Methods | camelCase | `calculateTax()` |
| Properties | camelCase | `private int $planId` |
| Variables / Args| camelCase | `int $userId` |
| Constants | UPPER_SNAKE_CASE | `public const MAX_RETRY_LIMIT = 3;` |

*Note on Acronyms:* Treat acronyms as standard words. Use `$apiUrl` not `$APIUrl`; use `getHtml()` not `getHTML()`.

### 4.2 WordPress Global Layer Naming
Anything that registers in the WordPress global scope (where it can collide with other plugins) must use the `fuelchef_` prefix.

| Type | Prefix | Example |
| :--- | :--- | :--- |
| Global Functions| `fuelchef_` | `fuelchef_get_active_subscriptions()` |
| Action/Filter Hooks| `fuelchef_` | `fuelchef_before_subscription_renewal` |
| Options/Meta Keys | `fuelchef_` | `_fuelchef_stripe_customer_id` |
| Database Tables | `fuelchef_` | `{$wpdb->prefix}fuelchef_subscriptions` |
| Transients | `fuelchef_` | `fuelchef_transient_plan_list` |

### 4.3 Plugin Constants
Defined exclusively in the main plugin bootstrap file (`fuelchef-subscription-box.php`).
Format: `FUELCHEF_SUBSCRIPTION_BOX_*`

```php
define('FUELCHEF_SUBSCRIPTION_BOX_VERSION', '1.0.0');
define('FUELCHEF_SUBSCRIPTION_BOX_PATH', plugin_dir_path(__FILE__));
```

### 4.4 Exceptions: WordPress / External APIs
Never alter the casing of native WordPress or WooCommerce functions.
```php
// Allowed usage of snake_case (Calling external APIs)
$product = wc_get_product($productId);
$option  = get_option('admin_email');
```

---

## 5. Namespacing & Autoloading Rules

- **Root Namespace:** All plugin code lives strictly under `FuelChefSubsBox\`.
- **Directory Mapping:** The root namespace maps to the `src/` directory following strict PSR-4 autoloading standards.
    - Example: `FuelChefSubsBox\Repositories\UserRepository` MUST reside in `src/Repositories/UserRepository.php`.

### 5.1 Third-Party Dependencies & Scoping
To prevent dependency conflicts with other WordPress plugins that might use different versions of the same libraries (e.g., Guzzle, Stripe SDK), all third-party vendor code must be scoped/prefixed.

- **Scoped Namespace:** `FuelChef_Dependencies\`
- **Rule:** Never reference unscoped vendor code (e.g., `GuzzleHttp\Client`) in production runtime. You must use the scoped equivalent (e.g., `FuelChef_Dependencies\GuzzleHttp\Client`) generated via our scoping scripts (e.g., PHP-Scoper or Strauss).
- **Enforcement:** Bypassing the scoped namespace in shipped code is a critical failure.

## 6. Architecture & Dependency Injection

To maintain a SOLID, testable, and maintainable codebase, we strictly control how classes are instantiated and how they interact.

### 6.1 Constructor Injection Only
Never use `new` to instantiate a service, controller, repository, or any class
with external dependencies inside another class. Dependencies must be passed
through the constructor. DTOs and value objects are exempt — they have no
dependencies and may be constructed with `new` (typically inside a static
factory method like `fromArray`).

```php
// Wrong — tightly coupled, untestable
final class OrderProcessor
{
    public function process(int $orderId): void
    {
        $logger = new Logger(); // Violation
        $logger->log("Processing $orderId");
    }
}

// Correct — loosely coupled via Dependency Injection
final readonly class OrderProcessor
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function process(int $orderId): void
    {
        $this->logger->log("Processing $orderId");
    }
}
```

### 6.2 No Global State
- The `global` keyword is **strictly forbidden** in OOP code.
- Singleton pattern (`getInstance()`) is **strictly forbidden everywhere**,
  including the bootstrap layer. Service lifecycle is managed exclusively by
  the PSR-11 container (see §6.3).

### 6.3 PSR-11 Dependency Injection Container

A PSR-11 container (`Psr\Container\ContainerInterface`) is the single source
of truth for service resolution and lifecycle management. It replaces all
previous uses of the singleton pattern.

- **One container, one composition root.** The container is built and
  configured in `Plugin.php` (the composition root) only. No other class
  should touch the container directly.
- **Registration.** Every service, controller, and repository is registered
  in the container at boot time. Registration maps an interface (or class
  name) to a factory that builds and returns the instance.
- **Resolution.** Classes receive their dependencies through constructor
  injection. The container resolves those dependencies automatically (via
  autowiring) or explicitly (via registered factories). No class calls
  `$container->get(...)` except the composition root.
- **Lifecycle.** The container controls when services are created. Services
  that must be shared (one instance per request) are configured as shared
  in the container. No class implements `getInstance()` or tracks its own
  singleton state.

```php
// Plugin.php — composition root (pseudo-code)
$container = new Container();
$container->bind(LoggerInterface::class, Logger::class);
$container->bind(RenewalService::class, fn ($c) => new RenewalService(
    $c->get(LoggerInterface::class)
));
$container->get(AdminController::class)->registerHooks();
```

All other classes receive their dependencies through normal constructor
injection — they have no awareness of the container.

---

## 7. WordPress Integration Rules

WordPress relies heavily on global hooks (`add_action`, `add_filter`). We must bridge this procedural system with our OOP architecture carefully.

### 7.1 Hook Registration Boundaries
Hooks are registered **only** in specific architectural layers:
- `src/Frontend/*` (Frontend Controllers/Listeners)
- `src/Admin/*` (Admin Controllers/Listeners)
- `src/Plugin.php` (Core Bootstrap layer)

### 7.2 Services vs. Hooks
- **Services** contain pure business logic, workflow orchestration, and domain rules.
- Services **MAY** trigger WordPress hooks (`do_action()`, `apply_filters()`) to allow third-party extensibility of our business logic.
- Services **MUST NOT** register themselves to WordPress hooks (`add_action()`) as their primary initialization. The Controller/Admin layer should handle listening to WordPress and then delegating to the Service.

### 7.3 Entry Points (Controllers)
All WordPress request handling starts in Frontend/Admin modules. These act as feature controllers. They listen to a WP hook, extract the data, validate permissions, and pass the data into the pure Service layer.

### 7.4 Custom Hook Documentation

Every custom hook (`do_action()`, `apply_filters()`) the plugin fires must be
documented with a PHPDoc-style comment directly above the call. This allows
third-party developers (and IDEs) to discover and use the hooks.

```php
/**
 * Fires after a subscription renewal is processed.
 *
 * @param positive-int $subscriptionId The renewed subscription ID.
 * @param float        $amount         The total charged amount.
 */
do_action('fuelchef_subscription_renewed', $subscriptionId, $amount);
```

Hooks documented this way **do not** need a separate `@see` or `@link` — the
comment at the call site is the canonical reference.

### 7.5 Asset Enqueuing

Scripts and styles must be enqueued through WordPress's standard functions
(`wp_enqueue_script`, `wp_enqueue_style`). Never hardcode `<link>` or
`<script>` tags.

- **Version parameter.** Always pass `FUELCHEF_SUBSCRIPTION_BOX_VERSION` as
  the version argument to ensure cache-busting on plugin updates.
- **JavaScript data.** Pass configuration and nonces to scripts via
  `wp_localize_script()` or `wp_add_inline_script()`. Never echo PHP
  variables directly into inline `<script>` tags.

---

## 8. Data Handling Rules

### 8.1 The "Input → Validate → Process" Pipeline
Every request handled by the plugin must strictly follow this pipeline:
1. **Sanitize Input:** Clean raw data immediately upon receipt.
2. **Validate Intent:** Check nonces (CSRF protection) and capabilities (RBAC).
3. **Process:** Pass the sanitized, strictly-typed data to the Service layer.

Output escaping is a separate concern handled exclusively by the presentation
layer at render time (see §8.3). It is intentionally absent from this pipeline
to keep business logic free of HTML concerns.

### 8.2 Sanitization
Never trust superglobals directly (`$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, `$_SERVER`). 
Use the appropriate WordPress sanitization functions before assigning variables.

```php
// Wrong
$email = $_POST['user_email'];

// Correct
$email = sanitize_email(wp_unslash($_POST['user_email'] ?? ''));
```

### 8.3 Late Escaping (Output Boundary)
Data must be kept in its raw, unescaped form while in the database and during business logic processing. It is **only** escaped when outputting to HTML.

- Use `esc_html()` for text content.
- Use `esc_attr()` for HTML attributes.
- Use `esc_url()` for links.
- Use `wp_kses_post()` for complex HTML strings.

---

## 9. Type Safety & Strict Checking

Type safety is the highest priority in this codebase. Every value flowing through the system MUST have a known, explicit type at every point.

### 9.1 Avoid `empty()`
Do not use `empty()`. It conflates multiple states (`null`, `false`, `0`, `''`, `'0'`, `[]`) and masks real bugs.

```php
// Wrong — hides the distinction between null, false, and zero
if (empty($result)) { ... }

// Correct — explicit checks for the expected falsy state
if ($result === null) { ... }
if ($result === 0) { ... }
if ($result === '') { ... }
if ($result === []) { ... }
```

### 9.2 Strict Comparisons Only
Always use `===` and `!==`. Never use loose comparisons (`==` or `!=`).

```php
// Correct
if ($status === 'active') { ... }
if (in_array($id, $ids, true)) { ... } // Note the true flag

// Wrong — loose comparison
if ($status == 'active') { ... }
if (in_array($id, $ids)) { ... } // Missing strict flag
```

### 9.3 Eradicating `mixed`
- No method parameter can be typed as `mixed` unless it is immediately narrowed via a guard clause.
- No method may return `mixed`.
- If a WordPress API returns `mixed` (e.g., `get_option()`), wrap it behind a typed helper or validate it immediately.

### 9.4 WordPress Value Helpers
Because `get_option()` or `get_post_meta()` return `mixed`, create utility helpers in `src/Helpers/` to enforce types.

```php
// src/Helpers/OptionHelper.php
final class OptionHelper
{
    public static function getString(string $key, string $default = ''): string
    {
        $value = get_option($key, $default);
        return is_string($value) ? $value : $default;
    }
}

// Usage in business logic:
$apiKey = OptionHelper::getString('fuelchef_api_key'); // Guaranteed to be a string
```

### 9.5 Inline `@var` Annotations
When PHPStan cannot infer a type (common when interacting with WP or generic collections), use an inline `@var` annotation. 
*Rule:* Place `@var` on the line immediately before the variable assignment.

```php
/** @var list<int> $userIds */
$userIds = get_users(['fields' => 'ID']);
```

### 9.6 Advanced PHPStan Types
Leverage advanced PHPStan types in PHPDoc to encode business rules:
- Prefer **Array Shapes** (`array{id: int, status: string}`) over generic arrays.
- Use `list<T>` for sequential, 0-indexed arrays instead of `array<int, T>`.
- Use specific primitives: `positive-int`, `non-empty-string`, `int<0, 100>`.
- Use `class-string<T>` when dealing with dynamic class instantiation.

---

## 10. Database Rules

WordPress databases can become bottlenecks. Strict rules govern how we interact with data persistence.

### 10.1 The Hierarchy of Data Access
When fetching or saving data, follow this exact order of preference:
1. **WordPress APIs** (e.g., Options, Transients, WP_Query) — *Highest Priority*
2. **WooCommerce APIs** (e.g., `wc_get_orders()`, `WC_Data_Store`)
3. **`$wpdb` Direct Queries** — *Absolute Last Resort*

### 10.2 Allowed `$wpdb` Usage
Direct queries via global `$wpdb` are strictly limited to:
- Interacting with custom plugin tables (e.g., `fuelchef_subscriptions`).
- Highly performance-critical bulk operations where WP_Query is demonstrably too slow.
- Complex `JOIN` statements that native APIs cannot accommodate.

### 10.3 Forbidden `$wpdb` Usage
You **MUST NEVER** use `$wpdb` to manually query, insert, or update:
- `wp_posts`
- `wp_postmeta`
- `wp_users`
- `wp_usermeta`
- WooCommerce Core Tables (e.g., `wc_orders`, `wc_order_meta`)
*If an API exists to handle this data, it must be used to ensure cache invalidation and hooks fire correctly.*

### 10.4 SQL Injection Prevention (`$wpdb->prepare`)
**Every single `$wpdb` query containing variables must use `$wpdb->prepare()`.** 
Zero exceptions. Raw string concatenation in SQL queries is a fireable offense.

```php
// Wrong — High risk of SQL injection
$wpdb->get_results("SELECT * FROM {$wpdb->prefix}fuelchef_subs WHERE status = '$status'");

// Correct
$query = $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}fuelchef_subs WHERE status = %s",
    $status
);
$results = $wpdb->get_results($query, ARRAY_A);
```

### 10.5 Narrowing `$wpdb` Results
`$wpdb->get_results` and `$wpdb->get_row` return `array|object|null`. You must narrow this immediately before passing it to Services or Repositories.

```php
$rows = $wpdb->get_results($query, ARRAY_A);

if (!is_array($rows)) {
    return [];
}

/** @var list<array{...}> $rows */
// Proceed to map $rows to DTOs...
```

### 10.6 Schema Versioning & Migration

Custom table schemas (see §10.2) are versioned and migrated on plugin
activation. The plugin stores a schema version option and compares it against
the current version defined in code.

- **Version option.** Store the current schema version in
  `get_option('fuelchef_db_version')` after a successful migration.
- **Migration functions.** Each version increment has its own migration
  function (e.g., `migrate_to_2()`) called from the activation hook. Use
  `dbDelta()` for CREATE/ALTER TABLE statements.
- **Idempotency.** Every migration must be safe to run multiple times
  (`IF NOT EXISTS`, `IF EXISTS` on ALTER).
- **No automatic rollback.** Migrations are forward-only. Schema downgrades
  are handled by deploying an older plugin version (which ships the older
  schema version).

```php
// Activation hook pseudo-code
$currentVersion = (int) get_option('fuelchef_db_version', 0);
if ($currentVersion < 2) {
    migrate_to_2();  // calls dbDelta()
    update_option('fuelchef_db_version', 2);
}
```

## 11. Services Layer Rules

The Service layer is the heart of the plugin. It contains pure business logic, domain rules, workflow orchestration, and calculations. 

### 11.1 Service Responsibilities
Services **MUST**:
- Coordinate actions between Repositories, external APIs, and WooCommerce.
- Be completely ignorant of the HTTP context (`$_GET`, `$_POST`).
- Rely exclusively on Dependency Injection for external classes.

### 11.2 Forbidden Service Actions
Services **MUST NOT**:
- Render templates or generate HTML.
- Access the database directly (this is the Repository's job).
- Read directly from `$_POST` or `$_GET`.
- `exit()` or `die()` — always throw exceptions or return result objects to let the Controller handle the HTTP response.

---

## 12. Repositories & Data Transfer Objects (DTOs)

To decouple our business logic from WordPress's database intricacies, we strictly enforce the Repository Pattern. 

### 12.1 Repository Responsibilities
- Abstract all data retrieval and persistence logic.
- Interact with `$wpdb`, `WP_Query`, or WooCommerce data stores.
- **Rule:** Repositories MUST NOT contain business logic. They only store and retrieve data.

### 12.2 Strict DTO / Entity Return Types
Repositories **MUST NEVER** return raw arrays, `stdClass`, or `mixed`. Every repository method must return a tightly defined Data Transfer Object (DTO) or a domain Entity.

```php
// Wrong — raw array leaks database structure into the app
public function findSubscription(int $id): ?array;

// Correct — strongly typed DTO
public function findSubscription(int $id): ?SubscriptionEntity;
```

### 12.3 DTO / Entity Pattern Construction
DTOs and Entities must be `final readonly` classes with strongly typed promoted properties. They encapsulate their own hydration logic via a static factory method.

Constructors **MUST** validate their arguments immediately to prevent invalid
state from existing in the system (see also §9.3 — no invalid state).

```php
final readonly class SubscriptionPlan
{
    /**
     * Creates a new SubscriptionPlan instance.
     *
     * @param positive-int     $id       The plan ID.
     * @param non-empty-string $name     The plan name.
     * @param float            $price    The plan price. Must be non-negative.
     * @param non-empty-string $currency ISO 4217 currency code.
     * @param non-empty-string $status   The plan status.
     *
     * @throws InvalidArgumentException If price is negative or name is empty.
     */
    public function __construct(
        public int $id,
        public string $name,
        public float $price,
        public string $currency,
        public string $status,
    ) {
        if ($price < 0.0) {
            throw new InvalidArgumentException('Price must be non-negative.');
        }
        if (trim($name) === '') {
            throw new InvalidArgumentException('Plan name cannot be empty.');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            price: (float) ($data['price'] ?? 0.0),
            currency: (string) ($data['currency'] ?? 'USD'),
            status: (string) ($data['status'] ?? 'active'),
        );
    }
}
```
*Rule:* The `fromArray` method is the *only* place that touches raw, unpredictable database data. Once instantiated, the DTO is guaranteed to be type-safe throughout the rest of the application.

*Note on `mixed` in `fromArray`:* The `@param array<string, mixed>` annotation
is the **only** place `mixed` is permitted in the entire codebase. It is
strictly confined to this hydration boundary — inside `fromArray` the raw
values are cast to explicit types before they enter the rest of the system.
See §2.4 and §9.3 for the general `mixed` ban.

### 12.4 Returning Collections
When returning multiple records, use `list<ClassName>` in the PHPDoc to indicate sequential, 0-indexed keys.

```php
/**
 * @return list<SubscriptionPlan>
 */
public function findAllActive(): array
{
    $rows = $this->wpdb->get_results("SELECT * FROM {$this->table} WHERE status = 'active'", ARRAY_A);
    
    if (!is_array($rows)) {
        return [];
    }

    /** @var list<SubscriptionPlan> */
    return array_map([SubscriptionPlan::class, 'fromArray'], $rows);
}
```

---

## 13. Templates Rules

Templates are the purest form of the presentation layer. 
- **No Business Logic:** Do not run queries or instantiate services inside templates.
- **No Data Fetching:** Variables must be passed into the template from the Controller.
- **Escape Everything:** Every dynamic variable outputted in a template must be wrapped in an escaping function (`esc_html`, `esc_attr`, `wp_kses_post`).

---

## 14. Security Standards

Every entry point (AJAX, REST API, Form Submission, Admin Page) must rigorously enforce the following:

1. **Verify Intent (Nonces):** Use `wp_verify_nonce()` for state-changing requests to prevent CSRF.
2. **Verify Capability (RBAC):** Use `current_user_can('manage_options')` (or custom capabilities) to ensure the user has authorization.
3. **Safe Redirects:** Never use raw `header('Location: ...')`. Use `wp_safe_redirect()` to prevent Open Redirect vulnerabilities.

---

## 15. Internationalization (i18n)

All user-facing text must be fully translatable using the standard WordPress i18n functions.

- **Text Domain:** `fuelchef-subscription-box`
- **No Variable Concatenation:** Translators cannot translate fragmented sentences. Use `sprintf()`.
- **Translator Comments:** If a string contains placeholders (`%s`, `%d`), you **MUST** provide a translator comment explaining what the placeholder represents.

```php
// Wrong
echo __('Hello ', 'fuelchef-subscription-box') . $userName;

// Correct
/* translators: %s: User's display name */
echo esc_html(sprintf(__('Hello %s', 'fuelchef-subscription-box'), $userName));
```

---

## 16. Error Handling & Exceptions

- **Custom Exceptions:** Use domain-specific, typed exceptions (e.g., `SubscriptionNotFoundException`, `PaymentFailedException`) extending native SPL exceptions.
- **Never Silence Errors:** The `@` operator is strictly forbidden.
- **Graceful Degradation in Hooks:** When a Service throws an exception, the Controller listening to the WP Hook must catch it, log it, and return a safe fallback to the user.

```php
try {
    $this->renewalService->process($subscriptionId);
} catch (PaymentFailedException $e) {
    error_log(sprintf('[FuelChef Subs] Renewal failed for %d: %s', $subscriptionId, $e->getMessage()));
    // Set a transient or flash message for the user, do not fatal error the site.
}
```

---

## 17. Performance & Background Processing

- **Transients / Object Cache:** Use the WP Transient API or `wp_cache_*` functions for expensive operations (e.g., remote API calls, complex aggregated queries). Cache invalidation must occur on relevant data saves.
- **Long-Running Tasks:** Any process that loops over more than a few records or hits an external API (e.g., bulk billing, synchronization) **MUST** be offloaded to Action Scheduler (`as_enqueue_async_action`). Never run heavy loops synchronously in a web request.

---

## 18. Frontend & Asset Rules

- **CSS Prefixing:** All custom CSS classes must be prefixed with `fuelchef-` (e.g., `.fuelchef-subscription-card`) to prevent theme collisions.

---

## 19. Static Analysis (PHPStan Strict Enforcement)

PHPStan is our automated gatekeeper. We target **Level 8** with `phpstan-strict-rules` enabled.

### 19.1 Strict PHPDoc Rules
- **Every method/function MUST have a PHPDoc block**, including constructors, even when
  there are no parameters or return value. The block must contain:
  * A short description (a single sentence that serves as the title/summary,
    explaining what the method does).
  * An optional long description (additional detail when the short description is
    insufficient).
  * `@param` annotations for every parameter, each with an inline description.
    The PHPDoc type MUST use the most specific PHPStan type available
    (`positive-int`, `non-empty-string`, `list<T>`, array shapes, etc.). When a
    more specific type is not possible, use the native type as a fallback.
  * `@return` annotation with an inline description, following the same specificity
    rules as `@param`. Use `@return self` for fluent interfaces, `@return void`
    for void methods.
  * `@throws` annotations for every explicitly thrown exception, with an inline
    description of the condition that causes it.
- **Bare arrays are forbidden.** You cannot use `@return array`. You must specify
  the contents: `@return array<string, int>` or use Array Shapes
  `@return array{id: int, status: string}`.
- Do not rely on PHPDoc for runtime execution; it is for static analysis only
  (`treatPhpDocTypesAsCertain: false`).

**Example:**
```php
/**
 * Processes a subscription renewal.
 *
 * Calculates the total amount due, charges the payment gateway, and updates
 * the subscription status. Sends a confirmation email on success.
 *
 * @param positive-int $subscriptionId The subscription to renew.
 * @param non-empty-string $currency   ISO 4217 currency code.
 *
 * @return float The charged amount.
 *
 * @throws PaymentFailedException  If the gateway declines the charge.
 * @throws SubscriptionNotFound    If the subscription ID does not exist.
 */
public function renew(int $subscriptionId, string $currency): float
{
    // ...
}
```

### 19.2 Ignoring Errors
- **Never** suppress a PHPStan error by widening a type (e.g., changing `string` to `mixed`). Fix the underlying logic.
- Ignoring lines is highly discouraged. If absolutely necessary (usually due to a WordPress core anomaly), an ignored line must be accompanied by a comment explaining exactly *why*.

```php
// @phpstan-ignore-next-line WP core intentionally returns string here despite docblock
$status = some_weird_wp_function(); 
```

---

## 20. Standard Precedence & Additional Style Rules

### 20.1 PSR-12 vs WPCS Precedence

PSR-12 is the primary style authority. WPCS is secondary — it applies only
where PSR-12 takes no explicit position. When a WPCS rule contradicts PSR-12,
PSR-12 wins. When PSR-12 is silent, WPCS rules are enforced.

**Yoda Conditions:** PSR-12 does not address Yoda conditions. WPCS enforces
them (`WordPress.PHP.YodaConditions`) via `phpcs.xml.dist`, so they are
followed. Write comparisons with the literal on the left:

```php
if (0 === $count)     // Correct — Yoda form
if ($count === 0)     // Wrong — non-Yoda form
```

### 20.2 Manually Enforced Rules

These rules are checked during code review (not configured in phpcs):

- **No Short Ternary:** The `?:` operator obscures intent, especially with falsy values. Use the full ternary `? :` or the null coalescing operator `??`.
  *Wrong:* `$val = $a ?: $b;`
  *Correct:* `$val = $a !== '' ? $a : $b;` (or `$val = $a ?? $b;`)

---

## 21. Commit Readiness Rule

A pull request or commit must never be submitted if it is "almost working."
Before committing, the code **MUST**:
1. Be logically complete and functional.
2. Leave no dead code, `var_dump`, or `error_log` debugging statements behind.

> **Automated enforcement:** Pre-commit runs `phpcbf` auto-fix; pre-push runs
> `phpcs` + `phpstan` (level 8). The push will be blocked if either fails, so
> always run `./scripts/docker composer lint` before pushing to catch issues
> early.
