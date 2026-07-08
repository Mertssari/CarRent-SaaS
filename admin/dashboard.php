<?php
require_once __DIR__ . '/../includes/functions.php';
start_session();
if (!is_logged_in()) { header('Location: ../login.php'); exit; }
if (!is_admin())     { header('Location: ../index.php'); exit; }
$csrf = csrf_token();
$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <title>Panel — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="container">
    <h4 class="mb-4"><i class="bi bi-speedometer2"></i> Genel Bakış</h4>
    <div class="row g-3" id="statCards">
        <div class="col-12 text-muted">Yükleniyor…</div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius:16px;">
                <div class="card-body">
                    <h6 class="mb-3">Hızlı Erişim</h6>
                    <a href="vehicles.php" class="btn btn-outline-dark me-2"><i class="bi bi-plus-circle"></i> Araç Ekle</a>
                    <a href="rentals.php" class="btn btn-outline-dark"><i class="bi bi-journal-text"></i> Kiralamalar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>
