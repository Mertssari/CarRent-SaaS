<?php
/**
 * api/admin/vehicles/create.php
 * Adds a new vehicle (admin). multipart/form-data + optional image upload.
 *
 * POST: brand, model, year, type, transmission, fuel_type, current_km,
 *       min_license_age, daily_price, status, csrf_token
 * FILE: image (optional)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/upload.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_admin();

// ---- Input ----
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

// ---- Validation (strict to the ENUMs) ----
$errors = [];
if ($brand === '')                                              $errors['brand'] = 'Marka gerekli.';
if ($model === '')                                              $errors['model'] = 'Model gerekli.';
if ($year < 1950 || $year > 2100)                               $errors['year'] = 'Geçerli bir yıl giriniz.';
if (!in_array($type, ['Sedan','SUV','Hatchback'], true))        $errors['type'] = 'Geçersiz tip.';
if (!in_array($transmission, ['Manual','Automatic'], true))     $errors['transmission'] = 'Geçersiz vites.';
if (!in_array($fuelType, ['Gasoline','Diesel','Electric'], true)) $errors['fuel_type'] = 'Geçersiz yakıt.';
if ($dailyPrice <= 0)                                           $errors['daily_price'] = 'Fiyat 0’dan büyük olmalı.';
if ($minLicense < 0)                                            $errors['min_license_age'] = 'Geçersiz değer.';
if (!in_array($status, ['Available','Rented','Maintenance'], true)) $status = 'Available';

if ($errors) {
    json_response(false, 'Formda hatalar var.', ['errors' => $errors], 422);
}

// ---- Image (optional) ----
$imagePath = null;
if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    try {
        $imagePath = save_vehicle_image($_FILES['image']);
    } catch (RuntimeException $e) {
        json_response(false, $e->getMessage(), ['errors' => ['image' => $e->getMessage()]], 422);
    }
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO vehicles
            (brand, model, year, type, transmission, fuel_type, current_km,
             min_license_age, daily_price, status, image_path)
         VALUES
            (:brand, :model, :year, :type, :trans, :fuel, :km,
             :minlic, :price, :status, :img)'
    );
    $stmt->execute([
        ':brand'  => $brand,
        ':model'  => $model,
        ':year'   => $year,
        ':type'   => $type,
        ':trans'  => $transmission,
        ':fuel'   => $fuelType,
        ':km'     => $currentKm,
        ':minlic' => $minLicense,
        ':price'  => $dailyPrice,
        ':status' => $status,
        ':img'    => $imagePath,
    ]);

    json_response(true, 'Araç eklendi.', ['vehicle_id' => (int) $pdo->lastInsertId()], 201);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[VEHICLE CREATE] ' . $e->getMessage());
    json_response(false, 'Araç eklenirken hata oluştu.', [], 500);
}
