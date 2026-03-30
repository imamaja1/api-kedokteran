<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIAKAD Kedokteran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d3b6e 0%, #1a73e8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
            overflow: hidden;
        }

        .login-header {
            background: #0d3b6e;
            padding: 2rem;
            text-align: center;
        }

        .login-body {
            padding: 2rem;
        }

        .form-control:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 .2rem rgba(26, 115, 232, .2);
        }

        .btn-login {
            background: #0d3b6e;
            color: #fff;
            font-weight: 600;
            width: 100%;
            padding: .75rem;
            border: none;
            border-radius: 8px;
            transition: background .2s;
        }

        .btn-login:hover {
            background: #1a73e8;
            color: #fff;
        }

        .input-icon {
            position: relative;
        }

        .input-icon .bi {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .input-icon .form-control {
            padding-left: 2.4rem;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            {{-- <i class="bi bi-heart-pulse-fill text-danger fs-2 mb-2 d-block"></i> --}}
            <h5 class="text-white fw-bold mb-0">SIAKAD Kedokteran</h5>
            <p class="text-white-50 small mb-0">Panel Administrasi Dokumentasi API</p>
        </div>
        <div class="login-body">
            @if ($errors->any())
            <div class="alert alert-danger py-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope-fill"></i>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" required autofocus placeholder="admin@siakad.ac.id">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn-login btn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                </button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>