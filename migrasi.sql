-- ============================================================
-- PKL DATABASE MIGRATION SCRIPT (TABEL ABSENSI LENGKAP & CONFIG BARU)
-- ============================================================
-- File: migrasi.sql
-- Kegunaan: Jalankan script SQL ini pada phpMyAdmin / MySQL hosting Anda
--           untuk membuat tabel absensi peserta dan fitur konfigurasi baru.
-- ============================================================

-- ------------------------------------------------------------
-- 1. PEMBUATAN TABEL-TABEL (JIKA BELUM ADA)
-- ------------------------------------------------------------

-- Tabel Absensi Peserta (Lengkap dengan Koordinat & Approval Status)
CREATE TABLE IF NOT EXISTS `absensi_peserta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('Hadir','Izin','Sakit','Alpha') DEFAULT 'Hadir',
  `keterangan` text DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `lat_masuk` decimal(10,8) DEFAULT NULL,
  `lng_masuk` decimal(11,8) DEFAULT NULL,
  `lat_keluar` decimal(10,8) DEFAULT NULL,
  `lng_keluar` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('Pending','Disetujui','Ditolak') DEFAULT 'Disetujui',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unik_absensi` (`username`,`tanggal`),
  KEY `idx_username` (`username`),
  KEY `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Pengaturan Absensi (GPS Koordinat & Radius Kantor)
CREATE TABLE IF NOT EXISTS `pengaturan_absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_lat` decimal(10,8) NOT NULL,
  `office_lng` decimal(11,8) NOT NULL,
  `radius_meter` int(11) NOT NULL DEFAULT 100,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Hari Libur Khusus / Tanggal Merah
CREATE TABLE IF NOT EXISTS `hari_libur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Hari Libur Pekan (Libur Rutin Mingguan)
CREATE TABLE IF NOT EXISTS `libur_pekan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hari_index` int(11) NOT NULL,
  `nama_hari` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hari_index` (`hari_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- 2. PENGISIAN DATA AWAL / CONFIG DEFAULT
-- ------------------------------------------------------------

-- Data Radius GPS Default (Koordinat default Monas, silakan diubah via akses Pusat)
INSERT INTO `pengaturan_absensi` (`id`, `office_lat`, `office_lng`, `radius_meter`)
VALUES (1, -6.17539200, 106.82715300, 100)
ON DUPLICATE KEY UPDATE id=id;

-- Libur Pekan Default (Sabtu dan Minggu)
INSERT INTO `libur_pekan` (`hari_index`, `nama_hari`) VALUES 
(0, 'Minggu'),
(6, 'Sabtu')
ON DUPLICATE KEY UPDATE hari_index=hari_index;


-- ------------------------------------------------------------
-- 4. TABEL PENGATURAN TANDA TANGAN SERTIFIKAT
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pengaturan_ttd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_ttd` varchar(255) NOT NULL,
  `jabatan_ttd` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pengaturan_ttd` (`id`, `nama_ttd`, `jabatan_ttd`)
VALUES (1, 'Sukarno', 'Manager Business Support')
ON DUPLICATE KEY UPDATE id=id;
