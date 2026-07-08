<?php
require_once __DIR__ . '/../includes/functions.php';
start_session();
if (!is_logged_in()) { header('Location: ../login.php'); exit; }
if (!is_admin())     { header('Location: ../index.php'); exit; }
$csrf = csrf_token();
$active = 'customers';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <title>Müşteriler — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-people"></i> Müşteriler</h4>
        <button id="purgeBtn" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-trash3"></i> Tüm Müşterileri Temizle
        </button>
    </div>
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr>
                        <th>#</th><th>Ad Soyad</th><th>E-posta</th><th>T.C. No</th>
                        <th>Ehliyet</th><th>Kiralama</th><th>Kayıt</th><th></th>
                    </tr></thead>
                    <tbody id="customerRows">
                        <tr><td colspan="8" class="text-center text-muted">Yükleniyor…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="appToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-customers.js?v=1"></script>
</body>
</html>
