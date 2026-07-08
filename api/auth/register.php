<?php
/**
 * api/auth/register.php
 * ---------------------
 * New customer registration (AJAX -> JSON).
 *
 * Expected POST fields:
 *   name_surname, email, tc_no, password, password_confirm,
 *   birth_date, license_date, csrf_token
 *
 * Business rules:
 *   - Email must be unique
 *   - National ID (tc_no) must be 11 digits and unique
 *   - Minimum password strength (>= 8 chars) and confirmation must match
 *   - User must be at least 18 years old
 *   - License issue date is required and cannot be in the future
 *   - Password is hashed with password_hash()
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';

require_post();

$token = $_POST['csrf_token'] ?? '';
verify_csrf($token);

// ---- Collect & sanitize input ----
$nameSurname = clean($_POST['name_surname'] ?? '');
$email       = clean($_POST['email'] ?? '');
$tcNo        = clean($_POST['tc_no'] ?? '');
$password    = (string) ($_POST['password'] ?? '');
$passwordC   = (string) ($_POST['password_confirm'] ?? '');
$birthDate   = clean($_POST['birth_date'] ?? '');
$licenseDate = clean($_POST['license_date'] ?? '');

// ---- Validation ----
$errors = [];

if ($nameSurname === '' || mb_strlen($nameSurname) < 3) {
    $errors['name_surname'] = 'Ad Soyad en az 3 karakter olmalıdır.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Geçerli bir e-posta adresi giriniz.';
}

if (mb_strlen($password) < 8) {
    $errors['password'] = 'Parola en az 8 karakter olmalıdır.';
} elseif ($password !== $passwordC) {
    $errors['password_confirm'] = 'Parolalar eşleşmiyor.';
}

// Birth date & age check
if ($birthDate === '' || !strtotime($birthDate)) {
    $errors['birth_date'] = 'Geçerli bir doğum tarihi giriniz.';
} elseif (calculate_age($birthDate) < 18) {
    $errors['birth_date'] = 'Kayıt için en az 18 yaşında olmalısınız.';
}

// National ID: required, 11 digits, first digit cannot be 0
if (!preg_match('/^[1-9][0-9]{10}$/', $tcNo)) {
    $errors['tc_no'] = 'T.C. Kimlik No 11 haneli rakam olmalıdır.';
}

// License issue date: required, valid, cannot be in the future
if ($licenseDate === '' || !strtotime($licenseDate)) {
    $errors['license_date'] = 'Geçerli bir ehliyet veriliş tarihi giriniz.';
} elseif (strtotime($licenseDate) > strtotime('today')) {
    $errors['license_date'] = 'Ehliyet tarihi gelecekte olamaz.';
}

if (!empty($errors)) {
    json_response(false, 'Lütfen formdaki hataları düzeltin.', ['errors' => $errors], 422);
}

// ---- Database operations ----
try {
    $pdo = Database::getConnection();

    // Email uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        json_response(false, 'Bu e-posta zaten kayıtlı.', ['errors' => ['email' => 'Bu e-posta zaten kayıtlı.']], 409);
    }

    // National ID uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE tc_no = :tc LIMIT 1');
    $stmt->execute([':tc' => $tcNo]);
    if ($stmt->fetch()) {
        json_response(false, 'Bu T.C. Kimlik No zaten kayıtlı.', ['errors' => ['tc_no' => 'Bu T.C. Kimlik No zaten kayıtlı.']], 409);
    }

    // Hash the password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert (role defaults to 'customer')
    $insert = $pdo->prepare(
        'INSERT INTO users (name_surname, email, tc_no, password, birth_date, license_date, role)
         VALUES (:name, :email, :tc, :pass, :birth, :license, :role)'
    );
    $insert->execute([
        ':name'    => $nameSurname,
        ':email'   => $email,
        ':tc'      => $tcNo,
        ':pass'    => $hash,
        ':birth'   => $birthDate,
        ':license' => $licenseDate,
        ':role'    => 'customer',
    ]);

    json_response(true, 'Kayıt başarılı! Giriş yapabilirsiniz.', [
        'user_id' => (int) $pdo->lastInsertId(),
    ], 201);

} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        json_response(false, 'Sunucu hatası: ' . $e->getMessage(), [], 500);
    }
    error_log('[REGISTER ERROR] ' . $e->getMessage());
    json_response(false, 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.', [], 500);
}
