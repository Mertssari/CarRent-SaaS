// CarRent — admin customer management
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const rows = document.getElementById('customerRows');
const toastEl = document.getElementById('appToast');
const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

function showToast(msg, ok) {
    toastEl.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('toastBody').textContent = msg;
    toast.show();
}

async function loadCustomers() {
    rows.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Yükleniyor…</td></tr>';
    const json = await (await fetch('../api/admin/users/list.php')).json();
    const list = json.data?.customers || [];
    if (!list.length) {
        rows.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Kayıtlı müşteri yok.</td></tr>';
        return;
    }
    rows.innerHTML = list.map(c => `
      <tr>
        <td>#${c.id}</td>
        <td><strong>${c.name_surname}</strong></td>
        <td class="small">${c.email}</td>
        <td class="small">${c.tc_no || '—'}</td>
        <td class="small">${c.license_date || '—'}</td>
        <td>
          <span class="badge bg-light text-dark border">${c.rental_count} toplam</span>
          ${Number(c.open_rentals) > 0 ? `<span class="badge bg-warning text-dark">${c.open_rentals} açık</span>` : ''}
        </td>
        <td class="small text-muted">${(c.created_at || '').slice(0, 10)}</td>
        <td>
          <button class="btn btn-sm btn-outline-danger" data-del="${c.id}" data-name="${c.name_surname}">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>`).join('');
}

// Delete a single customer
rows.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-del]');
    if (!btn) return;
    if (!confirm(`"${btn.dataset.name}" ve tüm kiralama/ödeme kayıtları silinecek. Emin misiniz?`)) return;
    const fd = new FormData();
    fd.append('id', btn.dataset.del);
    fd.append('csrf_token', CSRF);
    const json = await (await fetch('../api/admin/users/delete.php', { method: 'POST', body: fd })).json();
    showToast(json.message, json.success);
    loadCustomers();
});

// Purge all customers (admin accounts are preserved)
document.getElementById('purgeBtn').addEventListener('click', async () => {
    if (!confirm('TÜM müşteriler ve kiralama/ödeme kayıtları silinecek (admin korunur). Bu işlem geri alınamaz. Emin misiniz?')) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    const json = await (await fetch('../api/admin/users/purge.php', { method: 'POST', body: fd })).json();
    showToast(json.message, json.success);
    loadCustomers();
});

loadCustomers();
