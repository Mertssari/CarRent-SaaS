<?php
/**
 * config/db.php
 * -------------
 * PDO-based database connection layer (Singleton).
 *
 * Usage:
 *   require_once __DIR__ . '/db.php';
 *   $pdo = Database::getConnection();
 *
 *   $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
 *   $stmt->execute([':email' => $email]);
 *   $user = $stmt->fetch();
 *
 * All queries must use prepared statements (SQL injection protection).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Database
{
    /** @var PDO|null Single shared connection instance */
    private static ?PDO $instance = null;

    /** Prevent instantiation (static access only). */
    private function __construct() {}
    private function __clone() {}

    /**
     * Returns a single shared PDO connection (Singleton).
     */
    public static function getConnection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            // Throw exceptions on errors (no silent failures)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Fetch associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use real prepared statements (emulation off)
            PDO::ATTR_EMULATE_PREPARES   => false,
            // No persistent connections (resource management)
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Show details in development; hide them in production.
            if (APP_ENV === 'development') {
                throw new PDOException(
                    'Veritabanı bağlantı hatası: ' . $e->getMessage(),
                    (int) $e->getCode()
                );
            }
            // Production: generic message for the user, log the real error.
            error_log('[DB ERROR] ' . $e->getMessage());
            http_response_code(500);
            exit('Sunucu hatası. Lütfen daha sonra tekrar deneyin.');
        }

        return self::$instance;
    }
}
