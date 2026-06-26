# PHPStan Static Analysis: The Definitive Guide

This document is the authoritative reference for PHPStan usage within the **FuelChef Subscription Box** plugin. It dictates how we bridge the gap between WordPress's loosely-typed, legacy procedural code and our strict, enterprise-grade OOP architecture.

For a high-level summary of coding standards, refer to `standards.md` (Sections 9 and 19).

---

## 1. Core Philosophy & Configuration

Static analysis is not just a linter; it is our primary defense against runtime fatal errors, type coercion bugs, and unhandled edge cases. We do not write code to "make the red squiggles go away"—we write code to guarantee architectural integrity.

### 1.1 Project Configuration (`phpstan.neon.dist`)
- **Level 8:** The strictest built-in level. All method calls, property accesses, and return values must be verifiably type-safe.
- **`phpstan-strict-rules`:** Enabled. Enforces strict booleans (`if ($count > 0)` instead of `if ($count)`), bans variable variables, and ensures `switch` exhaustiveness.
- **`phpstan-wordpress` & WooCommerce Stubs:** Bootstrapped to provide baseline signatures for core APIs.
- **`treatPhpDocTypesAsCertain: false`:** *Crucial setting.* This means PHPStan will use your docblocks for inference, but will **NOT** assume they guarantee runtime safety. You must still write runtime guard clauses (e.g., `if (is_array($val))`) when dealing with external boundaries.

---

## 2. Native Types vs. PHPDoc Types

**Rule:** Native PHP 8.1+ types always take precedence. 
You should *only* use PHPDoc to express constraints that PHP's native engine cannot enforce (generics, array shapes, value ranges). **Do not duplicate native types in PHPDoc.**

```php
// WRONG: Redundant noise
/**
 * @param int $id
 * @return string
 */
public function getName(int $id): string { ... }

// CORRECT: PHPDoc adds missing contextual detail
/**
 * A short title.
 *
 * A 1-3 line short description.
 *
 * [Optional: longer description]
 *
 * @param positive-int $id User ID.
 * @return non-empty-string User display name.
 */
public function getName(int $id): string { ... }
```

---

## 3. The PHPDoc Type System

When native types fall short, use these specific PHPStan types to strictly define data structures.

### 3.1 Collections (`list<T>` vs `array<K, V>`)
Never use the bare `array` keyword in a docblock. You must always define the contents.

- **`list<T>`:** Use for sequential, 0-indexed arrays (e.g., `[ 'apple', 'banana' ]`).
- **`array<K, V>`:** Use for associative arrays where keys have meaning.

```php
/** @return list<WC_Product> */
public function getActiveProducts(): array;

/** @return array<string, WC_Product> */
public function getProductsMappedBySku(): array;
```

### 3.2 Array Shapes
Use array shapes when the structure is predictable, such as `$wpdb` results or JSON payloads. *Note: If a shape is passed around multiple methods, upgrade it to a typed DTO class.*

```php
/** @var array{id: int, name: string, price: float} $productRow */
$productRow = $wpdb->get_row("SELECT id, name, price FROM ...", ARRAY_A);

// Optional keys use a question mark:
/** @var array{id: int, user_email?: string} $userData */
```

### 3.3 Numeric & String Constraints
Encode business logic directly into types to prevent impossible states.

| Type | Meaning | Example Use Case |
| :--- | :--- | :--- |
| `positive-int` | Integer strictly > 0 | Database IDs, Quantities |
| `int<0, 100>` | Integer between bounds | Percentages, Discounts |
| `non-empty-string`| String with length >= 1 | Names, Slugs, API Keys |
| `numeric-string` | String holding a number | WooCommerce price strings |

### 3.4 Class-Strings & Objects
When dynamically instantiating classes or passing class names.

```php
/** @param class-string<WC_Product> $productClass */
public function instantiateProduct(string $productClass): WC_Product
{
    return new $productClass(); 
}
```

---

## 4. Conquering `mixed` (WordPress Boundaries)

WordPress and WooCommerce functions notoriously return `mixed` or union types involving `false`. **`mixed` is a virus.** If it enters your business logic, type safety dies.

You must neutralize `mixed` at the exact boundary where it is retrieved.

### Option A: The DTO Hydrator (Preferred for Database/API)
When fetching complex rows, immediately pass the raw data into a DTO factory that casts every single value.

