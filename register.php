<?php
require_once __DIR__ . '/includes/functions.php';
start_session();
if (is_logged_in()) { header('Location: index.php'); exit; }
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <title>Kayıt Ol — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center py-4" style="min-height:100vh;background:linear-gradient(135deg,#0d1b2a,#1b263b);">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card border-0 shadow-lg" style="border-radius:16px;">
        <div class="card-body p-4">
          <h4 class="text-center mb-1">Car<span style="color:#e0a458;">Rent</span></h4>
          <p class="text-center text-muted small mb-4">Yeni hesap oluşturun</p>
          <form id="registerForm" class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold">Ad Soyad</label>
              <input name="name_surname" class="form-control" required>
              <div class="invalid-feedback" data-err="name_surname"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">E-posta</label>
              <input type="email" name="email" class="form-control" required>
              <div class="invalid-feedback" data-err="email"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">T.C. Kimlik No</label>
              <input type="text" name="tc_no" class="form-control" inputmode="numeric"
                     maxlength="11" pattern="[0-9]{11}" placeholder="11 haneli"
                     oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
              <div class="invalid-feedback" data-err="tc_no"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Doğum Tarihi</label>
              <input type="date" name="birth_date" class="form-control" required>
              <div class="invalid-feedback" data-err="birth_date"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Ehliyet Veriliş Tarihi</label>
              <input type="date" name="license_date" class="form-control" required>
              <div class="invalid-feedback" data-err="license_date"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Parola</label>
              <input type="password" name="password" class="form-control" required>
              <div class="invalid-feedback" data-err="password"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Parola (Tekrar)</label>
              <input type="password" name="password_confirm" class="form-control" required>
              <div class="invalid-feedback" data-err="password_confirm"></div>
            </div>
            <div class="col-12">
              <button class="btn btn-accent w-100">Kayıt Ol</button>
              <div id="msg" class="small mt-3 text-center"></div>
            </div>
          </form>
          <p class="text-center small mt-3 mb-0">
            Zaten üye misiniz? <a href="login.php">Giriş yapın</a>
          </p>
        </div>
      </div>
      <p class="text-center mt-3"><a href="index.php" class="text-light small">← Ana sayfa</a></p>
    </div>
  </div>
</div>
<script src="assets/js/auth.js?v=3"></script>
</body>
</html>
