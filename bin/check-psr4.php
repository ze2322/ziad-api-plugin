<?php
/**
 * Verifies that each class file sits in the directory its namespace implies.
 *
 * A directory whose case differs from its namespace (src/Cli holding
 * Ziad\APIPlugin\CLI, for example) resolves fine on Windows and macOS but not
 * on the case-sensitive filesystems most WordPress hosts run, where the
 * autoloader silently fails to find the class.
 *
 * Usage: php bin/check-psr4.php
 */

$prefix  = 'Ziad\APIPlugin';
$baseDir = __DIR__ . '/../src';

$failures = 0;

$files = new RegexIterator(
    new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
    ),
    '/\.php$/'
);

foreach ($files as $file) {
    $path     = str_replace('\', '/', $file->getPathname());
    $contents = file_get_contents($path);

    if (!preg_match('/^namespace\s+([^;]+);/m', $contents, $matches)) {
        continue;
    }

    $namespace = trim($matches[1]);

    if (strpos($namespace, $prefix) !== 0) {
        fwrite(STDERR, "FAIL {$path}: namespace {$namespace} is outside {$prefix}\n");
        $failures++;
        continue;
    }

    $relative = trim(substr($namespace, strlen($prefix)), '\');
    $expected = rtrim(str_replace('\', '/', $baseDir . '/' . $relative), '/');
    $actual   = dirname($path);

    // Compare the resolved real paths so ../ segments do not cause false hits,
    // then compare the literal strings to catch case differences that the
    // filesystem itself would happily ignore.
    if (realpath($expected) !== realpath($actual) || basename($expected) !== basename($actual)) {
        fwrite(STDERR, "FAIL {$path}: namespace {$namespace} expects {$expected}\n");
        $failures++;
        continue;
    }

    echo "ok   {$path} ({$namespace})\n";
}

if ($failures > 0) {
    fwrite(STDERR, "\n{$failures} file(s) in a directory that does not match their namespace.\n");
    exit(1);
}

echo "\nAll class files match their namespace.\n";
