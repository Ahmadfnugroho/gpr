<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - GPR</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        :root {
            --navy: #1e3a8a;
            --navy-light: #3b82f6;
            --navy-dark: #1e40af;
            --card-bg: rgba(255, 255, 255, 0.95);
            --card-border: rgba(30, 58, 138, 0.1);
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 30%, #e2e8f0 70%, #cbd5e1 100%);
            color: var(--navy);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .brand-panel {
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 50%, #e2e8f0 100%);
            border-right: 1px solid rgba(30, 58, 138, 0.08);
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(30, 58, 138, 0.1), 0 10px 10px -5px rgba(30, 58, 138, 0.04);
        }

        .form-control.bg-clean {
            background: #ffffff;
            border-color: rgba(30, 58, 138, 0.2);
            color: var(--navy);
            border-radius: 8px;
        }

        .form-control.bg-clean::placeholder {
            color: rgba(30, 58, 138, 0.6);
        }

        .form-control.bg-clean:focus {
            background: #ffffff;
            border-color: var(--navy-light);
            box-shadow: 0 0 0 .25rem rgba(59, 130, 246, 0.25);
            color: var(--navy);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-dark) 100%);
            border: none;
            color: #ffffff;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--navy-dark) 0%, #1e40af 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(30, 58, 138, 0.3);
        }

        .link-faded {
            color: rgba(30, 58, 138, 0.7);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .link-faded:hover {
            color: var(--navy);
            text-decoration: underline;
        }

        .text-navy {
            color: var(--navy) !important;
        }

        .text-navy-light {
            color: rgba(30, 58, 138, 0.7) !important;
        }

        /* Small-screen banner image (logo) */
        .top-logo-sm {
            max-width: 140px;
            height: auto;
            filter: drop-shadow(0 4px 12px rgba(30, 58, 138, 0.2));
        }

        /* Left side illustration/logo */
        .brand-figure {
            max-width: 260px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 8px 25px rgba(30, 58, 138, 0.15));
        }
    </style>
</head>

<body>
    <div class="container-fluid px-0">
        <div class="row g-0 min-vh-100">
            <!-- Left brand/illustration panel (hidden on small screens) -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center brand-panel">
                <div class="text-center px-5">
                    <img src="/storage/LOGO-GPR.png" alt="GPR Logo" class="brand-figure mb-4">
                    <h2 class="fw-semibold text-navy" style="letter-spacing:.3px;">Welcome Back</h2>
                    <p class="text-navy-light mb-0">Sign in to continue to GPR</p>
                </div>
            </div>

            <!-- Right: Login form -->
            <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5 py-lg-0">
                <div class="w-100" style="max-width: 420px;">
                    <!-- Small-screen logo on top -->
                    <div class="text-center mb-4 d-lg-none">
                        <img src="/storage/LOGO-GPR.png" alt="GPR Logo" class="top-logo-sm">
                    </div>

                    <div class="login-card p-4 p-md-5 shadow-lg">
                        <div class="text-center mb-4">
                            <img src="/storage/LOGO-GPR.png" alt="GPR Logo" style="width: 56px; height: auto;" class="mb-2">
                            <h1 class="h4 mb-1 fw-semibold text-navy">Sign In</h1>
                            <p class="text-navy-light mb-0">Please login to your account</p>
                        </div>

                        <form method="POST" action="/" class="mb-3">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label text-navy fw-medium">Email</label>
                                <input type="email" id="email" name="email" class="form-control bg-clean" placeholder="you@example.com" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label text-navy fw-medium">Password</label>
                                <input type="password" id="password" name="password" class="form-control bg-clean" placeholder="••••••••" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>

                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                            <a href="/admin/password-reset/request" class="link-faded">Forgot Password?</a>
                            <a href="/register" class="link-faded">Register</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>