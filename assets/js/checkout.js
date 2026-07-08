// CarRent — advanced payment (checkout)
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const rentalId = window.CHECKOUT.rentalId;

const cardNumber = document.getElementById('cardNumber');
const cardName   = document.getElementById('cardName');
const cardExpiry = document.getElementById('cardExpiry');
const cardCvv    = document.getElementById('cardCvv');
const cardBrand  = document.getElementById('cardBrand');
const agree      = document.getElementById('agreeTerms');
const payBtn     = document.getElementById('payBtn');
const payMsg     = document.getElementById('payMsg');

// Card number masking (XXXX-XXXX-XXXX-XXXX)
cardNumber.addEventListener('input', () => {
    let d = cardNumber.value.replace(/\D/g, '').slice(0, 16);
    cardNumber.value = d.replace(/(.{4})/g, '$1-').replace(/-$/, '');
    detectBrand(d);
    validate();
});

// Card brand detection (Visa: 4, Mastercard: 51-55 / 2221-2720)
function detectBrand(digits) {
    let icon = '<i class="bi bi-credit-card"></i>', cls = 'text-muted';
    if (/^4/.test(digits)) { icon = '<i class="bi bi-credit-card-2-front"></i> VISA'; cls = 'text-primary fw-bold'; }
    else if (/^(5[1-5]|22[2-9]|2[3-7])/.test(digits)) { icon = '<i class="bi bi-credit-card-2-front"></i> MASTERCARD'; cls = 'text-danger fw-bold'; }
    cardBrand.className = 'fs-6 ' + cls;
    cardBrand.innerHTML = icon;
}

// Expiry MM/YY
cardExpiry.addEventListener('input', () => {
    let d = cardExpiry.value.replace(/\D/g, '').slice(0, 4);
    if (d.length >= 3) d = d.slice(0, 2) + '/' + d.slice(2);
    cardExpiry.value = d;
    validate();
});

cardCvv.addEventListener('input', () => { cardCvv.value = cardCvv.value.replace(/\D/g, ''); validate(); });
cardName.addEventListener('input', validate);
agree.addEventListener('change', validate);

// Are all fields valid? (16 digits, name, MM/YY, 3-4 digit CVV, terms accepted)
function validate() {
    const numOk = cardNumber.value.replace(/\D/g, '').length === 16;
    const nameOk = cardName.value.trim().length >= 3;
    const expOk = /^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry.value);
    const cvvOk = /^\d{3,4}$/.test(cardCvv.value);
    payBtn.disabled = !(numOk && nameOk && expOk && cvvOk && agree.checked);
    return { numOk, nameOk, expOk, cvvOk };
}

// Payment
payBtn.addEventListener('click', async () => {
    payMsg.textContent = '';
    payBtn.disabled = true;
    const original = payBtn.innerHTML;
    payBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> İşleniyor…';

    const fd = new FormData();
    fd.append('rental_id', rentalId);
    fd.append('payment_method', 'Credit Card');
    fd.append('csrf_token', CSRF);
    // Note: card details are NEVER sent to the server or stored (PCI compliance).

    try {
        const json = await (await fetch('api/payments/process.php', { method: 'POST', body: fd })).json();
        if (json.success) {
            payMsg.className = 'small mt-2 text-center text-success';
            payMsg.textContent = 'Ödeme başarılı, yönlendiriliyorsunuz…';
            setTimeout(() => location.href = 'confirmation.php?rental_id=' + rentalId, 900);
        } else {
            payMsg.className = 'small mt-2 text-center text-danger';
            payMsg.textContent = json.message;
            payBtn.disabled = false;
            payBtn.innerHTML = original;
        }
    } catch (e) {
        payMsg.className = 'small mt-2 text-center text-danger';
        payMsg.textContent = 'Bağlantı hatası, tekrar deneyin.';
        payBtn.disabled = false;
        payBtn.innerHTML = original;
    }
});
