<?php
/**
 * api/vehicles/detail.php
 * Single vehicle detail. GET param: id
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response(false, 'Geçersiz araç.', [], 422);
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        json_response(false, 'Araç bulunamadı.', [], 404);
    }

    json_response(true, 'Araç detayı.', ['vehicle' => $vehicle]);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[VEHICLE DETAIL] ' . $e->getMessage());
    json_response(false, 'Araç yüklenirken hata oluştu.', [], 500);
}
