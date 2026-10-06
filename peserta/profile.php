<?php
// ============================================================
// PESERTA - Profile (Mobile) + Kontak Admin + Logout
// ============================================================
include "../conn/conn.php";

if (!isset($_SESSION['id'])) { header("location:../index.php"); exit(); }

$id = $_SESSION['id'];
$esc_id = mysqli_real_escape_string($conn, $id);

$data_profile = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM users WHERE username = '$esc_id'"));
$peserta = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, b.bidang as nama_bidang FROM peserta p
     LEFT JOIN bidang b ON p.bidang_id = b.id
     WHERE p.username = '$esc_id'"));

$msg_pass = '';

// Proses ubah password
if (isset($_POST['submit'])) {
    $pw = $_POST['password_baru'];
    $cpw = $_POST['konfirmasi_password'];

    if (empty($pw) || empty($cpw)) {
        $msg_pass = 'error:Password tidak boleh kosong!';
    } elseif ($pw !== $cpw) {
        $msg_pass = 'error:Konfirmasi password tidak sesuai!';
    } elseif (strlen($pw) < 5) {
        $msg_pass = 'error:Password minimal 5 karakter!';
    } else {
        $hashed = password_hash($pw, PASSWORD_DEFAULT);
        $update = mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE username = '$esc_id'");
        $msg_pass = $update ? 'success:Password berhasil diubah!' : 'error:Gagal mengubah password!';
    }
}

$msg_profile = '';

// Proses edit profil
if (isset($_POST['submit_profile'])) {
    $nama = trim($_POST['nama']);
    $asal_sekolah = trim($_POST['asal_sekolah']);
    $jurusan = trim($_POST['jurusan']);

    if (empty($nama) || empty($asal_sekolah) || empty($jurusan)) {
        $msg_profile = 'error:Nama, Sekolah/Universitas, dan Jurusan tidak boleh kosong!';
    } else {
        $esc_nama = mysqli_real_escape_string($conn, $nama);
        $esc_sekolah = mysqli_real_escape_string($conn, $asal_sekolah);
        $esc_jurusan = mysqli_real_escape_string($conn, $jurusan);

        // Update database
        $update_users = mysqli_query($conn, "UPDATE users SET nama = '$esc_nama' WHERE username = '$esc_id'");
        $update_peserta = mysqli_query($conn, "UPDATE peserta SET nama = '$esc_nama', asal_sekolah = '$esc_sekolah', jurusan = '$esc_jurusan' WHERE username = '$esc_id'");

        if ($update_users && $update_peserta) {
            $_SESSION['nama'] = $nama;
            $msg_profile = 'success:Profil berhasil diperbarui!';
            
            // Reload data
            $data_profile = mysqli_fetch_array(mysqli_query($conn, "SELECT * FROM users WHERE username = '$esc_id'"));
            $peserta = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT p.*, b.bidang as nama_bidang FROM peserta p
                 LEFT JOIN bidang b ON p.bidang_id = b.id
                 WHERE p.username = '$esc_id'"));
        } else {
            $msg_profile = 'error:Gagal memperbarui profil!';
        }
    }
}

function tgl_indo_profile($t) {
    if (empty($t) || $t == '0000-00-00') return '-';
    $b = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $p = explode('-', $t);
    return $p[2].' '.$b[(int)$p[1]].' '.$p[0];
}
?>

<?php 
$msg = $msg_pass ?: $msg_profile;
if ($msg):
    $parts = explode(':', $msg, 2);
?>
<script>document.addEventListener('DOMContentLoaded', () => showToast('<?= addslashes($parts[1]) ?>', '<?= $parts[0] ?>'));</script>
<?php endif; ?>

