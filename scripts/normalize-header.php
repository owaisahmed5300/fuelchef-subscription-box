<?php

/**
 * Normalises the file header of scoped PHP files.
 *
 * This is the final formatting step in scripts/scope, run after php-cs-fixer
 * and phpcbf. It enforces the one header convention neither tool can express:
 *
 *   1. The file docblock hugs the opening tag — `<?php` is immediately followed
 *      by `/**`, with no blank line between (php-cs-fixer always wants a blank
 *      line there; PHPCBF leaves it alone).
 *   2. Exactly one blank line separates the file docblock from the code that
 *      follows it (WPCS Squiz.Commenting.FileComment.SpacingAfterComment, which
 *      PHPCBF cannot auto-fix).
 *
 * Files that do not open with a docblock (e.g. those starting with `declare`)
 * are left untouched, so it is safe to run across an entire tree.
 *
 * Usage: php scripts/normalize-header.php <directory>
 */

declare(strict_types=1);

$target = $argv[1] ?? '';

if ($target === '' || ! is_dir($target)) {
    fwrite(STDERR, "normalize-header: target directory missing or not a directory\n");
    exit(0);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $code = file_get_contents($path);

    if ($code === false) {
        continue;
    }

    $original = $code;

    // 1. Remove blank line(s) between the opening tag and a leading docblock.
    $code = preg_replace(
        '/\A<\?php\h*\R(?:\h*\R)+(\/\*\*)/',
        "<?php\n$1",
        $code,
        1
    );

    // 2. Ensure exactly one blank line after that leading file docblock.
    $code = preg_replace(
        '/\A(<\?php\R\/\*\*.*?\*\/)\R(?:\h*\R)*(?=\S)/s',
        "$1\n\n",
        $code,
        1
    );

    if ($code !== $original) {
        file_put_contents($path, $code);
    }
}
