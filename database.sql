-- Active: 1783363475926@@127.0.0.1@3306@mysql
-- Active: 1783363475926@@127.0.0.1@3306@mysql
-- =============================================================
--  Akıllı Araç Kiralama & Rezervasyon Sistemi (Car Rental SaaS)
--  MySQL Şeması — schema.md ile birebir uyumludur.
--  Karakter seti: utf8mb4 / InnoDB (FK desteği için)
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `car_rental`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `car_rental`;

-- -------------------------------------------------------------
-- 1) users
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_surname` VARCHAR(150) NOT NULL,
    `email`        VARCHAR(190) NOT NULL,
    `tc_no`        CHAR(11) NULL,                  -- T.C. Kimlik No (benzersiz)
    `password`     VARCHAR(255) NOT NULL,          -- password_hash() ile saklanır
    `birth_date`   DATE NOT NULL,                  -- yaş kontrolü
    `license_date` DATE NULL,                      -- ehliyet yaşı kontrolü
    `role`         ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    UNIQUE KEY `uq_users_tc` (`tc_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2) vehicles
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand`           VARCHAR(100) NOT NULL,
    `model`           VARCHAR(100) NOT NULL,
    `year`            SMALLINT UNSIGNED NOT NULL,
    `type`            ENUM('Sedan','SUV','Hatchback') NOT NULL,
    `transmission`    ENUM('Manual','Automatic') NOT NULL,
    `fuel_type`       ENUM('Gasoline','Diesel','Electric') NOT NULL,
    `current_km`      INT UNSIGNED NOT NULL DEFAULT 0,
    `min_license_age` TINYINT UNSIGNED NOT NULL DEFAULT 1,   -- gereken min ehliyet yaşı (yıl)
    `daily_price`     DECIMAL(10,2) NOT NULL,
    `status`          ENUM('Available','Rented','Maintenance') NOT NULL DEFAULT 'Available',
    `image_path`      VARCHAR(255) DEFAULT NULL,             -- araç fotoğrafı yolu
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_vehicles_status` (`status`),
    KEY `idx_vehicles_type`   (`type`),
    CONSTRAINT `chk_vehicles_price` CHECK (`daily_price` >= 0),
    CONSTRAINT `chk_vehicles_year`  CHECK (`year` BETWEEN 1950 AND 2100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3) locations (ofis / teslim noktaları)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
    `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4) rentals
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `rentals`;
CREATE TABLE `rentals` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`            INT UNSIGNED NOT NULL,
    `vehicle_id`         INT UNSIGNED NOT NULL,
    `pickup_location_id` INT UNSIGNED NULL,          -- teslim alma noktası
    `start_date`  DATE NOT NULL,
    `end_date`    DATE NOT NULL,
    `start_km`    INT UNSIGNED NOT NULL,           -- kiralama anındaki KM
    `end_km`      INT UNSIGNED NULL,               -- teslim anındaki KM (başta boş)
    `total_price` DECIMAL(10,2) NOT NULL,
    `status`      ENUM('Pending','Active','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rentals_user`    (`user_id`),
    KEY `idx_rentals_vehicle` (`vehicle_id`),
    KEY `idx_rentals_status`  (`status`),
    -- Tarih çakışması sorgularını hızlandırmak için:
    KEY `idx_rentals_overlap` (`vehicle_id`, `status`, `start_date`, `end_date`),
    CONSTRAINT `fk_rentals_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_rentals_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_rentals_location`
        FOREIGN KEY (`pickup_location_id`) REFERENCES `locations` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_rentals_dates` CHECK (`end_date` >= `start_date`),
    CONSTRAINT `chk_rentals_price` CHECK (`total_price` >= 0),
    CONSTRAINT `chk_rentals_km`    CHECK (`end_km` IS NULL OR `end_km` >= `start_km`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5) payments
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rental_id`      INT UNSIGNED NOT NULL,
    `amount`         DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('Credit Card','Bank Transfer') NOT NULL,
    `payment_status` ENUM('Paid','Refunded','Failed') NOT NULL DEFAULT 'Paid',
    `payment_date`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payments_rental` (`rental_id`),
    KEY `idx_payments_status` (`payment_status`),
    CONSTRAINT `fk_payments_rental`
        FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `chk_payments_amount` CHECK (`amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
--  MIGRATION: mevcut kurulumlar için image_path kolonu ekleme
--  (Tabloyu sıfırdan kuranlar için yukarıda zaten tanımlı;
--   var olan bir DB'de ise aşağıdaki satırı çalıştırın.)
-- =============================================================
-- ALTER TABLE `vehicles`
--     ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL AFTER `status`;

-- =============================================================
--  ÖRNEK / BAŞLANGIÇ VERİLERİ (Seed Data)
--  Not: parola hash'leri PHP password_hash() ile üretilmelidir.
--  Aşağıdaki hash 'Admin123!' parolasına örnektir (bcrypt).
-- =============================================================

INSERT INTO `users` (`name_surname`, `email`, `password`, `birth_date`, `license_date`, `role`) VALUES
('Sistem Yöneticisi', 'admin@carrent.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlA0m6f6vQ8b3o9F1s2G3h4I5j6K7', '1990-01-01', '2010-01-01', 'admin'),
('Ahmet Yılmaz',      'ahmet@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlA0m6f6vQ8b3o9F1s2G3h4I5j6K7', '1995-05-20', '2015-06-15', 'customer');

INSERT INTO `locations` (`name`) VALUES
('İstanbul Havalimanı (IST)'),
('Sabiha Gökçen HL (SAW)'),
('Kadıköy Ofis'),
('Taksim Ofis');

INSERT INTO `vehicles` (`brand`, `model`, `year`, `type`, `transmission`, `fuel_type`, `current_km`, `min_license_age`, `daily_price`, `status`) VALUES
('Toyota',   'Corolla', 2022, 'Sedan',     'Automatic', 'Gasoline', 15000, 1, 850.00,  'Available'),
('Volkswagen','Golf',   2021, 'Hatchback', 'Manual',    'Diesel',   30000, 2, 750.00,  'Available'),
('BMW',      'X5',      2023, 'SUV',       'Automatic', 'Diesel',   8000,  5, 2500.00, 'Available'),
('Tesla',    'Model 3', 2023, 'Sedan',     'Automatic', 'Electric', 5000,  3, 2000.00, 'Available');

-- =============================================================
--  YARDIMCI SORGULAR (Referans amaçlı — uygulamada prepared statement kullanın)
-- =============================================================

-- (A) Tarih çakışması olmayan (müsait) araçları listele:
--   :start ve :end kullanıcının seçtiği tarihlerdir.
--
-- SELECT v.*
-- FROM vehicles v
-- WHERE v.status = 'Available'
--   AND v.id NOT IN (
--       SELECT r.vehicle_id
--       FROM rentals r
--       WHERE r.status IN ('Pending','Active')
--         AND r.start_date <= :end
--         AND r.end_date   >= :start
--   );

-- (B) Ehliyet yaşı kontrolü (backend'de de tekrar doğrulanmalı):
--
-- SELECT
--   TIMESTAMPDIFF(YEAR, u.license_date, CURDATE()) AS license_age,
--   v.min_license_age,
--   (u.license_date IS NOT NULL
--    AND TIMESTAMPDIFF(YEAR, u.license_date, CURDATE()) >= v.min_license_age) AS is_eligible
-- FROM users u CROSS JOIN vehicles v
-- WHERE u.id = :user_id AND v.id = :vehicle_id;
