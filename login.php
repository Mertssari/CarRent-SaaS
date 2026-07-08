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
    <title>Giriş — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="min-height:100vh;background:linear-gradient(135deg,#0d1b2a,#1b263b);">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card border-0 shadow-lg" style="border-radius:16px;">
        <div class="card-body p-4">
          <h4 class="text-center mb-1">Car<span style="color:#e0a458;">Rent</span></h4>
          <p class="text-center text-muted small mb-4">Hesabınıza giriş yapın</p>
          <form id="loginForm">
            <div class="mb-3">
              <label class="form-label small fw-semibold">E-posta</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Parola</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-accent w-100">Giriş Yap</button>
            <div id="msg" class="small mt-3 text-center"></div>
          </form>
          <p class="text-center small mt-3 mb-0">
            Hesabınız yok mu? <a href="register.php">Kayıt olun</a>
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