<!-- ═══ Informasi Peserta ═══ -->
<?php if ($peserta): ?>
<div class="m-section-title"><i class='bx bx-id-card'></i> Informasi Peserta</div>
<div class="m-card" style="padding:4px 16px;">
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#e8f0fe; color:#1a73e8;"><i class='bx bx-user'></i></div>
        <div style="flex:1;">
            <div class="profile-info-label">Nama Lengkap</div>
            <div class="profile-info-value"><?= htmlspecialchars(ucwords($peserta['nama'] ?? $data_profile['nama'] ?? '')) ?></div>
        </div>
        <div>
            <button type="button" onclick="openEditProfilModal('nama')" style="background:none; border:none; color:var(--primary); font-size:18px; cursor:pointer; padding:4px; display:flex; align-items:center;">
                <i class='bx bx-edit-alt'></i>
            </button>
        </div>
    </div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#f1f3f4; color:#5f6368;"><i class='bx bx-at'></i></div>
        <div style="flex:1;">
            <div class="profile-info-label">Username</div>
            <div class="profile-info-value"><?= htmlspecialchars($data_profile['username'] ?? '') ?></div>
        </div>
    </div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#d1e7dd; color:#0f5132;"><i class='bx bx-building-house'></i></div>
        <div style="flex:1;">
            <div class="profile-info-label">Sekolah / Universitas</div>
            <div class="profile-info-value"><?= htmlspecialchars($peserta['asal_sekolah'] ?? '-') ?></div>
        </div>
        <div>
            <button type="button" onclick="openEditProfilModal('sekolah')" style="background:none; border:none; color:var(--primary); font-size:18px; cursor:pointer; padding:4px; display:flex; align-items:center;">
                <i class='bx bx-edit-alt'></i>
            </button>
        </div>
    </div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#cfe2ff; color:#084298;"><i class='bx bx-book-reader'></i></div>
        <div style="flex:1;">
            <div class="profile-info-label">Jurusan</div>
            <div class="profile-info-value"><?= htmlspecialchars($peserta['jurusan'] ?? '-') ?></div>
        </div>
        <div>
            <button type="button" onclick="openEditProfilModal('jurusan')" style="background:none; border:none; color:var(--primary); font-size:18px; cursor:pointer; padding:4px; display:flex; align-items:center;">
                <i class='bx bx-edit-alt'></i>
            </button>
        </div>
    </div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#fff3cd; color:#856404;"><i class='bx bx-briefcase'></i></div>
        <div>
            <div class="profile-info-label">Bidang</div>
            <div class="profile-info-value"><?= htmlspecialchars($peserta['nama_bidang'] ?? '-') ?></div>
        </div>
    </div>
    <?php if ($peserta['unit']): ?>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#e2d9f3; color:#6f42c1;"><i class='bx bx-buildings'></i></div>
        <div>
            <div class="profile-info-label">Unit</div>
            <div class="profile-info-value"><?= htmlspecialchars($peserta['unit']) ?></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#d1ecf1; color:#0c5460;"><i class='bx bx-calendar'></i></div>
        <div>
            <div class="profile-info-label">Periode Magang</div>
            <div class="profile-info-value"><?= tgl_indo_profile($peserta['tgl_masuk'] ?? '') ?> — <?= tgl_indo_profile($peserta['tgl_keluar'] ?? '') ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ Modal Edit Profil ═══ -->
<div class="m-modal-overlay" id="modalEditProfil">
    <div class="m-bottom-sheet">
        <div class="m-bottom-sheet-handle"></div>
        <div class="m-bottom-sheet-title">Edit Profil</div>
        <form action="index.php?page=profile" method="post">
            <div class="m-form-group">
                <label class="m-form-label">Nama Lengkap</label>
                <input type="text" name="nama" id="editNama" class="m-form-input" value="<?= htmlspecialchars($peserta['nama'] ?? $data_profile['nama'] ?? '') ?>" required>
            </div>
            <div class="m-form-group">
                <label class="m-form-label">Sekolah / Universitas</label>
                <input type="text" name="asal_sekolah" id="editSekolah" class="m-form-input" value="<?= htmlspecialchars($peserta['asal_sekolah'] ?? '') ?>" required>
            </div>
            <div class="m-form-group">
                <label class="m-form-label">Jurusan</label>
                <input type="text" name="jurusan" id="editJurusan" class="m-form-input" value="<?= htmlspecialchars($peserta['jurusan'] ?? '') ?>" required>
            </div>
            <button type="submit" name="submit_profile" class="action-btn action-btn-primary action-btn-block">
                <i class='bx bx-save'></i> Simpan Perubahan
            </button>
            <button type="button" class="action-btn action-btn-outline action-btn-block" style="margin-top:8px;" onclick="closeEditProfilModal()">
                Batal
            </button>
        </form>
    </div>
