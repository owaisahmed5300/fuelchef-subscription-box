<?php

/**
 * PHP CS Fixer configuration for the scoped release artifact.
 *
 * php-scoper reprints every file through nikic/php-parser's pretty-printer,
 * which normalises away ALL blank lines and leaves the scoped src dense and
 * unreadable. This config runs afterwards purely to restore blank-line
 * readability — PHPCBF/PSR-12 cannot *add* blank lines before statements, so it
 * cannot do this on its own.
 *
 * The rule set is intentionally narrow: @PSR12 plus a handful of blank-line
 * fixers. It deliberately avoids @Symfony, which would remove "unused" imports
 * (no_unused_imports) — unsafe on machine-generated scoped code — and rewrite
 * docblocks. Risky fixers are disabled so quote style and Yoda conditions are
 * left untouched; PHPCBF (WPCS) owns those afterwards.
 *
 * Pipeline order in scripts/scope: php-scoper -> THIS -> phpcbf ->
 * scripts/normalize-header.php. The header hug (<?php immediately followed by
 * the file docblock) is applied by normalize-header.php as the final step,
 * because no php-cs-fixer rule can express it.
 */

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// Default Finder target. scripts/scope overrides this by passing the scoped
// build dir as a positional path (--path-mode=override), because env vars are
// not forwarded into the Docker container that runs the fixer. PHP_CS_FIXER_TARGET
// remains an optional convenience for in-process/manual runs; the fallback is the
// hand-written src/ so a bare `php-cs-fixer fix` still has a valid directory.
$target = getenv('PHP_CS_FIXER_TARGET');
$target = is_string($target) && $target !== '' ? $target : __DIR__ . '/src';

$finder = Finder::create()
    ->in($target)
    ->name('*.php');

return (new Config())
    ->setRiskyAllowed(false)
    ->setUsingCache(false)
    ->setLineEnding("\n")
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,

        // Do not force a blank line after the opening tag. The file docblock
        // must hug <?php (see normalize-header.php); this only stops the fixer
        // from re-inserting the gap.
        'blank_line_after_opening_tag' => false,

        // One blank line between class members (php-scoper removes them all).
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
                'case' => 'none',
            ],
        ],

        // Restore the blank line before return statements that the source uses.
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],

        // A docblock must sit directly above its target, no blank line between.
        'no_blank_lines_after_phpdoc' => true,

        // Collapse 2+ consecutive blank lines to one; strip trailing whitespace
        // from blank lines; end files with exactly one newline.
        'no_extra_blank_lines' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
    ]);
