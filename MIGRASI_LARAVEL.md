# Migrasi PKL / UmumCSR — PHP Native → Laravel

Dokumen ini adalah rencana + checklist migrasi. File native **tidak dihapus**, dipindahkan ke `legacy/` sebagai referensi/backup.

## Keputusan
- **Lokasi:** in-place di `C:\laragon\www\pkl-umumcsr`; file native → `legacy/`.
- **URL lama:** path `.php` lama (terutama `/ceklogin/verifikasi.php?token=...` yang tercetak di QR sertifikat) dipertahankan sebagai route Laravel.
- **Auth:** Laravel Auth (guard `web`, tabel `users` existing, kolom `role`) + middleware `role:peserta|sdm|manager`.
- **Database:** `umumcsrc_pkl` dipakai langsung (read/write). **Tidak ada migration yang mengubah/menghapus tabel existing.** Backup `.sql` dibuat sebelum mulai.
- **Library:** `phpoffice/phpspreadsheet`, `chillerlan/php-qrcode`, FPDF lokal (`legacy/sdm/fpdf`).
- **Catatan:** tabel `tahun_aktif` tidak ada di DB dan halaman `pengaturan/tahun_aktif` tidak ada di menu (dormant). Tidak dibuatkan tabel.

## Struktur Data (existing, dipakai apa adanya)
| Tabel | PK | Kolom penting |
|---|---|---|
| `users` | id (AI) | username, nama, password (bcrypt), role, created_at |
| `peserta` | id (AI) | username, nama, status_peserta, asal_sekolah, jurusan, tgl_masuk, tgl_keluar, bidang_id, unit, status_magang, keterangan |
| `bidang` | id (AI) | bidang |
| `laporan_magang` | id (AI) | username, file_laporan, nama_file, ukuran_file, sdm_status, sdm_reviewed_by, sdm_tgl_review, sdm_keterangan_tolak, manager_status, manager_reviewed_by, manager_tgl_review, manager_keterangan_tolak, status, keterangan_tolak, tgl_upload, tgl_review, reviewed_by — FK username → peserta |
| `sertifikat_magang` | id (AI) | username, laporan_id, nomor_surat, qr_token, file_sertifikat, digital_file, nama_file_scan, ukuran_file_scan, status, tgl_cetak, tgl_upload_scan, manager_approved_at, generated_at, uploaded_by |
| `absensi_peserta` | id (AI) | username, tanggal, jam_masuk, jam_keluar, status, keterangan, file_surat, lat/lng masuk/keluar, created_at, approval_status |
| `pengaturan_absensi` | id (AI) | office_lat, office_lng, radius_meter, updated_at |
| `pengaturan_ttd` | id (AI) | nama_ttd, jabatan_ttd, updated_at |
| `hari_libur` | id (AI) | tanggal (unique), keterangan, created_at |
| `libur_pekan` | id (AI) | hari_index (unique), nama_hari |
| `dokumen` | id (AI) | nama_dokumen, file_dokumen, tipe_file, created_at |
| `nomor` | id (NO AI) | nomor_surat |
| `karyawan` | nid | nama, no_hp (tak dipakai fitur teranalisis) |
| `user_state` | no_hp | state, judul, periode (bot WA, tak dipakai fitur teranalisis) |

## Mapping Native → Laravel

### Root / Publik
| Native | Controller | View | Route |
|---|---|---|---|
| `index.php` (login) | `AuthController` | `auth/login` | `GET /login`, `POST /login` |
| `conn/conn.php` | — | — | `.env` + `config/database.php` |
| `ceklogin/index.php` | `CekLoginController@index` | `ceklogin.index` | `GET /ceklogin` |
| `ceklogin/search_peserta.php` | `CekLoginController@search` (JSON) | — | `GET /ceklogin/search_peserta.php` |
| `ceklogin/get_peserta.php` | `CekLoginController@getPeserta` (JSON) | — | `GET /ceklogin/get_peserta.php` |
| `ceklogin/verifikasi.php` | `VerifikasiController@show` | `ceklogin.verifikasi` | `GET /ceklogin/verifikasi.php` |
| `cekpeserta/index.php` | `CekPesertaController@index`/`@ubahStatus` | `cekpeserta.index` | `GET /cekpeserta`, `POST /cekpeserta` |

