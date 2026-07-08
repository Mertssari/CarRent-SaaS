<?php
/**
 * config/config.php
 * -----------------
 * Application settings and database credentials.
 *
 * NOTE: In production, read these values from environment variables
 * (getenv) or a .env file excluded from version control. The defaults
 * below are for local development (XAMPP/WAMP).
 */

declare(strict_types=1);

// --- Environment (development | production) ---
define('APP_ENV', 'development');

// --- Database settings ---
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'car_rental');
define('DB_USER', 'root');
define('DB_PASS', '');          // empty by default on XAMPP
define('DB_CHARSET', 'utf8mb4');

// --- Application settings ---
define('APP_NAME', 'CarRent — Akıllı Araç Kiralama');

// Root path the app is served from (subfolder deployments).
// Use '/CarRent' when under htdocs/CarRent on XAMPP; use '' at web root.
define('BASE_URL', '/CarRent');

// --- Error display (per environment) ---
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// --- Timezone ---
date_default_timezone_set('Europe/Istanbul');
