-- =====================================================================
-- GEDUNG INN — Skema Database MySQL/MariaDB
-- Tugas Besar Web Programming
--
-- Cara pakai (XAMPP / phpMyAdmin):
--   1. Buka phpMyAdmin  ->  tab "Import"  ->  pilih file ini  ->  Go
--   2. Atau lewat terminal:  mysql -u root < database/schema.sql
--
-- File ini membuat database `gedung_inn`, seluruh tabel, dan data contoh
-- 8 gedung (lengkap dengan foto, fasilitas, jenis acara, layanan tambahan).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `gedung_inn`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gedung_inn`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `venue_jenis`;
DROP TABLE IF EXISTS `venue_fasilitas`;
DROP TABLE IF EXISTS `venue_foto`;
DROP TABLE IF EXISTS `layanan`;
DROP TABLE IF EXISTS `venues`;
DROP TABLE IF EXISTS `jenis_acara`;
DROP TABLE IF EXISTS `fasilitas`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. Akun admin
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`          VARCHAR(80)  NOT NULL,
  `email`         VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Master data (dikelola lewat menu "Master data")
-- ---------------------------------------------------------------------
CREATE TABLE `kategori` (
  `id`     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`   VARCHAR(60) NOT NULL,
  `urutan` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kategori_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fasilitas` (
  `id`     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`   VARCHAR(60) NOT NULL,
  `urutan` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fasilitas_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jenis_acara` (
  `id`     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`   VARCHAR(60) NOT NULL,
  `urutan` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jenis_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Gedung (tabel utama)
-- ---------------------------------------------------------------------
CREATE TABLE `venues` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`          VARCHAR(120) NOT NULL,
  `kategori_id`   TINYINT UNSIGNED NOT NULL,
  `kecamatan`     VARCHAR(60)  NOT NULL,
  `alamat`        VARCHAR(200) NOT NULL,
  `kapasitas`     INT UNSIGNED NOT NULL,
  `harga_per_jam` INT UNSIGNED NOT NULL,
  `minimal_jam`   TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `kontak_nama`   VARCHAR(120) NOT NULL DEFAULT '',
  `kontak_wa`     VARCHAR(20)  NOT NULL DEFAULT '',
  `website_resmi` VARCHAR(255) NOT NULL DEFAULT '',
  `kontak_demo`   TINYINT(1)   NOT NULL DEFAULT 1,
  `deskripsi`     TEXT         NOT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_venues_kategori`  (`kategori_id`),
  KEY `idx_venues_kecamatan` (`kecamatan`),
  KEY `idx_venues_harga`     (`harga_per_jam`),
  KEY `idx_venues_kapasitas` (`kapasitas`),
  CONSTRAINT `fk_venues_kategori` FOREIGN KEY (`kategori_id`)
    REFERENCES `kategori` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `venue_foto` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `venue_id` INT UNSIGNED NOT NULL,
  `url`      VARCHAR(255) NOT NULL,
  `urutan`   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_foto_urutan` (`venue_id`, `urutan`),
  CONSTRAINT `fk_foto_venue` FOREIGN KEY (`venue_id`)
    REFERENCES `venues` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `layanan` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `venue_id` INT UNSIGNED NOT NULL,
  `nama`     VARCHAR(120) NOT NULL,
  `harga`    INT UNSIGNED NOT NULL DEFAULT 0,
  `satuan`   ENUM('per_jam','sekali') NOT NULL DEFAULT 'per_jam',
  `urutan`   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_layanan_venue` (`venue_id`),
  CONSTRAINT `fk_layanan_venue` FOREIGN KEY (`venue_id`)
    REFERENCES `venues` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Relasi many-to-many
-- ---------------------------------------------------------------------
CREATE TABLE `venue_fasilitas` (
  `venue_id`     INT UNSIGNED NOT NULL,
  `fasilitas_id` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`venue_id`, `fasilitas_id`),
  KEY `idx_vf_fasilitas` (`fasilitas_id`),
  CONSTRAINT `fk_vf_venue` FOREIGN KEY (`venue_id`)
    REFERENCES `venues` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_vf_fasilitas` FOREIGN KEY (`fasilitas_id`)
    REFERENCES `fasilitas` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `venue_jenis` (
  `venue_id`      INT UNSIGNED NOT NULL,
  `jenis_acara_id` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`venue_id`, `jenis_acara_id`),
  KEY `idx_vj_jenis` (`jenis_acara_id`),
  CONSTRAINT `fk_vj_venue` FOREIGN KEY (`venue_id`)
    REFERENCES `venues` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_vj_jenis` FOREIGN KEY (`jenis_acara_id`)
    REFERENCES `jenis_acara` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. DATA CONTOH
-- =====================================================================

-- Akun admin demo: admin@gedunginn.id / admin123
INSERT INTO `users` (`id`, `nama`, `email`, `password_hash`) VALUES
  (1, 'Admin Gedung Inn', 'admin@gedunginn.id',
   '$2y$10$iRx7s6LXhaPNrlMFILdIje5tU4VRxcU/6ei2u5vhh3bC7NqpUQGEa');

INSERT INTO `kategori` (`id`, `nama`, `urutan`) VALUES
  (1, 'Convention Hall', 1),
  (2, 'Aula', 2),
  (3, 'Open Space', 3);

INSERT INTO `fasilitas` (`id`, `nama`, `urutan`) VALUES
  (1, 'AC', 1),
  (2, 'Parkir', 2),
  (3, 'Mushola', 3),
  (4, 'Ruang Rias', 4),
  (5, 'Toilet', 5),
  (6, 'Wifi', 6);

INSERT INTO `jenis_acara` (`id`, `nama`, `urutan`) VALUES
  (1, 'Pernikahan', 1),
  (2, 'Seminar', 2),
  (3, 'Wisuda', 3),
  (4, 'Konser', 4),
  (5, 'Pameran', 5),
  (6, 'Komunitas', 6);

INSERT INTO `venues`
  (`id`, `nama`, `kategori_id`, `kecamatan`, `alamat`, `kapasitas`, `harga_per_jam`,
   `minimal_jam`, `kontak_nama`, `kontak_wa`, `website_resmi`, `kontak_demo`, `deskripsi`)
VALUES
  (1, 'Graha Manggala Siliwangi', 1, 'Bandung Wetan',
   'Jl. Aceh No. 66, Bandung Wetan, Kota Bandung', 1500, 1500000, 4,
   'Pengelola Graha Manggala Siliwangi (contoh)', '6280000000001',
   'https://example.com/?gedung=Graha%20Manggala%20Siliwangi', 1,
   'Graha Manggala Siliwangi adalah pilihan ruang serbaguna di Bandung Wetan, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.'),

  (2, 'Aula Balai Sartika', 2, 'Buahbatu',
   'Jl. Buah Batu No. 100, Buahbatu, Kota Bandung', 500, 750000, 3,
   'Pengelola Aula Balai Sartika (contoh)', '6280000000002',
   'https://example.com/?gedung=Aula%20Balai%20Sartika', 1,
   'Aula Balai Sartika adalah pilihan ruang serbaguna di Buahbatu, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.'),

  (3, 'Sasana Budaya Ganesha', 1, 'Coblong',
   'Jl. Tamansari No. 73, Coblong, Kota Bandung', 2000, 3000000, 6,
   'Pengelola Sasana Budaya Ganesha (contoh)', '6280000000003',
   'https://example.com/?gedung=Sasana%20Budaya%20Ganesha', 1,
   'Sasana Budaya Ganesha adalah pilihan ruang serbaguna di Coblong, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.'),

  (4, 'Taman Cikapundung', 3, 'Cidadap',
   'Jl. Ciumbuleuit No. 42, Cidadap, Kota Bandung', 300, 500000, 2,
   'Pengelola Taman Cikapundung (contoh)', '6280000000004',
   'https://example.com/?gedung=Taman%20Cikapundung', 1,
   'Taman Cikapundung adalah pilihan ruang serbaguna di Cidadap, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.'),

  (5, 'Graha Sukajadi', 2, 'Sukajadi',
   'Jl. Sukajadi No. 118, Sukajadi, Kota Bandung', 200, 450000, 2,
   'Pengelola Graha Sukajadi (contoh)', '6280000000005',
   'https://example.com/?gedung=Graha%20Sukajadi', 1,
   'Graha Sukajadi adalah pilihan ruang serbaguna di Sukajadi, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.'),

  (6, 'Bale Pasundan', 2, 'Lengkong',
   'Jl. Lengkong Besar No. 62, Lengkong, Kota Bandung', 100, 300000, 2,
   'Pengelola Bale Pasundan (contoh)', '6280000000006',
   'https://example.com/?gedung=Bale%20Pasundan', 1,
   'Bale Pasundan adalah pilihan ruang serbaguna di Lengkong, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.'),

  (7, 'The Green Forest', 3, 'Cidadap',
   'Jl. Sersan Bajuri No. 102, Cidadap, Kota Bandung', 600, 1200000, 4,
   'Pengelola The Green Forest (contoh)', '6280000000007',
   'https://example.com/?gedung=The%20Green%20Forest', 1,
   'The Green Forest adalah pilihan ruang serbaguna di Cidadap, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.'),

  (8, 'Bandung Convention Centre', 1, 'Sukajadi',
   'Jl. Soekarno Hatta No. 354, Sukajadi, Kota Bandung', 1000, 2000000, 4,
   'Pengelola Bandung Convention Centre (contoh)', '6280000000008',
   'https://example.com/?gedung=Bandung%20Convention%20Centre', 1,
   'Bandung Convention Centre adalah pilihan ruang serbaguna di Sukajadi, Bandung, untuk momen yang berkesan. Dengan ruangan yang nyaman, akses strategis, dan fasilitas lengkap, gedung ini siap mendukung acara Anda, mulai dari pertemuan hingga perayaan bersama orang terdekat. Tim pengelola siap membantu kebutuhan acara Anda.');

-- 4 foto per gedung, dirotasi dari 8 foto contoh
INSERT INTO `venue_foto` (`venue_id`, `url`, `urutan`)
SELECT v.id, CONCAT('venue-', (((v.id - 1) + k.n) % 8) + 1, '.jpg'), k.n + 1
FROM `venues` v
CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) k;

-- Layanan tambahan berbayar (sama untuk semua gedung contoh)
INSERT INTO `layanan` (`venue_id`, `nama`, `harga`, `satuan`, `urutan`)
SELECT v.id, l.nama, l.harga, l.satuan, l.urutan
FROM `venues` v
CROSS JOIN (
  SELECT 'Sound System' AS nama, 150000 AS harga, 'per_jam' AS satuan, 1 AS urutan
  UNION ALL SELECT 'Proyektor', 100000, 'per_jam', 2
  UNION ALL SELECT 'Biaya Kebersihan', 250000, 'sekali', 3
  UNION ALL SELECT 'Tambahan Kursi', 200000, 'sekali', 4
) l;

-- Fasilitas: gedung tertutup dapat 6 fasilitas, Open Space hanya 4
INSERT INTO `venue_fasilitas` (`venue_id`, `fasilitas_id`)
SELECT v.id, f.id
FROM `venues` v
JOIN `fasilitas` f ON f.nama IN ('AC', 'Parkir', 'Mushola', 'Ruang Rias', 'Toilet', 'Wifi')
WHERE v.kategori_id <> 3;

INSERT INTO `venue_fasilitas` (`venue_id`, `fasilitas_id`)
SELECT v.id, f.id
FROM `venues` v
JOIN `fasilitas` f ON f.nama IN ('Parkir', 'Mushola', 'Toilet', 'Wifi')
WHERE v.kategori_id = 3;

-- Jenis acara mengikuti pola contoh: (id-1) % 3
INSERT INTO `venue_jenis` (`venue_id`, `jenis_acara_id`)
SELECT v.id, j.id
FROM `venues` v
JOIN `jenis_acara` j ON j.nama IN ('Pernikahan', 'Seminar', 'Pameran')
WHERE (v.id - 1) % 3 = 0;

INSERT INTO `venue_jenis` (`venue_id`, `jenis_acara_id`)
SELECT v.id, j.id
FROM `venues` v
JOIN `jenis_acara` j ON j.nama IN ('Pernikahan', 'Wisuda', 'Komunitas')
WHERE (v.id - 1) % 3 = 1;

INSERT INTO `venue_jenis` (`venue_id`, `jenis_acara_id`)
SELECT v.id, j.id
FROM `venues` v
JOIN `jenis_acara` j ON j.nama IN ('Seminar', 'Konser', 'Wisuda')
WHERE (v.id - 1) % 3 = 2;

-- Selesai. Cek cepat:
--   SELECT COUNT(*) FROM venues;      -- 8
--   SELECT COUNT(*) FROM venue_foto;  -- 32
--   SELECT COUNT(*) FROM layanan;     -- 32
