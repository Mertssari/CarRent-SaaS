<?php
/**
 * api/admin/vehicles/delete.php
 * Deletes a vehicle (admin). Vehicles with rental history cannot be
 * deleted (FK RESTRICT).
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
    json_response(false, 'Geçersiz araç.', [], 422);
}

try {
    $pdo = Database::getConnection();

    // Fetch the image path (to clean up the file as well)
    $stmt = $pdo->prepare('SELECT image_path FROM vehicles WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(false, 'Araç bulunamadı.', [], 404);
    }

    $pdo->prepare('DELETE FROM vehicles WHERE id = :id')->execute([':id' => $id]);

    // Remove the image from disk
    if (!empty($row['image_path'])) {
        $file = __DIR__ . '/../../../' . $row['image_path'];
        if (is_file($file)) {
            @unlink($file);
        }
    }

    json_response(true, 'Araç silindi.', ['id' => $id]);

} catch (PDOException $e) {
    // FK RESTRICT -> the vehicle has rental records
    if ($e->getCode() === '23000') {
        json_response(false, 'Bu aracın kiralama kaydı olduğu için silinemez.', [], 409);
    }
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[VEHICLE DELETE] ' . $e->getMessage());
    json_response(false, 'Araç silinirken hata oluştu.', [], 500);
}
