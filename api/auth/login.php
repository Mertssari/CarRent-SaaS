<?php
/**
 * api/auth/login.php
 * ------------------
 * User login (AJAX -> JSON).
 *
 * Expected POST fields:
 *   email, password, csrf_token
 *
 * Security:
 *   - Hash verification via password_verify()
 *   - Same generic message whether the user exists or not (prevents user enumeration)
 *   - session_regenerate_id on successful login
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_post();

$token = $_POST['csrf_token'] ?? '';
verify_csrf($token);

$email    = clean($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

// ---- Basic validation ----
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    json_response(false, 'E-posta veya parola hatalı.', [], 401);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('SELECT id, name_surname, email, password, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // User missing OR wrong password -> the same generic message
    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'E-posta veya parola hatalı.', [], 401);
    }

    // (Optional) rehash if the hashing algorithm has been upgraded
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
        $upd->execute([':p' => $newHash, ':id' => $user['id']]);
    }

    // Start the session
    login_user($user);

    json_response(true, 'Giriş başarılı.', [
        'user' => [
            'id'           => (int) $user['id'],
            'name_surname' => $user['name_surname'],
            'role'         => $user['role'],
        ],
        // Role-based redirect: admin -> panel, customer -> vehicle showcase
        'redirect' => ($user['role'] === 'admin') ? 'admin/dashboard.php' : 'index.php',
    ]);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[LOGIN ERROR] ' . $e->getMessage());
    json_response(false, 'Giriş sırasında bir hata oluştu.', [], 500);
}
