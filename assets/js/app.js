// CarRent — customer showcase (AJAX)
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const BASE = document.querySelector('meta[name="base-url"]').content; // e.g. /CarRent
const grid = document.getElementById('vehicleGrid');
const statusBar = document.getElementById('statusBar');

const badge = (icon, text) => `<span class="spec-badge me-1 mb-1 d-inline-block"><i class="bi ${icon}"></i> ${text}</span>`;

// Root-relative image URL, compatible with subfolder deployments
const NO_IMG = BASE + '/assets/img/no-image.svg';
const imgUrl = (path) => path ? BASE + '/' + String(path).replace(/^\/+/, '') : NO_IMG;

// Admins cannot rent -> show a label instead of the button
const bookButton = (v) => window.APP.isAdmin
    ? '<span class="badge bg-secondary">Yönetici Hesabı</span>'
    : `<button class="btn btn-sm btn-accent" data-book='${JSON.stringify(v)}'>Kirala</button>`;

function vehicleCard(v) {
    const img = `<img src="${imgUrl(v.image_path)}" alt="${v.brand} ${v.model}"
                     onerror="this.onerror=null;this.src='${NO_IMG}'">`;
    return `
    <div class="col-sm-6 col-lg-4">
      <div class="vehicle-card">
        <div class="img-wrap">${img}</div>
        <div class="p-3">
          <h5 class="mb-1">${v.brand} ${v.model}</h5>
          <div class="text-muted small mb-2">${v.year}</div>
          <div class="mb-3">
            ${badge('bi-car-front', v.type)}
            ${badge('bi-gear', v.transmission)}
            ${badge('bi-fuel-pump', v.fuel_type)}
            ${badge('bi-speedometer2', Number(v.current_km).toLocaleString('tr-TR') + ' km')}
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="price">${Number(v.daily_price).toLocaleString('tr-TR')}₺ <small>/gün</small></div>
            ${bookButton(v)}
          </div>
        </div>
      </div>
    </div>`;
}

// Collect all search + filter criteria into a single query string
function buildParams() {
    const p = new URLSearchParams();
    const add = (k, v) => { if (v) p.set(k, v); };
    add('start_date', document.getElementById('start_date').value);
    add('end_date', document.getElementById('end_date').value);
    add('type', document.getElementById('f_type').value);
    add('transmission', document.getElementById('f_transmission').value);
    add('fuel_type', document.getElementById('f_fuel').value);
    add('sort', document.getElementById('f_sort').value);
    return p.toString();
}

async function loadVehicles() {
    grid.innerHTML = '<div class="col-12 text-center text-muted py-5">Yükleniyor…</div>';
    try {
        const res = await fetch('api/vehicles/list.php?' + buildParams());
        const json = await res.json();
        if (!json.success) { statusBar.textContent = json.message; grid.innerHTML = ''; return; }
        const list = json.data.vehicles;
        statusBar.textContent = json.data.date_filter
            ? `Seçilen tarihlerde ${json.data.count} araç müsait.`
            : `${json.data.count} araç listeleniyor.`;
        grid.innerHTML = list.length
            ? list.map(vehicleCard).join('')
            : '<div class="col-12 text-center text-muted py-5">Uygun araç bulunamadı.</div>';
    } catch (e) {
        grid.innerHTML = '<div class="col-12 text-danger text-center py-5">Bir hata oluştu.</div>';
    }
}

// Date search
document.getElementById('filterForm').addEventListener('submit', (e) => {
    e.preventDefault();
    loadVehicles();
});

// Sidebar filters -> instant reload
['f_sort', 'f_type', 'f_transmission', 'f_fuel'].forEach(id =>
    document.getElementById(id).addEventListener('change', loadVehicles));

// Reset filters
document.getElementById('f_reset').addEventListener('click', () => {
    ['f_type', 'f_transmission', 'f_fuel'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('f_sort').value = 'price_asc';
    loadVehicles();
});

// Load pickup locations
(async () => {
    try {
        const json = await (await fetch('api/locations/list.php')).json();
        const sel = document.getElementById('pickup_location_id');
        (json.data?.locations || []).forEach(l => {
            const o = document.createElement('option');
            o.value = l.id; o.textContent = l.name;
            sel.appendChild(o);
        });
    } catch (e) { /* silent */ }
})();

// Booking
const bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
const bookBody = document.getElementById('bookBody');

grid.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-book]');
    if (!btn) return;
    if (!window.APP.isAuth) { location.href = 'login.php'; return; }

    const v = JSON.parse(btn.dataset.book);
    const s = document.getElementById('start_date').value;
    const en = document.getElementById('end_date').value;
    const locSel = document.getElementById('pickup_location_id');
    const locId = locSel.value;
    if (!s || !en) { alert('Lütfen önce alış ve iade tarihi seçin.'); return; }
    if (!locId) { alert('Lütfen "Nereden Alacaksınız?" lokasyonunu seçin.'); return; }

    bookBody.innerHTML = `
      <p><strong>${v.brand} ${v.model}</strong> (${v.year})</p>
      <p class="small text-muted"><i class="bi bi-geo-alt"></i> ${locSel.options[locSel.selectedIndex].text}</p>
      <p class="small text-muted">${s} → ${en}</p>
      <p>Günlük: <strong>${Number(v.daily_price).toLocaleString('tr-TR')}₺</strong></p>
      <button id="confirmBook" class="btn btn-accent w-100">Rezervasyonu Onayla</button>
      <div id="bookMsg" class="mt-2 small"></div>`;
    bookModal.show();

    document.getElementById('confirmBook').onclick = async () => {
        const fd = new FormData();
        fd.append('vehicle_id', v.id);
        fd.append('pickup_location_id', locId);
        fd.append('start_date', s);
        fd.append('end_date', en);
        fd.append('csrf_token', CSRF);
        const res = await fetch('api/rentals/create.php', { method: 'POST', body: fd });
        const json = await res.json();
        const msg = document.getElementById('bookMsg');
        msg.className = 'mt-2 small ' + (json.success ? 'text-success' : 'text-danger');
        msg.textContent = json.success ? 'Rezervasyon oluşturuldu, ödemeye yönlendiriliyorsunuz…' : json.message;
        if (json.success) setTimeout(() => location.href = 'checkout.php?rental_id=' + json.data.rental_id, 1000);
    };
});

// Initial load
loadVehicles();
