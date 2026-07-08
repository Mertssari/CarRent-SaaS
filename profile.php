<?php
require_once __DIR__ . '/includes/functions.php';
start_session();
if (!is_logged_in()) { header('Location: login.php'); exit; }
$csrf = csrf_token();

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT name_surname, email, tc_no, birth_date, license_date FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => (int) $_SESSION['user_id']]);
$u = $stmt->fetch();
$isAdmin = is_admin();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <title>Profilim — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Car<span class="dot">Rent</span></a>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="text-light small">Merhaba, <span id="navName"><?= htmlspecialchars($u['name_surname']) ?></span></span>
            <?php if ($isAdmin): ?>
                <a href="admin/dashboard.php" class="btn btn-sm btn-accent"><i class="bi bi-speedometer2"></i> Admin Paneli</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-sm btn-outline-light">Kiralamalarım</a>
            <?php endif; ?>
            <a href="api/auth/logout.php" class="btn btn-sm btn-outline-light">Çıkış</a>
        </div>
    </div>
</nav>

<div class="container my-4" style="max-width:960px;">
    <h4 class="mb-4"><i class="bi bi-person-circle"></i> Profilim</h4>
    <div class="row g-4">

        <!-- SOL: Profil Bilgileri -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm" style="border-radius:16px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square"></i> Kişisel Bilgiler</h6>
                    <form id="profileForm" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ad Soyad</label>
                            <input name="name_surname" class="form-control" value="<?= htmlspecialchars($u['name_surname']) ?>" required>
                            <div class="invalid-feedback" data-err="name_surname"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                            <div class="invalid-feedback" data-err="email"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">T.C. Kimlik No <i class="bi bi-lock-fill text-muted"></i></label>
                            <input class="form-control" value="<?= htmlspecialchars($u['tc_no'] ?? '—') ?>" disabled>
                            <div class="form-text small">Güvenlik nedeniyle değiştirilemez.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Doğum Tarihi</label>
                            <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($u['birth_date']) ?>" required>
                            <div class="invalid-feedback" data-err="birth_date"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ehliyet Veriliş Tarihi</label>
                            <input type="date" name="license_date" class="form-control" value="<?= htmlspecialchars($u['license_date'] ?? '') ?>" required>
                            <div class="invalid-feedback" data-err="license_date"></div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-accent"><i class="bi bi-check2"></i> Bilgileri Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SAĞ: Şifre Değiştir -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm" style="border-radius:16px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock"></i> Şifre Değiştir</h6>
                    <form id="passwordForm" class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Mevcut Şifre</label>
                            <input type="password" name="current_password" class="form-control" required>
                            <div class="invalid-feedback" data-err="current_password"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Yeni Şifre</label>
                            <input type="password" name="new_password" class="form-control" minlength="8" required>
                            <div class="invalid-feedback" data-err="new_password"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Yeni Şifre (Tekrar)</label>
                            <input type="password" name="new_password_confirm" class="form-control" required>
                            <div class="invalid-feedback" data-err="new_password_confirm"></div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-dark w-100"><i class="bi bi-key"></i> Şifreyi Değiştir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast (şık uyarı) -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/profile.js?v=1"></script>
</body>
</html>
