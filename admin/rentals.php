<?php
require_once __DIR__ . '/../includes/functions.php';
start_session();
if (!is_logged_in()) { header('Location: ../login.php'); exit; }
if (!is_admin())     { header('Location: ../index.php'); exit; }
$csrf = csrf_token();
$active = 'rentals';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <title>Kiralamalar — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="bi bi-journal-text"></i> Tüm Kiralamalar</h4>
        <select id="statusFilter" class="form-select w-auto">
            <option value="">Tümü</option>
            <option>Pending</option><option>Active</option>
            <option>Completed</option><option>Cancelled</option>
        </select>
    </div>
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr>
                        <th>#</th><th>Müşteri</th><th>Araç</th><th>Teslim Noktası</th><th>Tarih</th>
                        <th>KM</th><th>Tutar</th><th>Durum</th><th>İşlem</th>
                    </tr></thead>
                    <tbody id="rentalRows"><tr><td colspan="9" class="text-center text-muted">Yükleniyor…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- İade modalı -->
<div class="modal fade" id="returnModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content"><div class="modal-header">
        <h5 class="modal-title">Araç İadesi</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="returnBody"></div></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-rentals.js?v=2"></script>
</body>
</html>
