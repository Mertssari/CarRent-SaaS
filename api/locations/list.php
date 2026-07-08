<?php
/**
 * api/locations/list.php
 * Lists pickup/office locations.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

try {
    $pdo = Database::getConnection();
    $rows = $pdo->query('SELECT id, name FROM locations ORDER BY name ASC')->fetchAll();
    json_response(true, 'Lokasyonlar.', ['locations' => $rows]);
} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[LOCATION LIST] ' . $e->getMessage());
    json_response(false, 'Lokasyonlar yüklenemedi.', [], 500);
}
