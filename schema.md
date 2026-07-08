# Akıllı Araç Kiralama & Rezervasyon Sistemi — Mimari & İş Mantığı

> Bu dosya projenin **kalıcı referansıdır**. Tüm geliştirmeler bu şemaya ve iş
> kurallarına (business logic) uygun yapılmalıdır.

## Teknoloji Yığını (Tech Stack)

| Katman        | Teknoloji                          |
| ------------- | ---------------------------------- |
| Frontend      | HTML, CSS, JS, Bootstrap 5         |
| Backend       | PHP + AJAX                         |
| Veritabanı    | MySQL                              |

---

## Veritabanı Şeması

### 1. `users` Tablosu
Sistemdeki kullanıcıları (müşteri ve admin) tutar.

| Kolon          | Tip / Kısıt                                   | Açıklama                                   |
| -------------- | --------------------------------------------- | ------------------------------------------ |
| `id`           | PK, AUTO_INCREMENT                            | Birincil anahtar                           |
| `name_surname` | VARCHAR(150), NOT NULL                        | Ad Soyad                                   |
| `email`        | VARCHAR(190), **UNIQUE**, NOT NULL            | Giriş için benzersiz e-posta               |
| `password`     | VARCHAR(255), NOT NULL                        | **Hash'lenecek** (password_hash)           |
| `birth_date`   | DATE, NOT NULL                                | Yaş kontrolü için doğum tarihi             |
| `license_date` | DATE, NULL                                    | Ehliyet alış tarihi (ehliyet yaşı kontrolü)|
| `role`         | ENUM('customer','admin'), DEFAULT 'customer'  | Kullanıcı rolü                             |
| `created_at`   | TIMESTAMP, DEFAULT CURRENT_TIMESTAMP          | Kayıt tarihi                               |

### 2. `vehicles` Tablosu
Kiralanabilir araç filosu.

| Kolon             | Tip / Kısıt                                          | Açıklama                                        |
| ----------------- | ---------------------------------------------------- | ----------------------------------------------- |
| `id`              | PK, AUTO_INCREMENT                                   | Birincil anahtar                                |
| `brand`           | VARCHAR(100), NOT NULL                               | Marka                                           |
| `model`           | VARCHAR(100), NOT NULL                               | Model                                           |
| `year`            | SMALLINT, NOT NULL                                   | Model yılı                                      |
| `type`            | ENUM('Sedan','SUV','Hatchback'), NOT NULL            | Araç tipi                                       |
| `transmission`    | ENUM('Manual','Automatic'), NOT NULL                 | Vites                                           |
| `fuel_type`       | ENUM('Gasoline','Diesel','Electric'), NOT NULL       | Yakıt tipi                                      |
| `current_km`      | INT UNSIGNED, DEFAULT 0                              | Güncel kilometre                                |
| `min_license_age` | TINYINT UNSIGNED, NOT NULL, DEFAULT 1               | Kiralamak için gereken min. ehliyet yaşı (yıl)  |
| `daily_price`     | DECIMAL(10,2), NOT NULL                              | Günlük kira ücreti                              |
| `status`          | ENUM('Available','Rented','Maintenance') DEFAULT 'Available' | Araç durumu                             |
| `created_at`      | TIMESTAMP, DEFAULT CURRENT_TIMESTAMP                 | Kayıt tarihi                                    |

### 3. `rentals` Tablosu
Kiralama kayıtları (rezervasyonlar).

| Kolon         | Tip / Kısıt                                                       | Açıklama                             |
| ------------- | ---------------------------------------------------------------- | ------------------------------------ |
| `id`          | PK, AUTO_INCREMENT                                               | Birincil anahtar                     |
| `user_id`     | FK → `users(id)`, NOT NULL                                      | Kiralayan müşteri                    |
| `vehicle_id`  | FK → `vehicles(id)`, NOT NULL                                   | Kiralanan araç                       |
| `start_date`  | DATE, NOT NULL                                                  | Kiralama başlangıç tarihi            |
| `end_date`    | DATE, NOT NULL                                                  | Kiralama bitiş tarihi                |
| `start_km`    | INT UNSIGNED, NOT NULL                                          | Kiralama anındaki KM                 |
| `end_km`      | INT UNSIGNED, **NULL**                                         | Teslim anındaki KM (başta boş)       |
| `total_price` | DECIMAL(10,2), NOT NULL                                        | Toplam kira tutarı                   |
| `status`      | ENUM('Pending','Active','Completed','Cancelled') DEFAULT 'Pending' | Kiralama durumu                 |
| `created_at`  | TIMESTAMP, DEFAULT CURRENT_TIMESTAMP                           | Kayıt tarihi                         |

