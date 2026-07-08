// CarRent — admin rental management (return / cancel)
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const rows = document.getElementById('rentalRows');
const filter = document.getElementById('statusFilter');
const returnModal = new bootstrap.Modal(document.getElementById('returnModal'));
const returnBody = document.getElementById('returnBody');

const money = (n) => Number(n).toLocaleString('tr-TR') + '₺';
const badge = (s) => ({
    Pending:'<span class="badge bg-warning text-dark">Beklemede</span>',
    Active:'<span class="badge bg-success">Aktif</span>',
    Completed:'<span class="badge bg-primary">Tamamlandı</span>',
    Cancelled:'<span class="badge bg-secondary">İptal</span>',
}[s] || s);

function actions(r) {
    let h = '';
    if (r.status === 'Active')
        h += `<button class="btn btn-sm btn-accent me-1" data-return='${JSON.stringify(r)}'>İade Al</button>`;
    if (r.status === 'Pending' || r.status === 'Active')
        h += `<button class="btn btn-sm btn-outline-danger" data-cancel="${r.id}">İptal</button>`;
    return h || '<span class="text-muted small">—</span>';
}

async function load() {
    rows.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Yükleniyor…</td></tr>';
    const q = filter.value ? '?status=' + encodeURIComponent(filter.value) : '';
    const res = await fetch('../api/rentals/list.php' + q);
    const json = await res.json();
    const list = json.data?.rentals || [];
    if (!list.length) { rows.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Kayıt yok.</td></tr>'; return; }
    rows.innerHTML = list.map(r => `
      <tr>
        <td>#${r.id}</td>
        <td>${r.name_surname}</td>
        <td>${r.brand} ${r.model}</td>
        <td class="small"><i class="bi bi-geo-alt text-muted"></i> ${r.location_name || '—'}</td>
        <td class="small">${r.start_date}<br>${r.end_date}</td>
        <td class="small">${Number(r.start_km).toLocaleString('tr-TR')}${r.end_km ? ' → ' + Number(r.end_km).toLocaleString('tr-TR') : ''}</td>
        <td>${money(r.total_price)}</td>
        <td>${badge(r.status)}</td>
        <td>${actions(r)}</td>
      </tr>`).join('');
}

rows.addEventListener('click', (e) => {
    const ret = e.target.closest('[data-return]');
    const can = e.target.closest('[data-cancel]');

    if (ret) {
        const r = JSON.parse(ret.dataset.return);
        returnBody.innerHTML = `
          <p><strong>${r.brand} ${r.model}</strong> — ${r.name_surname}</p>
          <p class="small text-muted">Çıkış KM: ${Number(r.start_km).toLocaleString('tr-TR')}</p>
          <label class="form-label small">Teslim (Bitiş) KM</label>
          <input type="number" id="endKm" class="form-control mb-3" min="${r.start_km}" value="${r.start_km}">
          <button id="confirmReturn" class="btn btn-accent w-100">İadeyi Tamamla</button>
          <div id="retMsg" class="small mt-2 text-center"></div>`;
        returnModal.show();
        document.getElementById('confirmReturn').onclick = async () => {
            const fd = new FormData();
            fd.append('rental_id', r.id);
            fd.append('end_km', document.getElementById('endKm').value);
            fd.append('csrf_token', CSRF);
            const j = await (await fetch('../api/rentals/return.php', { method:'POST', body:fd })).json();
            const m = document.getElementById('retMsg');
            m.className = 'small mt-2 text-center ' + (j.success ? 'text-success' : 'text-danger');
            m.textContent = j.message;
            if (j.success) setTimeout(() => { returnModal.hide(); load(); }, 1000);
        };
    }

    if (can) {
        if (!confirm('Bu kiralama iptal edilsin mi?')) return;
        const fd = new FormData();
        fd.append('rental_id', can.dataset.cancel);
        fd.append('csrf_token', CSRF);
        fetch('../api/rentals/cancel.php', { method:'POST', body:fd })
            .then(r => r.json()).then(j => { if (!j.success) alert(j.message); load(); });
    }
});

filter.addEventListener('change', load);
load();
