<?php
require_once __DIR__ . '/../includes/functions.php';
start_session();
// Sayfa erişim koruması (admin değilse yönlendir)
if (!is_logged_in()) { header('Location: ../login.php'); exit; }
if (!is_admin())     { header('Location: ../index.php'); exit; }
$csrf = csrf_token();
$active = 'vehicles';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Araç Yönetimi — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>

<div class="container my-4">
    <div class="row g-4">
        <!-- Ekleme formu -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0" style="border-radius:16px;">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-plus-circle"></i> Yeni Araç</h5>
                    <form id="vehicleForm" enctype="multipart/form-data" class="row g-2">
                        <div class="col-6"><input name="brand" class="form-control" placeholder="Marka" required></div>
                        <div class="col-6"><input name="model" class="form-control" placeholder="Model" required></div>
                        <div class="col-6"><input name="year" type="number" class="form-control" placeholder="Yıl" required></div>
                        <div class="col-6"><input name="daily_price" type="number" step="0.01" class="form-control" placeholder="Günlük ₺" required></div>
                        <div class="col-6">
                            <select name="type" class="form-select"><option>Sedan</option><option>SUV</option><option>Hatchback</option></select>
                        </div>
                        <div class="col-6">
                            <select name="transmission" class="form-select"><option>Automatic</option><option>Manual</option></select>
                        </div>
                        <div class="col-6">
                            <select name="fuel_type" class="form-select"><option>Gasoline</option><option>Diesel</option><option>Electric</option></select>
                        </div>
                        <div class="col-6">
                            <select name="status" class="form-select"><option>Available</option><option>Maintenance</option></select>
                        </div>
                        <div class="col-6"><input name="current_km" type="number" class="form-control" placeholder="KM" value="0"></div>
                        <div class="col-6"><input name="min_license_age" type="number" class="form-control" placeholder="Min ehliyet yılı" value="1"></div>
                        <div class="col-12">
                            <label class="form-label small">Fotoğraf (JPG/PNG)</label>
                            <input name="image" type="file" accept="image/jpeg,image/png" class="form-control">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-accent w-100">Aracı Kaydet</button>
                        </div>
                        <div class="col-12"><div id="formMsg" class="small"></div></div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius:16px;">
                <div class="card-body">
                    <h5 class="mb-3"><i class="bi bi-card-list"></i> Mevcut Araçlar</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr>
                                <th>Görsel</th><th>Araç</th><th>Tip</th><th>₺/gün</th><th>Durum</th><th></th>
                            </tr></thead>
                            <tbody id="vehicleRows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Düzenleme modalı -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content"><div class="modal-header">
        <h5 class="modal-title">Aracı Düzenle</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="editForm" enctype="multipart/form-data" class="row g-2">
          <input type="hidden" name="id">
          <div class="col-12 text-center">
            <img id="editCurrentImg" src="" alt="" style="max-height:120px;border-radius:10px;display:none;">
            <div id="editNoImg" class="text-muted small">Mevcut fotoğraf yok</div>
          </div>
          <div class="col-6"><input name="brand" class="form-control" placeholder="Marka" required></div>
          <div class="col-6"><input name="model" class="form-control" placeholder="Model" required></div>
          <div class="col-6"><input name="year" type="number" class="form-control" placeholder="Yıl" required></div>
          <div class="col-6"><input name="daily_price" type="number" step="0.01" class="form-control" placeholder="Günlük ₺" required></div>
          <div class="col-6"><select name="type" class="form-select"><option>Sedan</option><option>SUV</option><option>Hatchback</option></select></div>
          <div class="col-6"><select name="transmission" class="form-select"><option>Automatic</option><option>Manual</option></select></div>
          <div class="col-6"><select name="fuel_type" class="form-select"><option>Gasoline</option><option>Diesel</option><option>Electric</option></select></div>
          <div class="col-6"><select name="status" class="form-select"><option>Available</option><option>Rented</option><option>Maintenance</option></select></div>
          <div class="col-6"><input name="current_km" type="number" class="form-control" placeholder="KM"></div>
          <div class="col-6"><input name="min_license_age" type="number" class="form-control" placeholder="Min ehliyet yılı"></div>
          <div class="col-12"><label class="form-label small">Yeni Fotoğraf (opsiyonel)</label>
            <input name="image" type="file" accept="image/jpeg,image/png" class="form-control"></div>
          <div class="col-12"><button class="btn btn-accent w-100">Güncelle</button></div>
          <div class="col-12"><div id="editMsg" class="small"></div></div>
        </form>
      </div></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin-vehicles.js?v=6"></script>
</body>
</html>