### 4. `payments` Tablosu
Ödeme kayıtları.

| Kolon            | Tip / Kısıt                                            | Açıklama                    |
| ---------------- | ------------------------------------------------------ | --------------------------- |
| `id`             | PK, AUTO_INCREMENT                                     | Birincil anahtar            |
| `rental_id`      | FK → `rentals(id)`, NOT NULL                          | İlgili kiralama             |
| `amount`         | DECIMAL(10,2), NOT NULL                               | Ödenen tutar                |
| `payment_method` | ENUM('Credit Card','Bank Transfer'), NOT NULL         | Ödeme yöntemi               |
| `payment_status` | ENUM('Paid','Refunded','Failed') DEFAULT 'Paid'       | Ödeme durumu                |
| `payment_date`   | TIMESTAMP, DEFAULT CURRENT_TIMESTAMP                  | Ödeme tarihi                |

---

## İlişkiler (Relationships)

- `users (1) ──< (N) rentals` — Bir kullanıcının birden çok kiralaması olabilir.
- `vehicles (1) ──< (N) rentals` — Bir araç birden çok kez kiralanabilir.
- `rentals (1) ──< (N) payments` — Bir kiralamaya birden çok ödeme (ör. ödeme + iade) bağlanabilir.

Referans bütünlüğü (FK) kuralları:
- `rentals.user_id` → `ON DELETE RESTRICT` (kaydı olan kullanıcı silinemez).
- `rentals.vehicle_id` → `ON DELETE RESTRICT` (kaydı olan araç silinemez).
- `payments.rental_id` → `ON DELETE CASCADE` (kiralama silinince ödemeleri de silinir).

---

## Kritik İş Kuralları (Business Logic)

### 1. Ehliyet Yaşı Kontrolü (Backend'de zorunlu)
Müşteri araç kiralarken:
```
ehliyet_süresi_yıl = (CURRENT_DATE - users.license_date) yıl cinsinden
if (ehliyet_süresi_yıl < vehicles.min_license_age)  ->  KİRALAMA ENGELLENİR
```
- `license_date` NULL ise kiralama yapılamaz.
- Bu kontrol **backend PHP tarafında** yapılır; frontend'e güvenilmez.

### 2. Tarih Çakışması Önleme (Overlap / Double-Booking)
Seçilen `[start_date, end_date]` aralığında, ilgili araç için
`rentals` tablosunda **`status IN ('Pending','Active')`** olan bir kayıt varsa
araç **müsait değildir** ve listelenmez/kiralanamaz.

Çakışma koşulu (iki aralık kesişir):
```
existing.start_date <= yeni.end_date  AND  existing.end_date >= yeni.start_date
```

### 3. Yaş Kontrolü (opsiyonel/genişletilebilir)
`birth_date` üzerinden asgari yaş (ör. 18+) kontrolü yapılabilir.

### 4. Fiyat Hesaplama
```
gün_sayısı = DATEDIFF(end_date, start_date) + 1   (veya iş kuralına göre)
total_price = gün_sayısı * vehicles.daily_price
```

### 5. Durum Geçişleri
- **Rental:** `Pending` → `Active` → `Completed` / veya `Cancelled`.
- Kiralama `Active` olunca araç `status = 'Rented'` yapılır.
- Kiralama `Completed`/`Cancelled` olunca araç `status = 'Available'` yapılır (bakımda değilse).
- Teslimde `end_km` girilir ve `vehicles.current_km` güncellenir.

---

## Güvenlik Notları
- Parolalar `password_hash()` / `password_verify()` ile saklanır.
- Tüm sorgular **prepared statements** (PDO/MySQLi) ile yapılır (SQL Injection koruması).
- Rol bazlı yetkilendirme: yalnızca `admin` araç ekleme/düzenleme yapabilir.
