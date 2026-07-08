<?php
/**
 * api/rentals/list.php
 * Lists the user's rentals. Admins see all rentals.
 * GET (optional): status
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_login();

try {
    $pdo = Database::getConnection();

    $where  = [];
    $params = [];

    // Non-admins only see their own records
    if (!is_admin()) {
        $where[]        = 'r.user_id = :uid';
        $params[':uid'] = (int) $_SESSION['user_id'];
    }

    $status = clean($_GET['status'] ?? '');
    if ($status !== '') {
        $where[]           = 'r.status = :status';
        $params[':status'] = $status;
    }

    $sql = 'SELECT r.id, r.user_id, r.vehicle_id, r.start_date, r.end_date,
                   r.start_km, r.end_km, r.total_price, r.status, r.created_at,
                   u.name_surname, v.brand, v.model,
                   l.name AS location_name
            FROM rentals r
            JOIN users u    ON u.id = r.user_id
            JOIN vehicles v ON v.id = r.vehicle_id
            LEFT JOIN locations l ON l.id = r.pickup_location_id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY r.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(true, 'Kiralamalar listelendi.', [
        'rentals' => $stmt->fetchAll(),
    ]);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[RENTAL LIST] ' . $e->getMessage());
    json_response(false, 'Kiralamalar yüklenirken hata oluştu.', [], 500);
}
