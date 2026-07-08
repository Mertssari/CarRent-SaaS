<?php
/**
 * api/admin/users/list.php
 * Lists customers (admin), including their rental counts.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/functions.php';
require_admin();

try {
    $pdo = Database::getConnection();
    $rows = $pdo->query(
        "SELECT u.id, u.name_surname, u.email, u.tc_no, u.birth_date, u.license_date, u.created_at,
                COUNT(r.id) AS rental_count,
                SUM(CASE WHEN r.status IN ('Pending','Active') THEN 1 ELSE 0 END) AS open_rentals
         FROM users u
         LEFT JOIN rentals r ON r.user_id = u.id
         WHERE u.role = 'customer'
         GROUP BY u.id
         ORDER BY u.created_at DESC"
    )->fetchAll();

    json_response(true, 'Müşteriler listelendi.', ['customers' => $rows]);
} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[ADMIN USER LIST] ' . $e->getMessage());
    json_response(false, 'Müşteriler yüklenemedi.', [], 500);
}
