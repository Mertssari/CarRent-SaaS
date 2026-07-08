<?php
/**
 * api/admin/stats.php
 * Admin dashboard summary: vehicle/customer/rental counts and total revenue.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

try {
    $pdo = Database::getConnection();
    $one = fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();

    $stats = [
        'vehicles_total'   => $one('SELECT COUNT(*) FROM vehicles'),
        'vehicles_rented'  => $one("SELECT COUNT(*) FROM vehicles WHERE status = 'Rented'"),
        'customers'        => $one("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
        'rentals_active'   => $one("SELECT COUNT(*) FROM rentals WHERE status = 'Active'"),
        'rentals_pending'  => $one("SELECT COUNT(*) FROM rentals WHERE status = 'Pending'"),
        'revenue'          => (float) $pdo->query(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status = 'Paid'"
        )->fetchColumn(),
    ];

    json_response(true, 'İstatistikler.', ['stats' => $stats]);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[ADMIN STATS] ' . $e->getMessage());
    json_response(false, 'İstatistikler yüklenemedi.', [], 500);
}
