<?php
/**
 * api/admin/users/delete.php
 * Deletes a single customer together with their rentals/payments (admin).
 * Admin accounts can NOT be deleted through this endpoint.
 *
 * POST: id, csrf_token
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/functions.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_admin();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, 'Geçersiz kullanıcı.', [], 422);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(false, 'Kullanıcı bulunamadı.', [], 404);
    }
    if ($user['role'] === 'admin') {
        json_response(false, 'Admin hesapları silinemez.', [], 403);
    }

    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM payments WHERE rental_id IN (SELECT id FROM rentals WHERE user_id = ?)')->execute([$id]);
    $pdo->prepare('DELETE FROM rentals WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    // Free vehicles left dangling in 'Rented' status
    $pdo->exec("UPDATE vehicles SET status='Available'
                WHERE status='Rented'
                  AND id NOT IN (SELECT vehicle_id FROM rentals WHERE status IN ('Pending','Active'))");
    $pdo->commit();

    json_response(true, 'Müşteri ve kayıtları silindi.', ['id' => $id]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[ADMIN USER DELETE] ' . $e->getMessage());
    json_response(false, 'Silme sırasında hata oluştu.', [], 500);
}