```php
/** @var array<string, mixed>|null $row */
$row = $wpdb->get_row($query, ARRAY_A);

if (!is_array($row)) {
    throw new NotFoundException();
}

return SubscriptionPlan::fromArray($row); // DTO internalizes casting
```

### Option B: Typed Value Helpers (Preferred for Options/Meta)
Create wrappers for scalar WordPress values.

```php
// src/Helpers/Value.php
final class Value
{
    public static function int(mixed $value, int $default = 0): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }
}

// Usage
$threshold = Value::int(get_option('fuelchef_retry_limit', 3));
```

### Option C: Inline Guard Clauses
Narrow the type dynamically. PHPStan understands native PHP assertions.

```php
$status = get_post_meta($id, '_status', true);

if (!is_string($status)) {
    return 'default';
}

// PHPStan now knows $status is guaranteed string.
```

---

## 5. Inline `@var` Annotations

When PHPStan cannot infer a type (usually because a WordPress core function signature is vague), use an inline `@var`.

**Strict Rules for `@var`:**
1. Place it immediately on the line *above* the assignment.
2. Use the narrowest possible type.
3. **Never** use `mixed` in a `@var`. (`array<array-key, mixed>` is allowed only if absolutely unavoidable).

```php
// CORRECT
/** @var list<int> $userIds */
$userIds = get_users(['fields' => 'ID']);

// WRONG - Hides the problem
/** @var array $userIds */
$userIds = get_users(['fields' => 'ID']); 
```

---

## 6. Generics (`@template`)

PHPStan brings full generics to PHP via docblocks. Use them when a class, collection, or method operates on varying types but requires the input and output types to match.

### 6.1 Method-Level Generics
Useful for identity functions or dynamic resolvers.

```php
/**
 * @template T
 * @param T $item
 * @return T
 */
public function passthrough(mixed $item): mixed
{
    // If you pass an int, PHPStan knows it returns an int.
    return $item;
}
```

### 6.2 Constrained Generics
Restrict the generic to a specific parent class or interface.

```php
/**
 * @template T of WC_Product
 * @param class-string<T> $class
 * @return list<T>
 */
public function findAllByClass(string $class): array
```

### 6.3 Class-Level Generics (Wrappers / Results)
Ideal for the Result pattern or paginated collections.

```php
/**
 * @template TValue
 */
final readonly class Result
{
    /**
     * @param TValue|null $value
     */
    private function __construct(
        public mixed $value,
        public bool $success
    ) {}

    /**
     * @template T
     * @param T $value
     * @return self<T>
     */
    public static function ok(mixed $value): self
    {
        return new self($value, true);
    }
}

$result = Result::ok(new SubscriptionPlan(...)); 
// PHPStan understands $result->value is a SubscriptionPlan.
```

---

## 7. The `@phpstan-ignore` Discipline

Ignoring an error is a **last resort**. It is an admission that our types or architectural design failed, or that WordPress core is inherently un-typeable in a specific scenario.

### The Golden Rule
**Every `@phpstan-ignore-next-line` MUST be accompanied by a comment explaining EXACTLY why the rule is being bypassed.**

```php
// WRONG: Lazy suppression
// @phpstan-ignore-next-line
$value = apply_filters('some_filter', $original);

// CORRECT: Justified suppression
/** @phpstan-ignore-next-line — WP filter intentionally alters type, verified via validation below */
$value = apply_filters('some_filter', $original);
if (!is_string($value)) { ... }
```

Do **not** suppress an error by widening a type (e.g., changing `string` to `string|mixed`). Fix the underlying logic or write a proper guard clause.

---

## 8. Common Patterns & Pitfalls

### `void` vs `never`
- Use `@return void` (native `void`) when a method executes successfully but returns no value (side-effects only).
- Use `@return never` (native `never`) when a method will *never* complete its execution (e.g., it always throws an Exception, or calls `wp_die()`, `exit`).

### The Union Trap (`|false`)
Many WP APIs return `false` on failure (e.g., `get_option()`).
If you type-hint `$data = get_option(...)` and pass `$data` to a method expecting a `string`, PHPStan will flag a `string|false` error.
**Do not ignore this.** You must explicitly handle the `false` state.

```php
$data = get_option('my_key');
if ($data === false) {
    throw new RuntimeException('Option missing');
}
// Safely pass $data as string
```
