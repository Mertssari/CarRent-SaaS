<?php
/**
 * includes/functions.php
 * ----------------------
 * Shared helper functions used across the backend:
 *  - Session management
 *  - JSON responses / input sanitization
 *  - CSRF protection
 *  - Authorization (auth guards)
 *  - Age / license-age calculation (business rules)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/* -------------------------------------------------------------------------
 * SESSION
 * ---------------------------------------------------------------------- */

/** Starts a secure session (only once). */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,           // not accessible from JS (XSS protection)
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Is the user logged in? */
function is_logged_in(): bool
{
    start_session();
    return isset($_SESSION['user_id']);
}

/** Is the active user an admin? */
function is_admin(): bool
{
    start_session();
    return (($_SESSION['role'] ?? null) === 'admin');
}

/** Writes the user into the session (after login). */
function login_user(array $user): void
{
    start_session();
    // Regenerate the ID to prevent session fixation
    session_regenerate_id(true);
    $_SESSION['user_id']      = (int) $user['id'];
    $_SESSION['name_surname'] = $user['name_surname'];
    $_SESSION['role']         = $user['role'];
}

/** Ends the session. */
function logout_user(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* -------------------------------------------------------------------------
 * JSON RESPONSE & INPUT
 * ---------------------------------------------------------------------- */

/**
 * Sends a JSON response and terminates the script.
 * @param bool  $success Whether the operation succeeded
 * @param string $message Message for the user
 * @param array $data Extra payload
 * @param int   $httpCode HTTP status code
 */
function json_response(bool $success, string $message = '', array $data = [], int $httpCode = 200): void
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Trims string input. Note: data always reaches the DB via prepared statements. */
function clean(?string $value): string
{
    return trim((string) $value);
}

/** Ensures the request is POST; responds with an error otherwise. */
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, 'Geçersiz istek yöntemi.', [], 405);
    }
}

/* -------------------------------------------------------------------------
 * CSRF PROTECTION
 * ---------------------------------------------------------------------- */

/** Generates/returns the CSRF token (used in forms and AJAX requests). */
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Validates the submitted token; responds with an error if invalid. */
function verify_csrf(?string $token): void
{
    start_session();
    if (empty($token) || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $token)) {
        json_response(false, 'Güvenlik doğrulaması başarısız (CSRF).', [], 419);
    }
}

/* -------------------------------------------------------------------------
 * AUTHORIZATION (GUARDS)
 * ---------------------------------------------------------------------- */

/** For endpoints that require a logged-in user. */
function require_login(): void
{
    if (!is_logged_in()) {
        json_response(false, 'Bu işlem için giriş yapmalısınız.', [], 401);
    }
}

/** For endpoints that require an admin. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        json_response(false, 'Bu işlem için yetkiniz yok.', [], 403);
    }
}

/* -------------------------------------------------------------------------
 * BUSINESS RULES: AGE & DRIVING LICENSE
 * ---------------------------------------------------------------------- */

/**
 * Returns the number of whole years elapsed since the given date.
 * (Used for birth date -> age, license date -> license age.)
 */
function years_since(string $date): int
{
    try {
        $then = new DateTime($date);
        $now  = new DateTime('today');
        return (int) $then->diff($now)->y;
    } catch (Exception $e) {
        return 0;
    }
}

/** Returns the user's age. */
function calculate_age(string $birthDate): int
{
    return years_since($birthDate);
}

/** Returns the license age in years. */
function license_years(string $licenseDate): int
{
    return years_since($licenseDate);
}

/**
 * License age check (CRITICAL BUSINESS RULE).
 * Returns false if the user's license age is below the vehicle's min_license_age.
 *
 * @param ?string $licenseDate   users.license_date (may be NULL)
 * @param int     $minLicenseAge vehicles.min_license_age
 */
function is_license_eligible(?string $licenseDate, int $minLicenseAge): bool
{
    if (empty($licenseDate)) {
        return false; // no license info -> renting is not allowed
    }
    return license_years($licenseDate) >= $minLicenseAge;
}
