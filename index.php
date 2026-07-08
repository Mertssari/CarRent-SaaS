<?php
require_once __DIR__ . '/includes/functions.php';
start_session();
$csrf    = csrf_token();
$isAuth  = is_logged_in();
$isAdmin = is_admin();
$name    = $_SESSION['name_surname'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Car<span class="dot">Rent</span></a>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <?php if ($isAuth): ?>
                <span class="text-light small">Merhaba, <?= htmlspecialchars($name) ?></span>
                <a href="profile.php" class="btn btn-sm btn-outline-light"><i class="bi bi-person-circle"></i> Profilim</a>
                <?php if ($isAdmin): ?>
                    <a href="admin/dashboard.php" class="btn btn-sm btn-accent">
                        <i class="bi bi-speedometer2"></i> Admin Paneli
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-light">Kiralamalarım</a>
                <?php endif; ?>
                <a href="api/auth/logout.php" class="btn btn-sm btn-outline-light">Çıkış</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-outline-light">Giriş</a>
                <a href="register.php" class="btn btn-sm btn-accent">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- HERO + FİLTRE -->
<header class="hero">
    <div class="container text-center">
        <h1 class="display-6">Yolculuğunuza uygun aracı bulun</h1>
        <p class="lead opacity-75">Tarih seçin, filtreleyin, saniyeler içinde kiralayın.</p>
    </div>
</header>

<div class="container">
    <div class="card filter-card p-3 p-md-4">
        <form id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold"><i class="bi bi-geo-alt"></i> Nereden Alacaksınız?</label>
                <select name="pickup_location_id" id="pickup_location_id" class="form-select">
                    <option value="">Lokasyon seçin…</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Alış Tarihi</label>
                <input type="date" name="start_date" id="start_date" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">İade Tarihi</label>
                <input type="date" name="end_date" id="end_date" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-accent w-100">
                    <i class="bi bi-search"></i> Ara
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ARAÇ VİTRİNİ -->
<main class="container my-5">
    <div class="row g-4">
        <!-- Filtre sidebar -->
        <aside class="col-lg-3">
            <div class="card border-0 shadow-sm" style="border-radius:16px;">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-funnel"></i> Filtrele</h6>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sırala</label>
                        <select id="f_sort" class="form-select form-select-sm">
                            <option value="price_asc">Fiyat: Artan</option>
                            <option value="price_desc">Fiyat: Azalan</option>
                            <option value="year_desc">Model Yılı: Yeni</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Araç Sınıfı</label>
                        <select id="f_type" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option>Sedan</option><option>SUV</option><option>Hatchback</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Vites</label>
                        <select id="f_transmission" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option>Manual</option><option>Automatic</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Yakıt</label>
                        <select id="f_fuel" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option>Gasoline</option><option>Diesel</option><option>Electric</option>
                        </select>
                    </div>
                    <button id="f_reset" class="btn btn-sm btn-outline-secondary w-100 mt-2">Filtreleri Temizle</button>
                </div>
            </div>
        </aside>

        <!-- Araç grid -->
        <div class="col-lg-9">
            <div id="statusBar" class="text-muted small mb-3"></div>
            <div id="vehicleGrid" class="row g-4"></div>
        </div>
    </div>
</main>

<!-- Rezervasyon modalı -->
<div class="modal fade" id="bookModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Rezervasyon Onayı</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="bookBody"></div>
    </div>
  </div>
</div>

<footer class="bg-dark text-light py-4 text-center small">
    &copy; <?= date('Y') ?> CarRent — Akıllı Araç Kiralama
</footer>

<script>window.APP = { isAuth: <?= $isAuth ? 'true' : 'false' ?>, isAdmin: <?= $isAdmin ? 'true' : 'false' ?> };</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=4"></script>
</body>
</html>
