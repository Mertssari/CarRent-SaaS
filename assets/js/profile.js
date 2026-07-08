// CarRent — profile management (AJAX)
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const toastEl = document.getElementById('appToast');
const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

function showToast(msg, ok) {
    toastEl.className = 'toast align-items-center border-0 text-white ' + (ok ? 'bg-success' : 'bg-danger');
    document.getElementById('toastBody').textContent = msg;
    toast.show();
}

function clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

function showFieldErrors(form, errors = {}) {
    for (const [field, text] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${field}"]`);
        const fb = form.querySelector(`[data-err="${field}"]`);
        if (input) input.classList.add('is-invalid');
        if (fb) fb.textContent = text;
    }
}

async function submit(form, action, onSuccess) {
    clearErrors(form);
    const fd = new FormData(form);
    fd.append('action', action);
    fd.append('csrf_token', CSRF);
    try {
        const json = await (await fetch('api/users/update_profile.php', { method: 'POST', body: fd })).json();
        showToast(json.message, json.success);
        if (json.success) onSuccess?.(json);
        else if (json.data?.errors) showFieldErrors(form, json.data.errors);
    } catch (e) {
        showToast('Bağlantı hatası, tekrar deneyin.', false);
    }
}

// Profile details
document.getElementById('profileForm').addEventListener('submit', (e) => {
    e.preventDefault();
    submit(e.target, 'profile', (json) => {
        // Refresh the name shown in the navbar immediately
        document.getElementById('navName').textContent = json.data.user.name_surname;
    });
});

// Change password
document.getElementById('passwordForm').addEventListener('submit', (e) => {
    e.preventDefault();
    submit(e.target, 'password', () => e.target.reset());
});
