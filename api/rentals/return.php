<?php
/**
 * api/rentals/return.php
 * Vehicle return / rental completion (admin).
 * end_km is recorded -> rental 'Completed', vehicle current_km updated & 'Available'.
 *
 * POST: rental_id, end_km, csrf_token
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_admin();

$rentalId = (int) ($_POST['rental_id'] ?? 0);
$endKm    = (int) ($_POST['end_km'] ?? -1);

if ($rentalId <= 0) {
    json_response(false, 'Geçersiz kiralama.', [], 422);
}
if ($endKm < 0) {
    json_response(false, 'Geçerli bir teslim KM giriniz.', [], 422);
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
    if ($rental['status'] !== 'Active') {
        $pdo->rollBack();
        json_response(false, 'Yalnızca aktif kiralamalar iade edilebilir.', [], 409);
    }
    if ($endKm < (int) $rental['start_km']) {
        $pdo->rollBack();
        json_response(false, sprintf('Teslim KM, çıkış KM’sinden (%d) küçük olamaz.', (int) $rental['start_km']), [], 422);
    }

    // Complete the rental
    $pdo->prepare("UPDATE rentals SET end_km = :km, status = 'Completed' WHERE id = :id")
        ->execute([':km' => $endKm, ':id' => $rentalId]);

    // Update the vehicle: KM + available again (unless in maintenance)
    $pdo->prepare("UPDATE vehicles SET current_km = :km,
                   status = CASE WHEN status = 'Maintenance' THEN status ELSE 'Available' END
                   WHERE id = :vid")
        ->execute([':km' => $endKm, ':vid' => (int) $rental['vehicle_id']]);

    $pdo->commit();

    json_response(true, 'Araç iade alındı, kiralama tamamlandı.', [
        'rental_id' => $rentalId,
        'end_km'    => $endKm,
        'distance'  => $endKm - (int) $rental['start_km'],
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[RENTAL RETURN] ' . $e->getMessage());
    json_response(false, 'İade sırasında hata oluştu.', [], 500);
}
