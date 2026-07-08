<?php
/**
 * api/admin/users/purge.php
 * Deletes ALL customers (except admins) together with their
 * rentals/payments. Intended for test/demo data cleanup.
 *
 * POST: csrf_token
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/functions.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_admin();

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    $pdo->exec("DELETE FROM payments WHERE rental_id IN
                (SELECT id FROM rentals WHERE user_id IN (SELECT id FROM users WHERE role='customer'))");
    $pdo->exec("DELETE FROM rentals WHERE user_id IN (SELECT id FROM users WHERE role='customer')");
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'");
    $count = (int) $stmt->fetchColumn();
    $pdo->exec("DELETE FROM users WHERE role='customer'");

    // Free vehicles left dangling in 'Rented' status
    $pdo->exec("UPDATE vehicles SET status='Available'
                WHERE status='Rented'
                  AND id NOT IN (SELECT vehicle_id FROM rentals WHERE status IN ('Pending','Active'))");

    $pdo->commit();

    json_response(true, "$count müşteri ve tüm kayıtları silindi.", ['deleted' => $count]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[ADMIN USER PURGE] ' . $e->getMessage());
    json_response(false, 'Temizlik sırasında hata oluştu.', [], 500);
}
