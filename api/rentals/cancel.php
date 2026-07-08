<?php
/**
 * api/rentals/cancel.php
 * Cancels a rental. The owner or an admin may cancel.
 * Only 'Pending' or 'Active' rentals can be cancelled.
 *
 * Business rule (customer): cancellation is allowed only up to 24 hours
 * before the rental start (pickup time 10:00). Within the final 24 hours
 * cancellation is closed. Admins are exempt (operational flexibility).
 *
 * POST: rental_id, csrf_token
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_login();

$rentalId = (int) ($_POST['rental_id'] ?? 0);
if ($rentalId <= 0) {
    json_response(false, 'Geçersiz kiralama.', [], 422);
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM rentals WHERE id = :id FOR UPDATE');
    $stmt->execute([':id' => $rentalId]);
    $rental = $stmt->fetch();

    if (!$rental) {
        $pdo->rollBack();
        json_response(false, 'Kiralama bulunamadı.', [], 404);
    }

    // Authorization: owner or admin
    if (!is_admin() && (int) $rental['user_id'] !== (int) $_SESSION['user_id']) {
        $pdo->rollBack();
        json_response(false, 'Bu kiralamayı iptal etme yetkiniz yok.', [], 403);
    }

    if (!in_array($rental['status'], ['Pending', 'Active'], true)) {
        $pdo->rollBack();
        json_response(false, 'Bu kiralama iptal edilemez.', [], 409);
    }

    // ---- 24-HOUR RULE (customer) ----
    // Pickup moment: start_date 10:00. No cancellation within 24h of it.
    if (!is_admin()) {
        $pickupAt = new DateTime($rental['start_date'] . ' 10:00:00');
        $deadline = (clone $pickupAt)->modify('-24 hours');
        $now      = new DateTime('now');

        if ($now > $deadline) {
            $pdo->rollBack();
            json_response(false,
                'İptal süresi doldu: rezervasyonlar alış saatine (' .
                $pickupAt->format('d.m.Y H:i') . ') 24 saatten az kala iptal edilemez.',
                ['pickup_at' => $pickupAt->format('Y-m-d H:i'),
                 'cancel_deadline' => $deadline->format('Y-m-d H:i')],
                409
            );
        }
    }

    // Cancel
    $upd = $pdo->prepare("UPDATE rentals SET status = 'Cancelled' WHERE id = :id");
    $upd->execute([':id' => $rentalId]);

    // If the vehicle was 'Rented', make it 'Available' again
    $freeCar = $pdo->prepare(
        "UPDATE vehicles SET status = 'Available' WHERE id = :vid AND status = 'Rented'"
    );
    $freeCar->execute([':vid' => (int) $rental['vehicle_id']]);

    $pdo->commit();

    json_response(true, 'Kiralama iptal edildi.', ['rental_id' => $rentalId]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[RENTAL CANCEL] ' . $e->getMessage());
    json_response(false, 'İptal sırasında hata oluştu.', [], 500);
}
