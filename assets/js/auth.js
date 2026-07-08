// CarRent — login & register (AJAX)
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const msg = document.getElementById('msg');

function showMsg(text, ok) {
    msg.className = 'small mt-3 text-center ' + (ok ? 'text-success' : 'text-danger');
    msg.textContent = text;
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

async function submitForm(form, url, onSuccess) {
    clearErrors(form);
    showMsg('', true);
    const fd = new FormData(form);
    fd.append('csrf_token', CSRF);
    try {
        const res = await fetch(url, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) { onSuccess(json); }
        else {
            showMsg(json.message, false);
            if (json.data?.errors) showFieldErrors(form, json.data.errors);
        }
    } catch (e) {
        showMsg('Bağlantı hatası, tekrar deneyin.', false);
    }
}

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        submitForm(loginForm, 'api/auth/login.php', (json) => {
            showMsg(json.message, true);
            // Strip any leading '/' so the target always resolves relative to the subfolder
            const target = (json.data.redirect || 'index.php').replace(/^\/+/, '');
            setTimeout(() => location.href = target, 800);
        });
    });
}

const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        submitForm(registerForm, 'api/auth/register.php', (json) => {
            showMsg(json.message, true);
            setTimeout(() => location.href = 'login.php', 1200);
        });
    });
}