### Peserta (middleware `role:peserta`) — satu controller `PesertaController`
| Native | Controller method | View | Route |
|---|---|---|---|
| `peserta/index.php` | (layout) | `layouts.peserta` | — |
| `peserta/home.php` | `@home` | `peserta.home` | `GET /peserta` |
| `peserta/absensi.php` | `@absensi` | `peserta.absensi` | `GET /peserta/absensi` |
| `peserta/proses_absensi.php` | `@absensiProses` | JSON | `GET/POST /peserta/absensi/proses` |
| `peserta/upload_laporan.php` | `@laporan` / `@laporanStore` | `peserta.upload_laporan` | `GET/POST /peserta/laporan` |
| `peserta/referensi_laporan.php` | `@referensi` | `peserta.referensi_laporan` | `GET /peserta/laporan/referensi` |
| `peserta/sertifikat.php` | `@sertifikat` | `peserta.sertifikat` | `GET /peserta/sertifikat` |
| `peserta/dokumen.php` | `@dokumen` | `peserta.dokumen` | `GET /peserta/dokumen` |
| `peserta/profile.php` | `@profile` / `@profileUpdate` | `peserta.profile` | `GET/POST /peserta/profile` |
| `peserta/kontak.php` | `@kontak` | `peserta.kontak` | `GET /peserta/kontak` |
| `peserta/logout.php` | `AuthController@logout` | — | `GET/POST /logout` |

### SDM (middleware `role:sdm`) — route file per fitur di `routes/sdm/*.php` (auto-load via `bootstrap/app.php`)
| Native | Controller | View | Route |
|---|---|---|---|
| `sdm/index.php` | (layout) | `layouts.sdm` | — |
| `sdm/home.php` | `Sdm\HomeController@index` | `sdm.pages.home` | `GET /sdm` (`sdm.dashboard`) |
| `sdm/approval_laporan.php` | `Sdm\ApprovalLaporanController@{index,approve,reject}` | `sdm.pages.approval_laporan` | `/sdm/approval-laporan` |
| `sdm/approval_laporan_manager.php` | `Sdm\ApprovalManagerController@index` | `sdm.pages.approval_laporan_manager` | `/sdm/approval-manager` |
| `sdm/approval_absensi.php` | `Sdm\ApprovalAbsensiController@{index,approve,reject}` | `sdm.pages.approval_absensi` | `/sdm/approval-absensi` |
| `sdm/cetak_sertifikat.php` | `Sdm\CetakSertifikatController@{index,uploadScan}` | `sdm.pages.cetak_sertifikat` | `/sdm/cetak-sertifikat` |
| `sdm/print_sertifikat.php` | `Sdm\PrintSertifikatController@cetak` | (FPDF) | `GET /sdm/print-sertifikat` |
| `sdm/rekap_absensi.php` | `Sdm\RekapAbsensiController@{index,ajaxDetail}` | `sdm.pages.rekap_absensi` | `/sdm/rekap-absensi` |
| `sdm/reset_password.php` | `Sdm\ResetPasswordController@{index,store}` | `sdm.pages.reset_password` | `/sdm/reset-password` |
| `sdm/profile.php` | `Sdm\ProfileController@{index,update,changePassword}` | `sdm.pages.profile` | `/sdm/profile` |
| `sdm/peserta/daftar_peserta.php` | `Sdm\PesertaController@index` | `sdm.pages.daftar_peserta` | `/sdm/peserta` |
| `sdm/peserta/tambah_peserta.php` | `@create`/`@store` | `sdm.pages.tambah_peserta` | `/sdm/peserta/tambah` |
| `sdm/peserta/edit_peserta.php` | `@edit`/`@update` | `sdm.pages.edit_peserta` | `/sdm/peserta/edit/{id}` |
| `sdm/peserta/cek_username.php` | `@cekUsername` (JSON) | — | `/sdm/peserta/cek_username.php` |
| `sdm/peserta/export_excel.php` | `Sdm\PesertaExportController@export` | (xlsx) | `/sdm/peserta/export` |
| `sdm/users/users.php` | `Sdm\UserController@{index,store}` | `sdm.pages.users` | `/sdm/users` |
| `sdm/users/edit_users.php` | `@edit`/`@update` | `sdm.pages.edit_users` | `/sdm/users/edit/{id}` |
| `sdm/users/delete_users.php` | `@destroy` | — | `/sdm/users/delete/{id}` |
| `sdm/bidang/bidang.php` | `Sdm\BidangController@{index,store,update,destroy}` | `sdm.pages.bidang` | `/sdm/bidang` |
| `sdm/dokumen/dokumen.php` | `Sdm\DokumenController@{index,store,update,destroy}` | `sdm.pages.dokumen` | `/sdm/dokumen` |
| `sdm/pengaturan/hari_libur.php` | `Sdm\HariLiburController@{index,store,destroy,updateLiburPekan}` | `sdm.pages.hari_libur` | `/sdm/pengaturan/hari-libur` |
| `sdm/pengaturan/absensi_setting.php` | `Sdm\PengaturanAbsensiController@{index,update}` | `sdm.pages.absensi` | `/sdm/pengaturan/absensi` |
| `sdm/pengaturan/ttd_setting.php` | `Sdm\PengaturanTtdController@{index,update}` | `sdm.pages.ttd` | `/sdm/pengaturan/ttd` |
| `sdm/pengaturan/tahun_aktif.php` | **dilewati** (dormant, tabel tidak ada) | — | — |
| `sdm/ajax_cek_sertifikat.php` | `Sdm\AjaxCekSertifikatController@check` (JSON) | — | `/sdm/ajax_cek_sertifikat.php` |

