<?php
/**
 * includes/admin_nav.php
 * Shared admin top navigation. Set $active to highlight the current tab.
 * Usage: $active = 'dashboard'; require '.../includes/admin_nav.php';
 */
if (!isset($active)) { $active = ''; }
$items = [
    'dashboard' => ['dashboard.php', 'Panel', 'bi-speedometer2'],
    'vehicles'  => ['vehicles.php',  'Araçlar', 'bi-car-front'],
    'rentals'   => ['rentals.php',   'Kiralamalar', 'bi-journal-text'],
    'customers' => ['customers.php', 'Müşteriler', 'bi-people'],
];
?>
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Car<span style="color:#e0a458;">Rent</span>
            <span class="badge bg-warning text-dark ms-2">Admin</span></a>
        <div class="d-flex gap-2 align-items-center">
            <?php foreach ($items as $key => [$url, $label, $icon]): ?>
                <a href="<?= $url ?>" class="btn btn-sm <?= $active === $key ? 'btn-accent' : 'btn-outline-light' ?>">
                    <i class="bi <?= $icon ?>"></i> <?= $label ?>
                </a>
            <?php endforeach; ?>
            <a href="../index.php" class="btn btn-sm btn-outline-light">Site</a>
            <a href="../api/auth/logout.php" class="btn btn-sm btn-outline-light">Çıkış</a>
        </div>
    </div>
</nav>
