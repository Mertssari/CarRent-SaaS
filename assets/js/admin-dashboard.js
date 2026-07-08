// CarRent — admin dashboard summary
const cards = document.getElementById('statCards');

const card = (icon, label, value, color) => `
  <div class="col-6 col-lg-4">
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center"
             style="width:48px;height:48px;background:${color}1a;color:${color};font-size:1.4rem;">
          <i class="bi ${icon}"></i>
        </div>
        <div>
          <div class="h4 mb-0">${value}</div>
          <div class="text-muted small">${label}</div>
        </div>
      </div>
    </div>
  </div>`;

(async () => {
    const res = await fetch('../api/admin/stats.php');
    const json = await res.json();
    if (!json.success) { cards.innerHTML = `<div class="col-12 text-danger">${json.message}</div>`; return; }
    const s = json.data.stats;
    const money = Number(s.revenue).toLocaleString('tr-TR') + '₺';
    cards.innerHTML =
        card('bi-cash-stack', 'Toplam Gelir', money, '#198754') +
        card('bi-car-front', 'Toplam Araç', s.vehicles_total, '#0d6efd') +
        card('bi-key', 'Kirada', s.vehicles_rented, '#e0a458') +
        card('bi-people', 'Müşteri', s.customers, '#6610f2') +
        card('bi-hourglass-split', 'Bekleyen', s.rentals_pending, '#fd7e14') +
        card('bi-check2-circle', 'Aktif Kiralama', s.rentals_active, '#20c997');
})();
