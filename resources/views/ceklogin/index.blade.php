<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b4fb3">
    <title>Cek Akun Peserta - Website PKL</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Desain mengikuti halaman login (auth/login.blade.php) */
        :root {
            --blue: #0b4fb3;
            --ink: #16233b;
            --muted: #6b7a90;
            --line: #e1e8f3;
            --soft: #f4f7fc;
            --yellow: #ffc400;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { min-height: 100%; }
        body {
            min-height: 100vh; min-height: 100dvh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink); -webkit-font-smoothing: antialiased;
            background-color: #f1f5fb;
            background-image:
                radial-gradient(1100px 520px at 50% -12%, rgba(47, 124, 240, .12), transparent 70%),
                radial-gradient(#d3dcec 1px, transparent 1px);
            background-size: auto, 18px 18px;
        }

        .wrap {
            width: 100%; max-width: 1200px; margin: 0 auto;
            padding: calc(24px + env(safe-area-inset-top)) clamp(16px, 4vw, 40px) calc(32px + env(safe-area-inset-bottom));
        }

        .hero { max-width: 780px; margin: 0 auto; }

        .brand {
            display: flex; align-items: center; justify-content: center;
            width: 168px; height: 64px; margin: 0 auto 16px; padding: 8px 14px;
            border-radius: 18px; background: var(--soft); border: 1px solid var(--line);
        }
        .brand img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .brand i { font-size: 30px; color: var(--blue); display: none; }

        .tag {
            display: table; margin: 0 auto 12px; padding: 5px 12px; border-radius: 8px;
            background: #e8f0ff; color: var(--blue); font-size: 10.5px; font-weight: 800; letter-spacing: .5px;
        }
        h1 { text-align: center; font-size: clamp(26px, 4vw, 42px); font-weight: 800; line-height: 1.15; letter-spacing: -.3px; }
        .sub { text-align: center; font-size: clamp(12.5px, 1.5vw, 15px); color: var(--muted); line-height: 1.6; margin: 10px auto 26px; max-width: 460px; }

        .search-area { max-width: 680px; margin: 0 auto; }

        .lbl { display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; margin-bottom: 8px; }
        .lbl i { font-size: 17px; color: var(--blue); }

        .field { position: relative; }
        .field > .lead { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 19px; color: var(--muted); pointer-events: none; }
        .input {
            width: 100%; height: 50px; border-radius: 15px; border: 1.5px solid var(--line); background: #fff;
            padding: 0 14px 0 44px; font-family: inherit; font-size: 14px; color: var(--ink); outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .input::placeholder { color: #a3afc0; }
        .input:focus { border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .15); }

        /* Hasil pencarian (dropdown) */
        .dropdown {
            position: absolute; top: calc(100% + 8px); left: 0; right: 0; z-index: 20;
            background: #fff; border: 1px solid var(--line); border-radius: 16px;
            box-shadow: 0 16px 36px rgba(24, 60, 120, .16);
            max-height: 260px; overflow-y: auto; display: none; padding: 6px;
        }
        .result-item {
            display: block; width: 100%; text-align: left; border: 0; background: transparent; cursor: pointer;
            padding: 10px 12px; border-radius: 11px; font-family: inherit; color: inherit;
        }
        .result-item:hover, .result-item:focus-visible { background: var(--soft); outline: none; }
        .result-item .name { display: block; font-weight: 700; font-size: 13.5px; }
        .result-item .school { display: block; font-size: 11.5px; color: var(--muted); margin-top: 2px; }
        .dd-msg { padding: 14px; text-align: center; font-size: 12.5px; color: var(--muted); }
        .dd-msg.err { color: #a11d28; }

        /* Area hasil */
        .hasil { margin-top: 30px; }
        .empty { max-width: 760px; margin: 0 auto; text-align: center; padding: 36px 20px; border-radius: 18px; background: var(--soft); border: 1px dashed #cfd9ea; color: var(--muted); }
        .empty i { font-size: 44px; color: #b7c3d8; }
        .empty p { font-size: 12.5px; line-height: 1.55; margin-top: 6px; }
        .loading { display: flex; justify-content: center; padding: 28px 0; }
        .spin { width: 26px; height: 26px; border-radius: 50%; border: 3px solid #d3dcec; border-top-color: var(--blue); animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .fail { padding: 12px 14px; border-radius: 14px; background: #fdecee; color: #a11d28; border: 1px solid #f6c9ce; font-size: 12.5px; font-weight: 600; text-align: center; }

        /* Kartu kredensial */
        .cred-card {
            position: relative; overflow: hidden; border-radius: 20px; padding: 22px 24px; margin-bottom: 26px; color: #fff;
            background: linear-gradient(130deg, #2f7cf0 0%, #1766d6 45%, var(--blue) 100%);
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow: 0 14px 30px rgba(11, 79, 179, .25);
            display: grid; grid-template-columns: minmax(0, 1fr); gap: 18px;
        }
        .cred-card::after {
            content: "\eb71"; font-family: 'boxicons'; position: absolute; right: 18px; bottom: -20px;
            font-size: 104px; line-height: 1; opacity: .08; pointer-events: none;
        }
        .cred-head { display: flex; align-items: center; gap: 12px; position: relative; z-index: 1; min-width: 0; }
        .cred-ico {
            width: 46px; height: 46px; flex-shrink: 0; border-radius: 14px;
            display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--yellow);
            background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .22);
        }
        .cred-head-text { min-width: 0; }
        .cred-title { font-size: 13px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; line-height: 1.2; }
        .cred-sub { margin-top: 3px; font-size: 11.5px; line-height: 1.35; color: rgba(255, 255, 255, .78); }
        .cred-box { display: grid; grid-template-columns: 1fr; gap: 12px; position: relative; z-index: 1; }
        .cred-item {
            min-width: 0; text-align: left; cursor: pointer; font-family: inherit; color: inherit;
            display: flex; flex-direction: column; gap: 4px;
            background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .24);
            padding: 13px 15px; border-radius: 14px;
            transition: background .15s ease, border-color .15s ease, transform .15s ease;
        }
        .cred-item:hover { background: rgba(255, 255, 255, .24); border-color: rgba(255, 255, 255, .38); }
        .cred-item:active { transform: scale(.99); }
        .cred-item:focus-visible { outline: 3px solid var(--yellow); outline-offset: 2px; }
        .cred-label { display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; opacity: .82; }
        .cred-label i { font-size: 15px; color: var(--yellow); opacity: 1; flex-shrink: 0; }
        .cred-val { font-size: clamp(16px, 1.6vw, 19px); font-weight: 800; line-height: 1.25; letter-spacing: .2px; overflow-wrap: anywhere; font-variant-numeric: tabular-nums; }

        /* Detail profil */
        .sec-title { font-size: 10.5px; font-weight: 800; letter-spacing: .6px; color: var(--muted); text-transform: uppercase; margin-bottom: 10px; }
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .pcard { background: var(--soft); border: 1px solid var(--line); border-radius: 14px; padding: 11px 13px; }
        .p-label { font-size: 10px; color: var(--muted); text-transform: uppercase; font-weight: 800; letter-spacing: .3px; margin-bottom: 3px; }
        .p-val { font-weight: 700; font-size: 13px; overflow-wrap: anywhere; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
        .badge.aktif { background: #e1f5ea; color: #157347; }
        .badge.menunggu { background: #fff4cc; color: #8a6500; }
        .badge.selesai { background: #e6ebf3; color: #4a5a72; }

        /* Tombol kembali (gaya sama dengan tombol "Cek Username / NID" di login) */
        .back {
            display: flex; align-items: center; justify-content: center; gap: 6px; width: fit-content;
            margin: 22px auto 0; padding: 9px 16px; border-radius: 999px; text-decoration: none;
            background: var(--soft); border: 1px solid var(--line); color: #3d4c63; font-size: 12.5px; font-weight: 700;
        }
        .back i { font-size: 18px; color: var(--blue); }
        .back:hover { background: #fff; }

        .foot { text-align: center; font-size: 11px; color: var(--muted); margin-top: 18px; }

        .toast {
            position: fixed; left: 50%; bottom: calc(28px + env(safe-area-inset-bottom)); transform: translate(-50%, 12px);
            display: flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 999px;
            background: var(--ink); color: #fff; font-size: 12.5px; font-weight: 700; z-index: 9999;
            opacity: 0; pointer-events: none; transition: opacity .2s ease, transform .2s ease;
        }
        .toast i { color: var(--yellow); font-size: 18px; }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }

        .back:focus-visible { outline: 3px solid rgba(47, 124, 240, .5); outline-offset: 2px; }

        /* Hasil pencarian */
        .result-grid { display: block; }
        .result-cred, .result-detail { min-width: 0; }

        @media (max-width: 500px) {
            .profile-grid { grid-template-columns: 1fr; }
            .cred-card { padding: 18px; }
            .cred-ico { width: 42px; height: 42px; font-size: 22px; }
        }
        @media (max-width: 360px) {
            .wrap { padding-left: 14px; padding-right: 14px; }
            .brand { width: 150px; height: 58px; }
            h1 { font-size: 24px; }
            .cred-card { padding: 16px; border-radius: 18px; }
            .cred-head { gap: 10px; }
            .cred-sub { font-size: 11px; }
        }

        /* Tablet (lebar jendela >= 600px) */
        @media (min-width: 600px) {
            .brand { width: 190px; height: 72px; margin-bottom: 18px; }
            .input { height: 54px; font-size: 14.5px; }
            .profile-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            .pcard { padding: 13px 15px; }
            .cred-box { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        /* Desktop (lebar jendela >= 992px) */
        @media (min-width: 992px) {
            .hero { margin-bottom: 4px; }
            .search-area { max-width: 720px; }
            .lbl { justify-content: center; font-size: 13px; }
            .input { height: 56px; font-size: 15px; padding-left: 48px; border-radius: 16px; }
            .field > .lead { left: 16px; font-size: 20px; }
            .dropdown { max-height: 340px; }

            .hasil { margin-top: 34px; }
            .empty { padding: 46px 24px; }
            .empty i { font-size: 54px; }
            .empty p { font-size: 14px; }

            .profile-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .pcard { padding: 15px 17px; }
            .p-val { font-size: 14px; }
            .sec-title { font-size: 11px; margin-bottom: 12px; }

            .cred-card { padding: 26px 30px; border-radius: 22px; }
            .cred-box { gap: 14px; }
            .cred-item { padding: 15px 17px; }
            .cred-title { font-size: 14px; }
            .cred-sub { font-size: 12px; }

            .back { margin-top: 34px; padding: 10px 18px; font-size: 13px; }
            .foot { margin-top: 22px; font-size: 11.5px; }
        }

        /* Desktop lebar (lebar jendela >= 1200px) */
        @media (min-width: 1200px) {
            .cred-card { grid-template-columns: minmax(230px, 270px) minmax(0, 1fr); align-items: center; gap: 36px; padding: 28px 34px; }
            .cred-item { padding: 16px 18px; }
            .profile-grid { gap: 14px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .input, .cred-item, .toast { transition: none; }
            .spin { animation-duration: 1.6s; }
        }
    </style>
</head>
<body>

<div class="wrap">
    <div class="hero">
        <div class="brand">
            <img src="/assets/images/logo.png" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <i class='bx bxs-briefcase-alt-2'></i>
        </div>

        <span class="tag">PORTAL PKL &amp; MAGANG INDUSTRI</span>
        <h1>Cek Akun Peserta</h1>
        <p class="sub">Lupa username? Cari nama Anda di bawah ini.</p>
    </div>

    <div class="search-area">
        <label class="lbl" for="inputCari"><i class='bx bx-search-alt'></i> Cari nama peserta</label>
        <div class="field">
            <i class='bx bx-search lead'></i>
            <input type="text" id="inputCari" class="input" placeholder="Ketik nama Anda..." autocomplete="off">
            <div id="dropdown" class="dropdown"></div>
        </div>
    </div>

    <div id="hasilContainer" class="hasil" aria-live="polite">
        <div class="empty">
            <i class='bx bx-user-circle'></i>
            <p>Gunakan kolom pencarian di atas untuk<br>menemukan detail akun Anda.</p>
        </div>
    </div>

    <a href="/login" class="back"><i class='bx bx-left-arrow-alt'></i> Kembali ke Login</a>
    <div class="foot">SINFO PKL © 2026</div>
</div>

<div id="toast" class="toast" role="status" aria-live="polite"><i class='bx bx-check-circle'></i><span id="toastText"></span></div>

<script>
(function () {
    const bidangData = @json($bidang_list);
    const inputCari  = document.getElementById('inputCari');
    const dropdown   = document.getElementById('dropdown');
    const hasil      = document.getElementById('hasilContainer');
    const toastEl    = document.getElementById('toast');
    const toastText  = document.getElementById('toastText');
    let timer = null, toastTimer = null, reqId = 0;

    // Semua data dari server di-escape sebelum masuk ke HTML
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function showToast(msg) {
        toastText.textContent = msg;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2200);
    }

    function salin(text, label) {
        const selesai = () => showToast(label + ' berhasil disalin');
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(selesai, selesai);
            return;
        }
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        selesai();
    }

    const bukaDropdown  = () => { dropdown.style.display = 'block'; };
    const tutupDropdown = () => { dropdown.style.display = 'none'; };

    inputCari.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { tutupDropdown(); return; }
        timer = setTimeout(() => cari(q), 300);
    });

    async function cari(q) {
        const my = ++reqId;
        dropdown.innerHTML = '<div class="dd-msg">Mencari...</div>';
        bukaDropdown();
        try {
            const res = await fetch('/ceklogin/search_peserta.php?q=' + encodeURIComponent(q));
            if (!res.ok) throw new Error('gagal');
            const data = await res.json();
            if (my !== reqId) return;
            if (!data.length) {
                dropdown.innerHTML = '<div class="dd-msg err">Data tidak ditemukan</div>';
                return;
            }
            dropdown.innerHTML = data.map((i) =>
                '<button type="button" class="result-item" data-id="' + esc(i.id) + '">' +
                '<span class="name">' + esc(i.nama) + '</span>' +
                '<span class="school">' + esc(i.asal_sekolah) + '</span></button>'
            ).join('');
        } catch (e) {
            if (my === reqId) dropdown.innerHTML = '<div class="dd-msg err">Terjadi kesalahan</div>';
        }
    }

    dropdown.addEventListener('click', (e) => {
        const b = e.target.closest('.result-item');
        if (b) pilihPeserta(b.dataset.id);
    });

    hasil.addEventListener('click', (e) => {
        const b = e.target.closest('[data-copy]');
        if (b) salin(b.dataset.copy, b.dataset.label);
    });

    async function pilihPeserta(id) {
        tutupDropdown();
        inputCari.value = '';
        hasil.innerHTML = '<div class="loading"><div class="spin" role="status" aria-label="Memuat"></div></div>';

        try {
            const res = await fetch('/ceklogin/get_peserta.php?id=' + encodeURIComponent(id));
            const p = await res.json();
            if (!res.ok || p.error) throw new Error('gagal');

            const bidang = bidangData[p.bidang_id] || '-';
            const st = String(p.status_magang || '').toLowerCase();
            const badge = st === 'aktif' ? 'aktif' : (st === 'selesai' ? 'selesai' : 'menunggu');
            let tgl = '-';
            if (p.tgl_masuk) {
                const raw = String(p.tgl_masuk);
                const d = new Date(raw.length === 10 ? raw + 'T00:00:00' : raw);
                if (!isNaN(d)) tgl = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            }

            hasil.innerHTML =
                '<div class="result-grid">' +
                    '<div class="result-cred">' +
                        '<div class="cred-card">' +
                            '<div class="cred-head">' +
                                '<div class="cred-ico"><i class="bx bx-key"></i></div>' +
                                '<div class="cred-head-text">' +
                                    '<div class="cred-title">Informasi Login</div>' +
                                    '<div class="cred-sub">Klik kartu untuk menyalin username atau password.</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="cred-box">' +
                                '<button type="button" class="cred-item" data-copy="' + esc(p.username) + '" data-label="Username">' +
                                    '<div class="cred-label">Username <i class="bx bx-copy"></i></div>' +
                                    '<div class="cred-val">' + esc(p.username) + '</div></button>' +
                                '<button type="button" class="cred-item" data-copy="12345" data-label="Password">' +
                                    '<div class="cred-label">Password default <i class="bx bx-copy"></i></div>' +
                                    '<div class="cred-val">12345</div></button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="result-detail">' +
                        '<div class="sec-title">Detail profil</div>' +
                        '<div class="profile-grid">' +
                            '<div class="pcard"><div class="p-label">Nama lengkap</div><div class="p-val">' + esc(p.nama) + '</div></div>' +
                            '<div class="pcard"><div class="p-label">Status magang</div><div class="p-val"><span class="badge ' + badge + '">' + esc(p.status_magang) + '</span></div></div>' +
                            '<div class="pcard"><div class="p-label">Asal instansi</div><div class="p-val">' + esc(p.asal_sekolah) + '</div></div>' +
                            '<div class="pcard"><div class="p-label">Bidang</div><div class="p-val">' + esc(bidang) + '</div></div>' +
                            '<div class="pcard"><div class="p-label">Tanggal masuk</div><div class="p-val">' + esc(tgl) + '</div></div>' +
                            '<div class="pcard"><div class="p-label">Unit kerja</div><div class="p-val">' + esc(p.unit || '-') + '</div></div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        } catch (e) {
            hasil.innerHTML = '<div class="fail">Gagal memuat data</div>';
        }
    }

    document.addEventListener('click', (e) => {
        if (!inputCari.contains(e.target) && !dropdown.contains(e.target)) tutupDropdown();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutupDropdown(); });
})();
</script>

</body>
</html>