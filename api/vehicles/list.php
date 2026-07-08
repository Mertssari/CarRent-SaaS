<?php
/**
 * api/vehicles/list.php
 * Vehicle listing. When dates are provided, returns only vehicles
 * without a date conflict (available for the selected range).
 * GET params (optional): start_date, end_date, type, transmission, fuel_type, sort
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

try {
    $pdo = Database::getConnection();

    $start = clean($_GET['start_date'] ?? '');
    $end   = clean($_GET['end_date'] ?? '');

    $where  = ["v.status <> 'Maintenance'"];
    $params = [];

    // Simple filters
    foreach (['type', 'transmission', 'fuel_type'] as $f) {
        $val = clean($_GET[$f] ?? '');
        if ($val !== '') {
            $where[]     = "v.$f = :$f";
            $params[":$f"] = $val;
        }
    }

    // Apply the overlap check when a date range is provided
    $dateFilter = false;
    if ($start !== '' && $end !== '') {
        if (!strtotime($start) || !strtotime($end) || strtotime($end) < strtotime($start)) {
            json_response(false, 'Geçersiz tarih aralığı.', [], 422);
        }
        $dateFilter = true;
        // Exclude vehicles with a Pending/Active rental in the selected range
        $where[] = "v.id NOT IN (
            SELECT r.vehicle_id FROM rentals r
            WHERE r.status IN ('Pending','Active')
              AND r.start_date <= :end
              AND r.end_date   >= :start
        )";
        $params[':start'] = $start;
        $params[':end']   = $end;
    }

    // Sorting (whitelisted for safety)
    $sortMap = [
        'price_asc'  => 'v.daily_price ASC',
        'price_desc' => 'v.daily_price DESC',
        'year_desc'  => 'v.year DESC',
    ];
    $orderBy = $sortMap[clean($_GET['sort'] ?? '')] ?? 'v.daily_price ASC';

    $sql = 'SELECT v.id, v.brand, v.model, v.year, v.type, v.transmission, v.fuel_type,
                   v.current_km, v.min_license_age, v.daily_price, v.status, v.image_path
            FROM vehicles v
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $orderBy;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();

    json_response(true, 'Araçlar listelendi.', [
        'vehicles'    => $vehicles,
        'date_filter' => $dateFilter,
        'count'       => count($vehicles),
    ]);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[VEHICLE LIST] ' . $e->getMessage());
    json_response(false, 'Araçlar yüklenirken hata oluştu.', [], 500);
}
