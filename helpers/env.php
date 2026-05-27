<?php

/**
 * helpers/env.php
 *
 * Utility to load a .env file into $_ENV and putenv().
 */

/**
 * Parse and register environment variables from a .env file.
 *
 * Rules:
 *  - Lines starting with # are treated as comments and skipped.
 *  - Empty / blank lines are skipped.
 *  - Expected format: KEY=VALUE (no surrounding quotes required).
 *
 * @param string $path Absolute path to the .env file.
 * @throws Exception If the file cannot be found or read.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new Exception("Environment file not found: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        throw new Exception("Failed to read environment file: {$path}");
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split on the first '=' only so values can contain '='
        $parts = explode('=', $line, 2);

        if (count($parts) !== 2) {
            continue; // Malformed line — skip silently
        }

        [$key, $value] = $parts;
        $key   = trim($key);
        $value = trim($value);

        // Register in both superglobal and process environment
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}
