<?php
/**
 * api/admin/vehicles/update.php
 * Updates a vehicle (admin). Image is optional: if a new one is uploaded,
 * the old file is removed; otherwise the existing image is kept.
 *
 * POST: id, brand, model, year, type, transmission, fuel_type, current_km,
 *       min_license_age, daily_price, status, csrf_token
 * FILE: image (optional)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/upload.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_admin();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, 'Geçersiz araç.', [], 422);
}

$brand        = clean($_POST['brand'] ?? '');
$model        = clean($_POST['model'] ?? '');
$year         = (int) ($_POST['year'] ?? 0);
$type         = clean($_POST['type'] ?? '');
$transmission = clean($_POST['transmission'] ?? '');
$fuelType     = clean($_POST['fuel_type'] ?? '');
$currentKm    = (int) ($_POST['current_km'] ?? 0);
$minLicense   = (int) ($_POST['min_license_age'] ?? 1);
$dailyPrice   = (float) ($_POST['daily_price'] ?? 0);
$status       = clean($_POST['status'] ?? 'Available');

$errors = [];
if ($brand === '')                                              $errors['brand'] = 'Marka gerekli.';
if ($model === '')                                              $errors['model'] = 'Model gerekli.';
if ($year < 1950 || $year > 2100)                               $errors['year'] = 'Geçerli bir yıl giriniz.';
if (!in_array($type, ['Sedan','SUV','Hatchback'], true))        $errors['type'] = 'Geçersiz tip.';
if (!in_array($transmission, ['Manual','Automatic'], true))     $errors['transmission'] = 'Geçersiz vites.';
if (!in_array($fuelType, ['Gasoline','Diesel','Electric'], true)) $errors['fuel_type'] = 'Geçersiz yakıt.';
if ($dailyPrice <= 0)                                           $errors['daily_price'] = 'Fiyat 0’dan büyük olmalı.';
if (!in_array($status, ['Available','Rented','Maintenance'], true)) $status = 'Available';

if ($errors) {
    json_response(false, 'Formda hatalar var.', ['errors' => $errors], 422);
}

try {
    $pdo = Database::getConnection();

    // Fetch the current record (to keep/replace the existing image)
    $cur = $pdo->prepare('SELECT image_path FROM vehicles WHERE id = :id');
    $cur->execute([':id' => $id]);
    $existing = $cur->fetch();
    if (!$existing) {
        json_response(false, 'Araç bulunamadı.', [], 404);
    }

    // Process the new image if one was uploaded
    $imagePath = $existing['image_path'];
    $oldImage  = null;
    if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $imagePath = save_vehicle_image($_FILES['image']);
            $oldImage  = $existing['image_path']; // removed after the update
        } catch (RuntimeException $e) {
            json_response(false, $e->getMessage(), ['errors' => ['image' => $e->getMessage()]], 422);
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE vehicles SET brand=:brand, model=:model, year=:year, type=:type,
             transmission=:trans, fuel_type=:fuel, current_km=:km, min_license_age=:minlic,
             daily_price=:price, status=:status, image_path=:img
         WHERE id=:id'
    );
    $stmt->execute([
        ':brand'  => $brand, ':model' => $model, ':year' => $year, ':type' => $type,
        ':trans'  => $transmission, ':fuel' => $fuelType, ':km' => $currentKm,
        ':minlic' => $minLicense, ':price' => $dailyPrice, ':status' => $status,
        ':img'    => $imagePath, ':id' => $id,
    ]);

    // Remove the old image from disk
    if ($oldImage) {
        $f = __DIR__ . '/../../../' . $oldImage;
        if (is_file($f)) { @unlink($f); }
    }

    json_response(true, 'Araç güncellendi.', ['vehicle_id' => $id]);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[VEHICLE UPDATE] ' . $e->getMessage());
    json_response(false, 'Araç güncellenirken hata oluştu.', [], 500);
}
