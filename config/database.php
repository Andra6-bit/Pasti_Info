<?php

/**
 * config/database.php
 *
 * Establishes the MySQLi database connection using values from .env.
 * The connection is stored in $koneksi for use across the application.
 */

// Load environment helper
require_once __DIR__ . '/../helpers/env.php';

// Load .env from project root
loadEnv(__DIR__ . '/../.env');

// ─── Connection ────────────────────────────────────────────────────────────────
$koneksi = mysqli_connect(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    !empty($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'db_pinfo'
);

// ─── Connection guard ─────────────────────────────────────────────────────────
if (!$koneksi) {
    // Do NOT expose credentials or mysqli_connect_error() in production output
    error_log('Database connection failed: ' . mysqli_connect_error());
    die('A database error occurred. Please try again later.');
}

// Enforce UTF-8 throughout
mysqli_set_charset($koneksi, 'utf8mb4');