<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login PKL</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            margin: 0;
            background: #f0f5f1;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 36px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #d1e7dd;
        }
        .logo {
            width: 180px;
            margin-bottom: 16px;
        }
        .form-control {
            padding: 10px 12px;
            border-radius: 6px;
        }
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15);
        }
        .btn-login {
            background: #198754;
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px;
            border-radius: 6px;
            transition: 0.2s ease;
        }
        .btn-login:hover:not(:disabled) {
            background: #157347;
        }
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .cek-link {
            display: block;
            margin-top: 16px;
            color: #198754;
            font-size: 13px;
            text-decoration: none;
        }
        .cek-link:hover {
            color: #0f5132;
        }
    </style>
</head>
<body>

<div class="login-box text-center">
    <img src="/assets/images/logo.png" class="logo">
    <h4 class="title">Login PKL</h4>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" id="loginForm" action="{{ url('/login') }}">
        @csrf
        <div class="mb-3 text-start">
            <label>NID</label>
            <input type="text" name="NRP" class="form-control" required>
        </div>
        <div class="mb-3 text-start">
            <label>Password</label>
            <div style="position:relative">
                <input type="password" name="pass" id="passInput" class="form-control" required style="padding-right:40px">
                <i id="togglePass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6c757d" class="bx bx-hide"></i>
            </div>
        </div>
        <div class="d-grid">
            <button class="btn btn-login" name="submit" id="btnLogin">LOGIN</button>
        </div>
    </form>

    <script>
        document.getElementById('togglePass').addEventListener('click', function() {
            var input = document.getElementById('passInput');
            var icon = this;
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bx bx-show';
            } else {
                input.type = 'password';
                icon.className = 'bx bx-hide';
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        });
    </script>

    <a href="{{ url('/ceklogin') }}" class="cek-link">
    🔍 Cek Username Peserta
    </a>
</div>

</body>
</html>
