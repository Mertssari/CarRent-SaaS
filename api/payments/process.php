<?php
/**
 * api/payments/process.php
 * Processes a rental payment. On success, atomically:
 *   create payment (Paid) -> rental 'Active' -> vehicle 'Rented'.
 *
 * POST: rental_id, payment_method ('Credit Card'|'Bank Transfer'), csrf_token
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_login();

$rentalId = (int) ($_POST['rental_id'] ?? 0);
$method   = clean($_POST['payment_method'] ?? '');

if ($rentalId <= 0) {
    json_response(false, 'Geçersiz kiralama.', [], 422);
}
if (!in_array($method, ['Credit Card', 'Bank Transfer'], true)) {
    json_response(false, 'Geçersiz ödeme yöntemi.', [], 422);
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // Lock the rental row
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
        json_response(false, 'Bu kiralama için ödeme yetkiniz yok.', [], 403);
    }

    // Only 'Pending' rentals can be paid
    if ($rental['status'] !== 'Pending') {
        $pdo->rollBack();
        json_response(false, 'Bu kiralama ödemeye uygun değil.', [], 409);
    }

    // Lock the vehicle and make sure it is still rentable
    $vstmt = $pdo->prepare('SELECT status FROM vehicles WHERE id = :vid FOR UPDATE');
    $vstmt->execute([':vid' => (int) $rental['vehicle_id']]);
    $vehicle = $vstmt->fetch();

    if (!$vehicle || $vehicle['status'] === 'Maintenance') {
        $pdo->rollBack();
        json_response(false, 'Araç şu anda müsait değil, ödeme alınamadı.', [], 409);
    }

    // 1) Payment record (Paid)
    $pay = $pdo->prepare(
        'INSERT INTO payments (rental_id, amount, payment_method, payment_status)
         VALUES (:rid, :amount, :method, :status)'
    );
    $pay->execute([
        ':rid'    => $rentalId,
        ':amount' => (float) $rental['total_price'],
        ':method' => $method,
        ':status' => 'Paid',
    ]);
    $paymentId = (int) $pdo->lastInsertId();

    // 2) Rental -> Active
    $pdo->prepare("UPDATE rentals SET status = 'Active' WHERE id = :id")
        ->execute([':id' => $rentalId]);

    // 3) Vehicle -> Rented
    $pdo->prepare("UPDATE vehicles SET status = 'Rented' WHERE id = :vid")
        ->execute([':vid' => (int) $rental['vehicle_id']]);

    $pdo->commit();

    json_response(true, 'Ödeme başarılı, kiralama aktif.', [
        'payment_id' => $paymentId,
        'rental_id'  => $rentalId,
        'amount'     => (float) $rental['total_price'],
    ], 201);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[PAYMENT PROCESS] ' . $e->getMessage());
    json_response(false, 'Ödeme işlenirken hata oluştu.', [], 500);
}