### Manager (middleware `role:manager`)
| Native | Controller | View | Route |
|---|---|---|---|
| `manager/index.php` | (layout) | `layouts.manager` | — |
| `manager/home.php` | `Manager\DashboardController@index` | `manager.home` | `GET /manager` |
| `manager/approval_laporan.php` | `Manager\ApprovalLaporanController@{index,approve,reject}` | `manager.approval_laporan` | `/manager/approval-laporan` |
| `manager/profile.php` | `Manager\ProfileController` | `manager.profile` | `/manager/profile` |
| `manager/logout.php` | `LogoutController` | — | `/logout` |

## Aset & Upload
- `assets/`, `uploads/`, `favicon.ico`, `manifest.json`, `sertifikat.jpg`, `logo.png` → `public/`.
- Link lama `../uploads/xxx` → `/uploads/xxx` (nama file di DB tidak berubah).

## Tahapan
- [x] Analisis project & skema DB
- [x] **Tahap 0** Backup DB, native → `legacy/`, aset → `public/`
- [x] **Tahap 1** Scaffold Laravel + `.env` + packages + Model + middleware role
- [x] **Tahap 2** Auth + halaman publik + route `.php` kompatibel
- [x] **Tahap 3** Modul Peserta
- [x] **Tahap 4** Modul SDM
- [ ] **Tahap 5** Modul Manager
- [ ] **Tahap 6** Verifikasi menyeluruh

## Verifikasi (Tahap 6)
- [ ] Login 3 role + redirect
- [ ] CRUD peserta, bidang, users, dokumen
- [ ] Upload laporan, surat absensi, scan sertifikat
- [ ] Generate & verifikasi QR + PDF sertifikat
- [ ] Semua route + koneksi DB
- [ ] Tidak ada error PHP/Laravel