</div>

<!-- ═══ Ubah Password ═══ -->
<div class="m-section-title"><i class='bx bx-lock-alt'></i> Ubah Password</div>
<div class="m-card">
    <form action="index.php?page=profile" method="post">
        <div class="m-form-group">
            <label class="m-form-label">Password Baru</label>
            <div style="position:relative;">
                <input type="password" name="password_baru" id="pw1" class="m-form-input" placeholder="Masukkan password baru" minlength="5" required style="padding-right:40px;">
                <button type="button" onclick="togglePw('pw1', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text-muted); font-size:18px; cursor:pointer;">
                    <i class='bx bx-hide'></i>
                </button>
            </div>
            <div class="m-form-hint">Minimal 5 karakter</div>
        </div>
        <div class="m-form-group">
            <label class="m-form-label">Konfirmasi Password</label>
            <div style="position:relative;">
                <input type="password" name="konfirmasi_password" id="pw2" class="m-form-input" placeholder="Ketik ulang password" required style="padding-right:40px;">
                <button type="button" onclick="togglePw('pw2', this)" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text-muted); font-size:18px; cursor:pointer;">
                    <i class='bx bx-hide'></i>
                </button>
            </div>
        </div>
        <button type="submit" name="submit" class="action-btn action-btn-primary action-btn-block">
            <i class='bx bx-check'></i> Ubah Password
        </button>
    </form>
</div>

<!-- ═══ Kontak Admin ═══ -->
<div class="m-section-title"><i class='bx bx-headphone'></i> Hubungi Admin</div>
<div class="m-card">
    <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:48px; height:48px; border-radius:50%; background:#25d366; display:flex; align-items:center; justify-content:center; color:white; font-size:24px; flex-shrink:0;">
            <i class='bx bxl-whatsapp'></i>
        </div>
        <div style="flex:1;">
            <div style="font-size:14px; font-weight:600;">Admin Ubaid</div>
            <div style="font-size:12px; color:var(--text-secondary);">0852 3322 3872</div>
        </div>
        <a href="https://wa.me/6285233223872?text=Halo%20Admin%2C%20saya%20peserta%20magang%20(<?= urlencode($_SESSION['nama'] ?? '') ?>)%20ingin%20bertanya"
           target="_blank" class="action-btn action-btn-success" style="padding:8px 16px; font-size:12px;">
            <i class='bx bxl-whatsapp'></i> Chat
        </a>
    </div>
</div>

<!-- ═══ Logout ═══ -->
<a href="logout.php" class="action-btn action-btn-block" style="background:var(--bg-card); color:#dc3545; border:2px solid #dc3545; margin-bottom:16px; text-decoration:none;">
    <i class='bx bx-log-out'></i> Logout
</a>

<div style="text-align:center; font-size:10px; color:var(--text-muted); padding-bottom:8px;">
    PKL Magang v2.0 · © 2026
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bx bx-show';
    } else {
        input.type = 'password';
        icon.className = 'bx bx-hide';
    }
}

function openEditProfilModal(focusField) {
    const modal = document.getElementById('modalEditProfil');
    modal.classList.add('show');
    
    if (focusField === 'nama') {
        document.getElementById('editNama').focus();
    } else if (focusField === 'sekolah') {
        document.getElementById('editSekolah').focus();
    } else if (focusField === 'jurusan') {
        document.getElementById('editJurusan').focus();
    }
}

function closeEditProfilModal() {
    document.getElementById('modalEditProfil').classList.remove('show');
}

document.getElementById('modalEditProfil').addEventListener('click', function(e) {
    if (e.target === this) closeEditProfilModal();
});
</script>