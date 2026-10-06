-- ============================================================
-- PKL Mobile Redesign - Database Migration
-- Jalankan SQL ini di phpMyAdmin atau MySQL CLI
-- Database: umumcsrc_pkl
-- ============================================================

-- Tabel Absensi Peserta
CREATE TABLE IF NOT EXISTS `absensi_peserta` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `tanggal` DATE NOT NULL,
    `jam_masuk` TIME DEFAULT NULL,
    `jam_keluar` TIME DEFAULT NULL,
    `status` ENUM('Hadir','Izin','Sakit','Alpha') DEFAULT 'Hadir',
    `keterangan` TEXT DEFAULT NULL,
    `file_surat` VARCHAR(255) DEFAULT NULL,
    `lat_masuk` DECIMAL(10,8) DEFAULT NULL,
    `lng_masuk` DECIMAL(11,8) DEFAULT NULL,
    `lat_keluar` DECIMAL(10,8) DEFAULT NULL,
    `lng_keluar` DECIMAL(11,8) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unik_absensi` (`username`, `tanggal`),
    KEY `idx_username` (`username`),
    KEY `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Hari Libur / Tanggal Merah
CREATE TABLE IF NOT EXISTS `hari_libur` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tanggal` DATE NOT NULL UNIQUE,
    `keterangan` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
