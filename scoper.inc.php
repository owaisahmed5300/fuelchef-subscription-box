<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

/**
 * Loads a WordPress symbol-exclusion list from sniccowp/php-scoper-wordpress-excludes.
 *
 * The lists are provided on demand by `scripts/scope`, which downloads + verifies
 * them and exports their directory as WP_EXCLUDES_DIR. When that is unset (e.g. a
 * local Composer install of the package), fall back to the vendored copy resolved
 * relative to this file. `scripts/scope` copies scoper.inc.php into `_deps/` before
 * invocation, so `__DIR__ . '/../vendor'` points at the project's top-level
 * `vendor/` directory at runtime.
 */
function getWpExcludedSymbols(string $fileName): array
{
    $excludesDir = getenv("WP_EXCLUDES_DIR");

    if (is_string($excludesDir) && $excludesDir !== "") {
        $filePath = rtrim($excludesDir, "/\\") . "/" . $fileName;
    } else {
        $filePath = __DIR__ . "/../vendor/sniccowp/php-scoper-wordpress-excludes/generated/" . $fileName;
    }

    if (!file_exists($filePath)) {
        return [];
    }

    return json_decode(file_get_contents($filePath), true);
}

$wpConstants = getWpExcludedSymbols("exclude-wordpress-constants.json");
$wpClasses = getWpExcludedSymbols("exclude-wordpress-classes.json");
$wpFunctions = getWpExcludedSymbols("exclude-wordpress-functions.json");

return [
    "prefix" => "FuelChef_Dependencies",

    "finders" => [
        // 1. Scope the source code so we don't have to write prefixed namespaces manually!
        Finder::create()->files()->name("*.php")->in("src"),

        // 2. Scope the production vendor dependencies
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName("/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/")
            ->exclude(["tests", "doc", "test"])
            ->in("vendor"),

        // 3. Include composer.json to ensure autoloaders map correctly
        Finder::create()->append(["composer.json"]),
    ],

    "exclude-files" => [],
    "patchers" => [],

    // Make sure your own namespace is completely ignored by the scoper
    "exclude-namespaces" => ["FuelChefSubsBox", "WP", "Automattic", "WooCommerce"],

    // Exclude global WordPress symbols so they don't get prefixed inside vendor/ packages
    "exclude-classes" => array_merge($wpClasses, ['/^$/']),
    "exclude-functions" => array_merge($wpFunctions, ['/^$/']),
    "exclude-constants" => array_merge($wpConstants, ['/^$/']),

    "expose-global-constants" => false,
    "expose-global-classes" => false,
    "expose-global-functions" => false,
    "expose-namespaces" => [],
    "expose-classes" => [],
    "expose-functions" => [],
    "expose-constants" => [],
];
