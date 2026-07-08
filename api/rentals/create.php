<?php
/**
 * api/rentals/create.php
 * Creates a rental (booking).
 *
 * POST: vehicle_id, pickup_location_id, start_date, end_date, csrf_token
 *
 * Business rules (enforced on the backend):
 *   1) Login required (admins cannot rent)
 *   2) License age >= the vehicle's min_license_age
 *   3) Luxury-class driver validation (age >= 25 and license >= 3 years)
 *   4) No date overlap with Pending/Active rentals
 *   5) total_price = days * daily_price + 20% VAT
 * Race conditions are prevented with a transaction + SELECT ... FOR UPDATE.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_login();

// Business rule: admin accounts cannot rent vehicles
if (is_admin()) {
    json_response(false, 'Yönetici hesabı ile araç kiralanamaz.', [], 403);
}

$userId     = (int) $_SESSION['user_id'];
$vehicleId  = (int) ($_POST['vehicle_id'] ?? 0);
$locationId = (int) ($_POST['pickup_location_id'] ?? 0);
$start      = clean($_POST['start_date'] ?? '');
$end        = clean($_POST['end_date'] ?? '');

// ---- Input validation ----
if ($vehicleId <= 0) {
    json_response(false, 'Geçersiz araç.', [], 422);
}
if ($locationId <= 0) {
    json_response(false, 'Lütfen bir teslim alma lokasyonu seçin.', [], 422);
}
if (!strtotime($start) || !strtotime($end)) {
    json_response(false, 'Geçerli tarihler giriniz.', [], 422);
}
if (strtotime($start) < strtotime('today')) {
    json_response(false, 'Başlangıç tarihi geçmişte olamaz.', [], 422);
}
if (strtotime($end) < strtotime($start)) {
    json_response(false, 'Bitiş tarihi başlangıçtan önce olamaz.', [], 422);
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // Lock the vehicle (prevents concurrent double-booking)
    $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = :id FOR UPDATE');
    $stmt->execute([':id' => $vehicleId]);
    $vehicle = $stmt->fetch();

    if (!$vehicle) {
        $pdo->rollBack();
        json_response(false, 'Araç bulunamadı.', [], 404);
    }
    if ($vehicle['status'] === 'Maintenance') {
        $pdo->rollBack();
        json_response(false, 'Araç şu anda kiralanamaz (bakımda).', [], 409);
    }

    // Is the pickup location valid?
    $lstmt = $pdo->prepare('SELECT id FROM locations WHERE id = :id');
    $lstmt->execute([':id' => $locationId]);
    if (!$lstmt->fetch()) {
        $pdo->rollBack();
        json_response(false, 'Geçersiz teslim alma lokasyonu.', [], 422);
    }

    // User details (age + license)
    $ustmt = $pdo->prepare('SELECT birth_date, license_date FROM users WHERE id = :id LIMIT 1');
    $ustmt->execute([':id' => $userId]);
    $user = $ustmt->fetch();

    // Base license-age check (the vehicle's min_license_age value)
    if (!is_license_eligible($user['license_date'] ?? null, (int) $vehicle['min_license_age'])) {
        $pdo->rollBack();
        json_response(false, sprintf(
            'Bu aracı kiralamak için en az %d yıllık ehliyet gereklidir.',
            (int) $vehicle['min_license_age']
        ), [], 403);
    }

    // ---- DRIVER VALIDATION: luxury-class rule ----
    // Luxury: daily price > 2000 TL OR segment is SUV/Sedan
    // Requirement: age >= 25 and license >= 3 years
    $isLuxury = ((float) $vehicle['daily_price'] > 2000)
        || in_array($vehicle['type'], ['SUV', 'Sedan'], true);

    if ($isLuxury) {
        $age         = empty($user['birth_date']) ? 0 : calculate_age($user['birth_date']);
        $licenseYrs  = empty($user['license_date']) ? 0 : license_years($user['license_date']);
        if ($age < 25 || $licenseYrs < 3) {
            $pdo->rollBack();
            json_response(false,
                'Ehliyet yaşınız bu araç için yetersizdir. Bu sınıf araçlar için en az 25 yaşında ve 3 yıllık ehliyet gereklidir.',
                ['required' => ['min_age' => 25, 'min_license_years' => 3],
                 'yours'    => ['age' => $age, 'license_years' => $licenseYrs]],
                403
            );
        }
    }

    // Date-overlap check (on the locked vehicle)
    $overlap = $pdo->prepare(
        "SELECT COUNT(*) FROM rentals
         WHERE vehicle_id = :vid
           AND status IN ('Pending','Active')
           AND start_date <= :end
           AND end_date   >= :start"
    );
    $overlap->execute([':vid' => $vehicleId, ':start' => $start, ':end' => $end]);
    if ((int) $overlap->fetchColumn() > 0) {
        $pdo->rollBack();
        json_response(false, 'Seçilen tarihlerde araç müsait değil.', [], 409);
    }

    // Price calculation (inclusive day count: +1) + 20% VAT
    $days     = (int) ((strtotime($end) - strtotime($start)) / 86400) + 1;
    $subtotal = $days * (float) $vehicle['daily_price'];
    $vat      = round($subtotal * 0.20, 2);
    $total    = round($subtotal + $vat, 2);   // VAT-inclusive grand total

    // Create the rental (Pending)
    $ins = $pdo->prepare(
        'INSERT INTO rentals (user_id, vehicle_id, pickup_location_id, start_date, end_date, start_km, total_price, status)
         VALUES (:uid, :vid, :loc, :start, :end, :start_km, :total, :status)'
    );
    $ins->execute([
        ':uid'      => $userId,
        ':vid'      => $vehicleId,
        ':loc'      => $locationId,
        ':start'    => $start,
        ':end'      => $end,
        ':start_km' => (int) $vehicle['current_km'],
        ':total'    => $total,
        ':status'   => 'Pending',
    ]);
    $rentalId = (int) $pdo->lastInsertId();

    $pdo->commit();

    json_response(true, 'Rezervasyon oluşturuldu (ödeme bekleniyor).', [
        'rental_id'   => $rentalId,
        'days'        => $days,
        'subtotal'    => $subtotal,
        'vat'         => $vat,
        'total_price' => $total,
    ], 201);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[RENTAL CREATE] ' . $e->getMessage());
    json_response(false, 'Rezervasyon oluşturulurken hata oluştu.', [], 500);
}
