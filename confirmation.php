<?php
require_once __DIR__ . '/includes/functions.php';
start_session();
if (!is_logged_in()) { header('Location: login.php'); exit; }

$rentalId = (int) ($_GET['rental_id'] ?? 0);
$pdo = Database::getConnection();
$stmt = $pdo->prepare(
    'SELECT r.*, v.brand, v.model, v.year, v.transmission, v.fuel_type, v.daily_price, v.image_path,
            l.name AS location_name, u.name_surname, u.email,
            p.amount AS paid_amount, p.payment_method, p.payment_date
     FROM rentals r
     JOIN vehicles v ON v.id = r.vehicle_id
     JOIN users u    ON u.id = r.user_id
     LEFT JOIN locations l ON l.id = r.pickup_location_id
     LEFT JOIN payments p  ON p.rental_id = r.id AND p.payment_status = \'Paid\'
     WHERE r.id = :id
     ORDER BY p.id DESC LIMIT 1'
);
$stmt->execute([':id' => $rentalId]);
$r = $stmt->fetch();

if (!$r || (int) $r['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(404);
    exit('Rezervasyon bulunamadı.');
}
// Ödenmemiş rezervasyonu buraya sokma
if (!in_array($r['status'], ['Active', 'Completed'], true)) {
    header('Location: checkout.php?rental_id=' . $rentalId); exit;
}

$start = new DateTime($r['start_date']);
$end   = new DateTime($r['end_date']);
$days  = $start->diff($end)->days + 1;
$money = fn($n) => number_format((float) $n, 2, ',', '.') . ' ₺';
$img   = $r['image_path'] ? BASE_URL . '/' . $r['image_path'] : BASE_URL . '/assets/img/no-image.svg';
$paid  = $r['paid_amount'] ?? $r['total_price'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyon Fişi #<?= $rentalId ?> — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .receipt { box-shadow: none !important; border: 1px solid #ddd; }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark no-print">
    <div class="container">
        <a class="navbar-brand" href="index.php">Car<span class="dot">Rent</span></a>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-sm btn-outline-light">Kiralamalarım</a>
            <a href="index.php" class="btn btn-sm btn-outline-light">Ana Sayfa</a>
        </div>
    </div>
</nav>

<div class="container my-4" style="max-width:720px;">
    <!-- Başarı başlığı -->
    <div class="text-center mb-4 no-print">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
             style="width:72px;height:72px;background:#19875422;color:#198754;font-size:2.2rem;">
            <i class="bi bi-check-lg"></i>
        </div>
        <h4 class="mb-1">Ödemeniz Başarıyla Alındı</h4>
        <p class="text-muted">Rezervasyonunuz onaylandı. Fişinizi yazdırabilir veya saklayabilirsiniz.</p>
    </div>

    <!-- Dijital fiş -->
    <div class="card receipt border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                <div>
                    <div class="h5 mb-0">Car<span style="color:var(--accent);">Rent</span></div>
                    <div class="text-muted small">Dijital Rezervasyon Fişi</div>
                </div>
                <div class="text-end">
                    <div class="badge bg-success">ONAYLANDI</div>
                    <div class="small text-muted mt-1">Rez. No: <strong>#<?= $rentalId ?></strong></div>
                </div>
            </div>

            <div class="d-flex gap-3 mb-3">
                <img src="<?= htmlspecialchars($img) ?>" alt="" style="width:120px;height:80px;object-fit:cover;border-radius:10px;">
                <div>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($r['brand'] . ' ' . $r['model']) ?> <span class="text-muted fw-normal">(<?= (int) $r['year'] ?>)</span></div>
                    <div class="mt-1">
                        <span class="spec-badge"><i class="bi bi-gear"></i> <?= htmlspecialchars($r['transmission']) ?></span>
                        <span class="spec-badge"><i class="bi bi-fuel-pump"></i> <?= htmlspecialchars($r['fuel_type']) ?></span>
                    </div>
                </div>
            </div>

            <div class="row small g-2 mb-3">
                <div class="col-6"><span class="text-muted">Sürücü:</span> <strong><?= htmlspecialchars($r['name_surname']) ?></strong></div>
                <div class="col-6"><span class="text-muted">E-posta:</span> <strong><?= htmlspecialchars($r['email']) ?></strong></div>
                <div class="col-6"><span class="text-muted">Teslim Noktası:</span> <strong><?= htmlspecialchars($r['location_name'] ?? '—') ?></strong></div>
                <div class="col-6"><span class="text-muted">Toplam Süre:</span> <strong><?= $days ?> gün</strong></div>
                <div class="col-6"><span class="text-muted">Alış:</span> <strong><?= htmlspecialchars($r['start_date']) ?> 10:00</strong></div>
                <div class="col-6"><span class="text-muted">İade:</span> <strong><?= htmlspecialchars($r['end_date']) ?> 10:00</strong></div>
                <div class="col-6"><span class="text-muted">Ödeme Yöntemi:</span> <strong><?= htmlspecialchars($r['payment_method'] ?? 'Credit Card') ?></strong></div>
                <div class="col-6"><span class="text-muted">Ödeme Tarihi:</span> <strong><?= htmlspecialchars($r['payment_date'] ?? '-') ?></strong></div>
            </div>

            <div class="d-flex justify-content-between align-items-center bg-light rounded p-3">
                <span class="fw-bold">Ödenen Tutar (KDV Dahil)</span>
                <span class="fw-bold fs-4" style="color:var(--brand);"><?= $money($paid) ?></span>
            </div>

            <p class="text-muted small mt-3 mb-0">
                Ofise giderken fiziksel ehliyet, T.C. kimlik kartı ve sürücü adına kayıtlı kredi kartınızı yanınızda bulundurmayı unutmayın.
            </p>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-accent"><i class="bi bi-printer"></i> Fişi Yazdır</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Kiralamalarıma Dön</a>
    </div>
</div>

</body>
</html>
