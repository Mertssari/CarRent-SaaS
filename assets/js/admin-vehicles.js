// CarRent — admin vehicle management (create / edit / delete)
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const BASE = document.querySelector('meta[name="base-url"]').content; // e.g. /CarRent
const NO_IMG = BASE + '/assets/img/no-image.svg';
const imgUrl = (path) => path ? BASE + '/' + String(path).replace(/^\/+/, '') : NO_IMG;
const rows = document.getElementById('vehicleRows');
const formMsg = document.getElementById('formMsg');
const editForm = document.getElementById('editForm');
const editMsg = document.getElementById('editMsg');
const editModal = new bootstrap.Modal(document.getElementById('editModal'));

let cache = {}; // id -> vehicle (used to populate the edit form)

const statusBadge = (s) => ({
    Available: '<span class="badge bg-success">Available</span>',
    Rented:    '<span class="badge bg-secondary">Rented</span>',
    Maintenance:'<span class="badge bg-warning text-dark">Maintenance</span>',
}[s] || s);

async function loadVehicles() {
    rows.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Yükleniyor…</td></tr>';
    const res = await fetch('../api/vehicles/list.php');
    const json = await res.json();
    const list = json.data?.vehicles || [];
    cache = {};
    if (!list.length) { rows.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Kayıt yok.</td></tr>'; return; }
    rows.innerHTML = list.map(v => {
        cache[v.id] = v;
        return `
      <tr>
        <td><img src="${imgUrl(v.image_path)}" width="56" height="40"
                 style="object-fit:cover;border-radius:6px;"
                 onerror="this.onerror=null;this.src='${NO_IMG}'"></td>
        <td><strong>${v.brand} ${v.model}</strong><br><span class="text-muted small">${v.year}</span></td>
        <td>${v.type}</td>
        <td>${Number(v.daily_price).toLocaleString('tr-TR')}₺</td>
        <td>${statusBadge(v.status)}</td>
        <td>
          <button class="btn btn-sm btn-outline-secondary me-1" data-edit="${v.id}"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-outline-danger" data-del="${v.id}"><i class="bi bi-trash"></i></button>
        </td>
      </tr>`;
    }).join('');
}

// Create
document.getElementById('vehicleForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    formMsg.textContent = '';
    const fd = new FormData(e.target);
    fd.append('csrf_token', CSRF);
    const json = await (await fetch('../api/admin/vehicles/create.php', { method: 'POST', body: fd })).json();
    formMsg.className = 'small ' + (json.success ? 'text-success' : 'text-danger');
    formMsg.textContent = json.message;
    if (json.success) { e.target.reset(); loadVehicles(); }
});

// Edit / Delete
rows.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('[data-edit]');
    const delBtn = e.target.closest('[data-del]');

    if (editBtn) {
        const v = cache[editBtn.dataset.edit];
        if (!v) return;
        editMsg.textContent = '';
        editForm.reset();
        ['id','brand','model','year','daily_price','type','transmission','fuel_type','status','current_km','min_license_age']
            .forEach(f => { if (editForm[f]) editForm[f].value = v[f]; });
        // Current photo preview (kept unless a new file is chosen)
        const curImg = document.getElementById('editCurrentImg');
        const noImg = document.getElementById('editNoImg');
        if (v.image_path) {
            curImg.src = imgUrl(v.image_path); curImg.style.display = 'inline-block'; noImg.style.display = 'none';
        } else {
            curImg.style.display = 'none'; noImg.style.display = 'block';
        }
        editModal.show();
    }

    if (delBtn) {
        if (!confirm('Bu araç silinsin mi?')) return;
        const fd = new FormData();
        fd.append('id', delBtn.dataset.del);
        fd.append('csrf_token', CSRF);
        const json = await (await fetch('../api/admin/vehicles/delete.php', { method: 'POST', body: fd })).json();
        if (!json.success) alert(json.message);
        loadVehicles();
    }
});

// Save edit
editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    editMsg.textContent = '';
    const fd = new FormData(editForm);
    fd.append('csrf_token', CSRF);
    const json = await (await fetch('../api/admin/vehicles/update.php', { method: 'POST', body: fd })).json();
    editMsg.className = 'small ' + (json.success ? 'text-success' : 'text-danger');
    editMsg.textContent = json.message;
    if (json.success) setTimeout(() => { editModal.hide(); loadVehicles(); }, 800);
});

loadVehicles();
