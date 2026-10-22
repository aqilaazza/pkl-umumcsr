<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b4fb3">
    <title>Login PKL</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0b4fb3;
            --ink: #16233b;
            --muted: #6b7a90;
            --line: #e1e8f3;
            --soft: #f4f7fc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }
        body {
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            padding: calc(20px + env(safe-area-inset-top)) 16px calc(20px + env(safe-area-inset-bottom));
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink); -webkit-font-smoothing: antialiased;
            background-color: #f1f5fb;
            background-image: radial-gradient(#d3dcec 1px, transparent 1px);
            background-size: 18px 18px;
        }

        .wrap { width: 100%; max-width: 400px; }

        .card {
            position: relative; background: #fff; border-radius: 26px; padding: 30px 24px 24px;
            border: 1px solid rgba(230, 236, 245, .95);
            box-shadow: 0 18px 44px rgba(24, 60, 120, .12);
        }
        .card::before {
            content: ''; position: absolute; top: -1px; left: 50%; transform: translateX(-50%);
            width: 38%; height: 3px; border-radius: 0 0 6px 6px;
            background: linear-gradient(90deg, transparent, #2f7cf0, transparent);
        }

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
        h1 { text-align: center; font-size: 25px; font-weight: 800; line-height: 1.2; }
        .sub { text-align: center; font-size: 12.5px; color: var(--muted); line-height: 1.55; margin: 8px auto 22px; max-width: 290px; }

        .alert {
            display: flex; align-items: flex-start; gap: 8px; margin-bottom: 16px; padding: 11px 13px;
            border-radius: 14px; background: #fdecee; color: #a11d28; border: 1px solid #f6c9ce;
            font-size: 12.5px; font-weight: 600; line-height: 1.45;
        }
        .alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        .group { margin-bottom: 16px; }
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
        .input.has-eye { padding-right: 48px; }
        .eye {
            position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
            width: 40px; height: 40px; border: 0; background: transparent; color: var(--muted);
            font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 12px;
        }

        .remember { display: flex; align-items: center; gap: 10px; margin: 2px 0 20px; font-size: 12.5px; color: #3d4c63; cursor: pointer; user-select: none; }
        .remember input { position: absolute; opacity: 0; pointer-events: none; }
        .box {
            width: 20px; height: 20px; border-radius: 7px; border: 1.5px solid #c3cddb; background: #fff;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; font-size: 15px;
            transition: background .15s ease, border-color .15s ease;
        }
        .box i { opacity: 0; transform: scale(.6); transition: opacity .15s ease, transform .2s cubic-bezier(.34, 1.4, .64, 1); }
        .remember input:checked + .box { background: var(--blue); border-color: var(--blue); }
        .remember input:checked + .box i { opacity: 1; transform: none; }
        .remember input:focus-visible + .box { box-shadow: 0 0 0 4px rgba(47, 124, 240, .25); }

        .btn {
            display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 52px;
            border: 0; border-radius: 16px; cursor: pointer; font-family: inherit; font-size: 14px; font-weight: 800;
            color: #fff; background: linear-gradient(135deg, #2f7cf0, var(--blue));
            box-shadow: 0 10px 22px rgba(11, 79, 179, .32); transition: transform .15s ease, opacity .15s ease;
        }
        .btn i { font-size: 20px; transition: transform .2s ease; }
        .btn:hover:not(:disabled) i { transform: translateX(3px); }
        .btn:active:not(:disabled) { transform: scale(.98); }
        .btn:disabled { opacity: .75; cursor: not-allowed; }
        .spin { width: 17px; height: 17px; border-radius: 50%; border: 2.5px solid rgba(255, 255, 255, .4); border-top-color: #fff; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .cek {
            display: flex; align-items: center; justify-content: center; gap: 6px; width: fit-content;
            margin: 20px auto 0; padding: 9px 16px; border-radius: 999px; text-decoration: none;
            background: var(--soft); border: 1px solid var(--line); color: #3d4c63; font-size: 12.5px; font-weight: 700;
        }
        .cek i { font-size: 16px; color: #e0a800; }

        .foot { text-align: center; font-size: 11px; color: var(--muted); margin-top: 18px; }

        .btn:focus-visible, .cek:focus-visible, .eye:focus-visible { outline: 3px solid rgba(47, 124, 240, .5); outline-offset: 2px; }

        @media (max-width: 360px) {
            .card { padding: 26px 16px 20px; border-radius: 22px; }
            h1 { font-size: 22px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .btn, .btn i, .input, .box, .box i { transition: none; }
            .spin { animation-duration: 1.6s; }
        }
    </style>
</head>
<body>

<div class="wrap">
    <div class="card">
        <div class="brand">
            <img src="/assets/images/logo.png" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <i class='bx bxs-briefcase-alt-2'></i>
        </div>

        <span class="tag">PORTAL PKL &amp; MAGANG INDUSTRI</span>
        <h1>Login</h1>
        <p class="sub">Masukkan NID dan password akun Anda untuk masuk ke portal.</p>

        @if (session('error'))
            <div class="alert" role="alert"><i class='bx bx-error-circle'></i><span>{{ session('error') }}</span></div>
        @endif

        <form method="POST" id="loginForm" action="{{ url('/login') }}">
            @csrf
            <div class="group">
                <label class="lbl" for="nidInput"><i class='bx bx-id-card'></i> NID</label>
                <div class="field">
                    <i class='bx bx-user lead'></i>
                    <input type="text" name="NRP" id="nidInput" class="input" placeholder="Masukkan NID Anda" autocomplete="username" autocapitalize="off" required>
                </div>
            </div>

            <div class="group">
                <label class="lbl" for="passInput"><i class='bx bx-key'></i> Password</label>
                <div class="field">
                    <i class='bx bx-lock-alt lead'></i>
                    <input type="password" name="pass" id="passInput" class="input has-eye" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button type="button" class="eye" id="togglePass" aria-label="Tampilkan password"><i class='bx bx-hide'></i></button>
                </div>
            </div>

            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                <span class="box"><i class='bx bx-check'></i></span>
                Simpan sesi masuk
            </label>

            <button class="btn" name="submit" id="btnLogin">
                <span id="btnText">Masuk ke Portal</span> <i class='bx bx-right-arrow-alt'></i>
            </button>
        </form>

        <a href="{{ url('/ceklogin') }}" class="cek"><i class='bx bx-search-alt'></i> Cek Username / NID</a>
    </div>

    <div class="foot">SINFO PKL © 2026</div>
</div>

<script>
    document.getElementById('togglePass').addEventListener('click', function () {
        var input = document.getElementById('passInput');
        var icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bx bx-show';
            this.setAttribute('aria-label', 'Sembunyikan password');
        } else {
            input.type = 'password';
            icon.className = 'bx bx-hide';
            this.setAttribute('aria-label', 'Tampilkan password');
        }
    });

    document.getElementById('loginForm').addEventListener('submit', function () {
        var btn = document.getElementById('btnLogin');
        btn.disabled = true;
        btn.innerHTML = '<span class="spin"></span> Memproses...';
    });
</script>

</body>
</html>