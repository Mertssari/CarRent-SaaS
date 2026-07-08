<?php
/**
 * api/auth/logout.php
 * -------------------
 * Ends the session. Can be called via GET or POST.
 * Returns JSON for AJAX requests; otherwise redirects to the home page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

logout_user();

// Is this an AJAX request? (detected via the X-Requested-With header)
$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

if ($isAjax) {
    json_response(true, 'Çıkış yapıldı.', ['redirect' => BASE_URL . '/index.php']);
}

header('Location: ' . BASE_URL . '/index.php');
exit;
