# 🚗 CarRent — Smart Car Rental & Reservation System (Car Rental SaaS)

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla%20%2B%20AJAX-F7DF1E?logo=javascript&logoColor=black)

An enterprise-grade, end-to-end **car rental and reservation platform**.
Includes a customer-facing showcase, an advanced checkout flow, and a fully featured admin panel.
All critical business rules are validated **on the backend** — the frontend is never trusted.

---

## ✨ Features

### Customer Side
- 🔍 **Smart vehicle search** — date-range overlap (double-booking) check: unavailable vehicles are never listed
- 🎛️ **Instant filtering** — price sorting, vehicle class, transmission, fuel type (AJAX, no page reload)
- 📍 **Pickup location selection** — airport / city offices
- 💳 **Advanced checkout** — invoice breakdown (days × price + 20% VAT), masked card form, Visa/Mastercard detection
- 📜 **Rental terms panel** — fuel, pickup, cancellation and traffic-fine policies; payment is blocked until accepted
- 🧾 **Printable booking receipt** — print-friendly digital receipt after payment
- ⏰ **24-hour cancellation rule** — no cancellation within 24 hours of the pickup time
- 👤 **Profile management** — update personal details + secure password change (national ID is immutable)

### Business Rules (enforced on the backend)
- 🪪 **License age check** — rental is rejected if the license age is below the vehicle's `min_license_age`
- 🏆 **Luxury-class driver validation** — daily price > 2000₺ or SUV/Sedan: requires age ≥ 25 and a license ≥ 3 years old
- 🔒 **Race-condition protection** — booking/payment run atomically with transactions + `SELECT ... FOR UPDATE`
- 🚫 **Admins cannot rent** — hidden in the UI and rejected with 403 by the API

### Admin Panel
- 📊 Overview: total revenue, vehicle/customer/rental metrics
- 🚙 Vehicle management: create/edit/delete + **secure photo upload** (real MIME validation, unique file names)
- 📋 Rental management: status filter, **vehicle return** (`end_km` → vehicle mileage updated), cancellation
- 👥 Customer management: listing, single delete, one-click test-data cleanup

### Security
- `password_hash()` / `password_verify()` (bcrypt) with automatic rehash
- **PDO prepared statements** everywhere (SQL injection protection)
- **CSRF** token validation (`hash_equals`), session fixation protection (`session_regenerate_id`)
- User enumeration prevention (generic message on login errors)
- Two-layer role-based authorization: page guards + API `require_admin()`
- Card details are **never sent to or stored on** the server

---

## 🛠️ Tech Stack

| Layer      | Technology                                          |
|------------|-----------------------------------------------------|
| Frontend   | HTML5, CSS3, Bootstrap 5.3, Vanilla JS (Fetch/AJAX) |
| Backend    | PHP 8.x (PDO)                                       |
| Database   | MySQL 8.x (InnoDB, FK, CHECK)                       |
| Server     | Apache (XAMPP)                                      |

---

## 🚀 Setup (XAMPP)

### 1. Requirements
- [XAMPP](https://www.apachefriends.org/) (bundles PHP 8+ and MySQL)

### 2. Place the project
Copy the project folder into XAMPP's `htdocs` directory:

```
C:\xampp\htdocs\CarRent   (or D:\xampp\htdocs\CarRent)
```

> If you use a different folder name, update the `BASE_URL` value in `config/config.php`.

### 3. Set up the database
1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin`.
3. Use the **Import** tab to import **`database.sql`** from the project root.
   - This creates the `car_rental` database, all tables (users, vehicles, locations, rentals, payments), relationships, and seed data.

### 4. Configuration
```bash
# inside the config folder:
config.example.php  →  config.php  (copy it)
```
Enter your DB username/password in `config.php` (XAMPP default: `root` / empty password — usually no change needed).

### 5. Create an admin account
The password hash of the seed admin in `database.sql` is a placeholder. To get a real admin:

1. Register a normal account at `http://localhost/CarRent/register.php`.
2. Run this query in phpMyAdmin:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
   ```
3. Log in again → you will be redirected to the admin panel automatically.

### 6. Run 🎉
```
http://localhost/CarRent/
```

---

## 📁 Project Structure

```
CarRent/
├── index.php               # Customer showcase (search + filters + booking)
├── login.php / register.php
├── dashboard.php           # My rentals (pay/cancel)
├── checkout.php            # Invoice summary + card form + terms
├── confirmation.php        # Printable booking receipt
├── profile.php             # Profile & password management
├── admin/
│   ├── dashboard.php       # Metric cards
│   ├── vehicles.php        # Vehicle CRUD + photos
│   ├── rentals.php         # All rentals + return/cancel
│   └── customers.php       # Customer management
├── api/                    # JSON AJAX endpoints
│   ├── auth/               # login, register, logout
│   ├── vehicles/           # list (overlap filter + sorting), detail
│   ├── rentals/            # create, list, cancel (24h rule), return
│   ├── payments/           # process (atomic transaction)
│   ├── locations/          # list
│   ├── users/              # update_profile (profile + password)
│   └── admin/              # stats, vehicle CRUD, user management
├── includes/
│   ├── functions.php       # Session, CSRF, guards, business rules
│   ├── upload.php          # Secure image upload
│   └── admin_nav.php       # Shared admin navigation
├── config/
│   ├── config.example.php  # Template (committed)
│   └── config.php          # Real settings (gitignored)
├── assets/                 # CSS, JS, images
├── uploads/                # Vehicle photos (gitignored)
├── database.sql            # Full schema + seed data
└── schema.md               # Architecture & business rules document
```

---

## 🗄️ Database Schema (Summary)

- **users** — customer/admin; `tc_no` (UNIQUE national ID), `birth_date`, `license_date` (used by business rules)
- **vehicles** — fleet; `min_license_age`, `daily_price`, `status`, `image_path`
- **locations** — pickup offices
- **rentals** — bookings; `start_km`/`end_km`, date-overlap index, FK `RESTRICT`
- **payments** — payments; FK `CASCADE`

For the detailed schema and all business rules, see: [`schema.md`](schema.md)

---

## 📄 License
This project is for portfolio/educational purposes.
