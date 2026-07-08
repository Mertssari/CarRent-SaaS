<?php
require_once __DIR__ . '/includes/functions.php';
start_session();
if (!is_logged_in()) { header('Location: login.php'); exit; }
$csrf = csrf_token();
$name = $_SESSION['name_surname'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <title>Kiralamalarım — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Car<span class="dot">Rent</span></a>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="text-light small">Merhaba, <?= htmlspecialchars($name) ?></span>
            <a href="profile.php" class="btn btn-sm btn-outline-light"><i class="bi bi-person-circle"></i> Profilim</a>
            <a href="index.php" class="btn btn-sm btn-outline-light">Araçlar</a>
            <a href="api/auth/logout.php" class="btn btn-sm btn-outline-light">Çıkış</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <h4 class="mb-3"><i class="bi bi-journal-text"></i> Kiralamalarım</h4>
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr>
                        <th>Araç</th><th>Teslim Noktası</th><th>Tarih</th><th>Tutar</th><th>Durum</th><th>Ödeme</th><th></th>
                    </tr></thead>
                    <tbody id="rentalRows">
                        <tr><td colspan="7" class="text-center text-muted">Yükleniyor…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Ödeme modalı -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Ödeme</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="payBody"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/dashboard.js?v=4"></script>
</body>
</html>
