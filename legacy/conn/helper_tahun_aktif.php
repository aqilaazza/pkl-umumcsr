<?php
/**
 * helper_tahun_aktif.php
 * -------------------------------------------------------
 * Include file ini di conn.php atau di setiap halaman
 * yang membutuhkan informasi tahun aktif.
 *
 * Cara pakai:
 *   include "helper_tahun_aktif.php";
 *   echo $TAHUN_AKTIF;         // → 2025 (int)
 *   echo $TAHUN_AKTIF_INFO['keterangan']; // → "Tahun Anggaran 2025"
 * -------------------------------------------------------
 */

$TAHUN_AKTIF      = null;
$TAHUN_AKTIF_INFO = null;

$_ta_q = @mysqli_query($conn, "SELECT * FROM tahun_aktif WHERE status = 'aktif' LIMIT 1");
if ($_ta_q) {
    $_ta = mysqli_fetch_assoc($_ta_q);
    if ($_ta) {
        $TAHUN_AKTIF      = (int) $_ta['tahun'];
        $TAHUN_AKTIF_INFO = $_ta;
    }
    unset($_ta);
}
unset($_ta_q);


// -------------------------------------------------------
// Fungsi alternatif — panggil kapan saja setelah include
// -------------------------------------------------------

/**
 * Ambil tahun aktif dari database.
 * @return int|null
 */
function getTahunAktif(): ?int {
    global $conn;
    $_r_q = @mysqli_query($conn, "SELECT tahun FROM tahun_aktif WHERE status = 'aktif' LIMIT 1");
    if ($_r_q) {
        $r = mysqli_fetch_assoc($_r_q);
        return $r ? (int) $r['tahun'] : null;
    }
    return null;
}
