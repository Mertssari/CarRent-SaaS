// CarRent — customer panel (rentals, payment, cancellation)
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const rows = document.getElementById('rentalRows');
const payModal = new bootstrap.Modal(document.getElementById('payModal'));
const payBody = document.getElementById('payBody');

const money = (n) => Number(n).toLocaleString('tr-TR') + '₺';

const statusBadge = (s) => ({
    Pending:   '<span class="badge bg-warning text-dark">Beklemede</span>',
    Active:    '<span class="badge bg-success">Aktif</span>',
    Completed: '<span class="badge bg-primary">Tamamlandı</span>',
    Cancelled: '<span class="badge bg-secondary">İptal</span>',
}[s] || s);

const payInfo = (s) => s === 'Pending'
    ? '<span class="text-warning small">Ödeme bekleniyor</span>'
    : (s === 'Cancelled' ? '<span class="text-muted small">—</span>'
    : '<span class="text-success small">Ödendi</span>');

// 24-hour rule: cancellation is closed within 24h of pickup (start_date 10:00)
function isCancellable(r) {
    const pickup = new Date(r.start_date + 'T10:00:00');
    return (pickup.getTime() - Date.now()) >= 24 * 60 * 60 * 1000;
}

function actions(r) {
    let html = '';
    if (r.status === 'Pending') {
        html += `<button class="btn btn-sm btn-accent me-1" data-pay='${JSON.stringify(r)}'>Öde</button>`;
    }
    if (r.status === 'Pending' || r.status === 'Active') {
        html += isCancellable(r)
            ? `<button class="btn btn-sm btn-outline-danger" data-cancel="${r.id}">İptal</button>`
            : `<span class="badge bg-light text-muted border" title="Alış saatine 24 saatten az kaldığı için iptal edilemez">İptal süresi doldu</span>`;
    }
    return html || '<span class="text-muted small">—</span>';
}

async function loadRentals() {
    rows.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Yükleniyor…</td></tr>';
    const res = await fetch('api/rentals/list.php');
    const json = await res.json();
    const list = json.data?.rentals || [];
    if (!list.length) { rows.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Henüz kiralamanız yok.</td></tr>'; return; }
    rows.innerHTML = list.map(r => `
      <tr>
        <td><strong>${r.brand} ${r.model}</strong></td>
        <td class="small"><i class="bi bi-geo-alt text-muted"></i> ${r.location_name || '—'}</td>
        <td class="small">${r.start_date}<br>${r.end_date}</td>
        <td>${money(r.total_price)}</td>
        <td>${statusBadge(r.status)}</td>
        <td>${payInfo(r.status)}</td>
        <td>${actions(r)}</td>
      </tr>`).join('');
}

// Payment / cancellation actions
rows.addEventListener('click', (e) => {
    const payBtn = e.target.closest('[data-pay]');
    const cancelBtn = e.target.closest('[data-cancel]');

    if (payBtn) {
        // Complete the payment on the advanced checkout screen
        const r = JSON.parse(payBtn.dataset.pay);
        location.href = 'checkout.php?rental_id=' + r.id;
    }

    if (cancelBtn) {
        if (!confirm('Bu kiralamayı iptal etmek istiyor musunuz?')) return;
        const fd = new FormData();
        fd.append('rental_id', cancelBtn.dataset.cancel);
        fd.append('csrf_token', CSRF);
        fetch('api/rentals/cancel.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(j => { if (!j.success) alert(j.message); loadRentals(); });
    }
});

loadRentals();
