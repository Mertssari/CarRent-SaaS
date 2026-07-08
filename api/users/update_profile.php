<?php
/**
 * api/users/update_profile.php
 * Profile update (action=profile) and password change (action=password).
 * The national ID (tc_no) cannot be changed for security reasons.
 *
 * POST (profile):  action=profile, name_surname, email, birth_date, license_date, csrf_token
 * POST (password): action=password, current_password, new_password, new_password_confirm, csrf_token
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_post();
verify_csrf($_POST['csrf_token'] ?? '');
require_login();

$userId = (int) $_SESSION['user_id'];
$action = clean($_POST['action'] ?? '');

$pdo = Database::getConnection();

/* ---------------- PROFILE UPDATE ---------------- */
if ($action === 'profile') {
    $name    = clean($_POST['name_surname'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $birth   = clean($_POST['birth_date'] ?? '');
    $license = clean($_POST['license_date'] ?? '');

    $errors = [];
    if ($name === '' || mb_strlen($name) < 3)          $errors['name_surname'] = 'Ad Soyad en az 3 karakter olmalıdır.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors['email'] = 'Geçerli bir e-posta giriniz.';
    if ($birth === '' || !strtotime($birth))           $errors['birth_date'] = 'Geçerli bir doğum tarihi giriniz.';
    elseif (calculate_age($birth) < 18)                $errors['birth_date'] = 'En az 18 yaşında olmalısınız.';
    if ($license === '' || !strtotime($license))       $errors['license_date'] = 'Geçerli bir ehliyet tarihi giriniz.';
    elseif (strtotime($license) > strtotime('today'))  $errors['license_date'] = 'Ehliyet tarihi gelecekte olamaz.';

    if ($errors) {
        json_response(false, 'Lütfen formdaki hataları düzeltin.', ['errors' => $errors], 422);
    }

    try {
        // Is the email taken by another user?
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = :e AND id <> :id LIMIT 1');
        $chk->execute([':e' => $email, ':id' => $userId]);
        if ($chk->fetch()) {
            json_response(false, 'Bu e-posta başka bir hesapta kayıtlı.', ['errors' => ['email' => 'Bu e-posta kullanımda.']], 409);
        }

        $upd = $pdo->prepare(
            'UPDATE users SET name_surname = :n, email = :e, birth_date = :b, license_date = :l WHERE id = :id'
        );
        $upd->execute([':n' => $name, ':e' => $email, ':b' => $birth, ':l' => $license, ':id' => $userId]);

        // Refresh the session
        $_SESSION['name_surname'] = $name;

        json_response(true, 'Profiliniz güncellendi.', [
            'user' => ['name_surname' => $name, 'email' => $email],
        ]);
    } catch (PDOException $e) {
        if (APP_ENV === 'development') {
            json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
        }
        error_log('[PROFILE UPDATE] ' . $e->getMessage());
        json_response(false, 'Profil güncellenirken hata oluştu.', [], 500);
    }
}

/* ---------------- PASSWORD CHANGE ---------------- */
if ($action === 'password') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new     = (string) ($_POST['new_password'] ?? '');
    $newC    = (string) ($_POST['new_password_confirm'] ?? '');

    $errors = [];
    if ($current === '')                  $errors['current_password'] = 'Mevcut şifrenizi girin.';
    if (mb_strlen($new) < 8)              $errors['new_password'] = 'Yeni şifre en az 8 karakter olmalıdır.';
    elseif ($new !== $newC)               $errors['new_password_confirm'] = 'Yeni şifreler eşleşmiyor.';

    if ($errors) {
        json_response(false, 'Lütfen formdaki hataları düzeltin.', ['errors' => $errors], 422);
    }

    try {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            json_response(false, 'Mevcut şifreniz hatalı.', ['errors' => ['current_password' => 'Mevcut şifre hatalı.']], 403);
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = :p WHERE id = :id')
            ->execute([':p' => $hash, ':id' => $userId]);

        json_response(true, 'Şifreniz başarıyla değiştirildi.');
    } catch (PDOException $e) {
        if (APP_ENV === 'development') {
            json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
        }
        error_log('[PASSWORD CHANGE] ' . $e->getMessage());
        json_response(false, 'Şifre değiştirilirken hata oluştu.', [], 500);
    }
}

json_response(false, 'Geçersiz işlem.', [], 422);
