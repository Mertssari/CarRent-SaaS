<?php
require_once __DIR__ . '/includes/functions.php';
start_session();
if (!is_logged_in()) { header('Location: login.php'); exit; }

$rentalId = (int) ($_GET['rental_id'] ?? 0);
$csrf = csrf_token();

$pdo = Database::getConnection();
$stmt = $pdo->prepare(
    'SELECT r.*, v.brand, v.model, v.year, v.transmission, v.fuel_type, v.daily_price, v.image_path,
            l.name AS location_name
     FROM rentals r
     JOIN vehicles v ON v.id = r.vehicle_id
     LEFT JOIN locations l ON l.id = r.pickup_location_id
     WHERE r.id = :id LIMIT 1'
);
$stmt->execute([':id' => $rentalId]);
$r = $stmt->fetch();

// Yetki + durum kontrolü
if (!$r || (int) $r['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(404);
    exit('Rezervasyon bulunamadı.');
}
if ($r['status'] !== 'Pending') {
    header('Location: dashboard.php'); exit;
}

// Fiyat kırılımı (total_price KDV dahildir)
$start = new DateTime($r['start_date']);
$end   = new DateTime($r['end_date']);
$days  = $start->diff($end)->days + 1;
$daily = (float) $r['daily_price'];
$subtotal = $days * $daily;
$vat      = round($subtotal * 0.20, 2);
$total    = round($subtotal + $vat, 2);

$money = fn($n) => number_format((float) $n, 2, ',', '.') . ' ₺';
$img   = $r['image_path'] ? BASE_URL . '/' . $r['image_path'] : BASE_URL . '/assets/img/no-image.svg';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>Ödeme — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Car<span class="dot">Rent</span></a>
        <a href="dashboard.php" class="btn btn-sm btn-outline-light">Vazgeç</a>
    </div>
</nav>

<div class="container my-4">
    <div class="row g-4">
        <!-- SOL: Fatura Özeti -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm" style="border-radius:16px;">
                <div class="card-body p-4">
                    <h5 class="mb-3"><i class="bi bi-receipt"></i> Rezervasyon Özeti</h5>
                    <div class="d-flex gap-3 mb-3">
                        <img src="<?= htmlspecialchars($img) ?>" alt="" style="width:110px;height:74px;object-fit:cover;border-radius:10px;">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($r['brand'] . ' ' . $r['model']) ?></div>
                            <div class="text-muted small"><?= (int) $r['year'] ?></div>
                            <div class="mt-1">
                                <span class="spec-badge"><i class="bi bi-gear"></i> <?= htmlspecialchars($r['transmission']) ?></span>
                                <span class="spec-badge"><i class="bi bi-fuel-pump"></i> <?= htmlspecialchars($r['fuel_type']) ?></span>
                            </div>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush small mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-geo-alt text-muted"></i> Teslim Noktası</span>
                            <span class="fw-semibold"><?= htmlspecialchars($r['location_name'] ?? '—') ?></span></li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-calendar-check text-muted"></i> Alış</span>
                            <span class="fw-semibold"><?= htmlspecialchars($r['start_date']) ?> 10:00</span></li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-calendar-x text-muted"></i> İade</span>
                            <span class="fw-semibold"><?= htmlspecialchars($r['end_date']) ?> 10:00</span></li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-clock-history text-muted"></i> Toplam Süre</span>
                            <span class="fw-semibold"><?= $days ?> gün</span></li>
                    </ul>

                    <!-- Fiyat kırılımı -->
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted"><?= $money($daily) ?> × <?= $days ?> gün</span>
                        <span><?= $money($subtotal) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">KDV (%20)</span>
                        <span><?= $money($vat) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5">Toplam Ödenecek</span>
                        <span class="fw-bold fs-4" style="color:var(--brand);"><?= $money($total) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ: Kart Formu + Koşullar -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-credit-card-2-front"></i> Kart ile Öde</h5>
                        <div id="cardBrand" class="fs-4 text-muted"><i class="bi bi-credit-card"></i></div>
                    </div>
                    <form id="paymentForm" class="row g-3" novalidate>
                        <input type="hidden" id="rental_id" value="<?= $rentalId ?>">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kart Numarası</label>
                            <input type="text" id="cardNumber" class="form-control" inputmode="numeric"
                                   placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="19" autocomplete="cc-number" required>
                            <div class="invalid-feedback">Geçerli bir kart numarası girin.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kart Üzerindeki İsim</label>
                            <input type="text" id="cardName" class="form-control" placeholder="AD SOYAD"
                                   style="text-transform:uppercase" autocomplete="cc-name" required>
                            <div class="invalid-feedback">İsim gerekli.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Son Kullanma (AA/YY)</label>
                            <input type="text" id="cardExpiry" class="form-control" placeholder="AA/YY" maxlength="5"
                                   inputmode="numeric" autocomplete="cc-exp" required>
                            <div class="invalid-feedback">AA/YY formatında girin.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">CVV</label>
                            <input type="password" id="cardCvv" class="form-control" placeholder="•••" maxlength="4"
                                   inputmode="numeric" autocomplete="cc-csc" required>
                            <div class="invalid-feedback">CVV gerekli.</div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Koşullar -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius:16px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-check"></i> Kiralama Kuralları ve Önemli Uyarılar</h6>
                    <ul class="small text-muted mb-3" style="line-height:1.8;">
                        <li><strong>⛽ Yakıt Politikası:</strong> Araç nasıl alındıysa o seviyede yakıtla iade edilmelidir. Eksik yakıt durumunda yakıt bedeli + hizmet ücreti tahsil edilir.</li>
                        <li><strong>⏳ Teslim Alma Kuralları:</strong> Seçtiğiniz ofise giderken yanınızda fiziksel Ehliyetiniz, T.C. Kimlik Kartınız ve sürücü adına kayıtlı kredi kartınız bulunmalıdır. Dijital ehliyetler geçersizdir.</li>
                        <li><strong>❌ İptal ve İade:</strong> Rezervasyon saatine 24 saat kalaya kadar ücretsiz iptal yapılabilir. Son 24 saat içindeki iptallerde 1 günlük kiralama bedeli kesilir.</li>
                        <li><strong>👮 Trafik Cezaları:</strong> Kiralama süresi boyunca oluşacak köprü geçişleri (HGS), radar ve hatalı park gibi tüm trafik cezaları kiralayana aittir.</li>
                    </ul>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeTerms">
                        <label class="form-check-label small" for="agreeTerms">
                            Yukarıdaki <strong>Kiralama Kuralları ve Önemli Uyarılar</strong>'ı okudum, kabul ediyorum.
                        </label>
                    </div>
                </div>
            </div>

            <button id="payBtn" class="btn btn-accent btn-lg w-100" disabled>
                <i class="bi bi-lock-fill"></i> <?= $money($total) ?> Öde
            </button>
            <div id="payMsg" class="small mt-2 text-center"></div>
        </div>
    </div>
</div>

<script>window.CHECKOUT = { rentalId: <?= $rentalId ?> };</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/checkout.js?v=1"></script>
</body>
</html>
