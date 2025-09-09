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
        --bg-dark: #0b0b0e;
        --bg-accent1: #2d0b59;
        --bg-accent2: #5b1bb2;
        --card-bg: rgba(255,255,255,0.06);
        --card-border: rgba(255,255,255,0.12);
      }

      body {
        min-height: 100vh;
        background: radial-gradient(1200px 600px at 50% -10%, rgba(123, 44, 191, 0.35), transparent),
                    radial-gradient(900px 900px at 50% 110%, rgba(123, 44, 191, 0.25), transparent),
                    linear-gradient(180deg, #1a1028 0%, #0e0c15 60%, #0b0b0e 100%);
        color: #fff;
      }

      .brand-panel {
        background: radial-gradient(1000px 600px at 20% 10%, rgba(255,255,255,0.06), transparent),
                    linear-gradient(160deg, rgba(139, 92, 246, 0.25) 0%, rgba(139, 92, 246, 0.05) 60%, transparent 100%);
        border-right: 1px solid rgba(255,255,255,0.08);
      }

      .login-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--card-border);
        border-radius: 16px;
      }

      .form-control.bg-glass {
        background: rgba(255,255,255,0.06);
        border-color: rgba(255,255,255,0.15);
        color: #fff;
      }
      .form-control.bg-glass::placeholder { color: rgba(255,255,255,0.5); }
      .form-control.bg-glass:focus {
        background: rgba(255,255,255,0.1);
        border-color: rgba(255,255,255,0.35);
        box-shadow: 0 0 0 .25rem rgba(139, 92, 246, 0.25);
        color: #fff;
      }

      .btn-primary {
        background: linear-gradient(90deg, #ffffff, #d9d9d9);
        border: none;
        color: #111;
      }
      .btn-primary:hover { filter: brightness(0.95); color: #000; }

      .link-faded { color: rgba(255,255,255,0.7); text-decoration: none; }
      .link-faded:hover { color: #fff; text-decoration: underline; }

      /* Small-screen banner image (logo) */
      .top-logo-sm {
        max-width: 140px;
        height: auto;
        filter: drop-shadow(0 6px 20px rgba(0,0,0,0.35));
      }

      /* Left side illustration/logo */
      .brand-figure {
        max-width: 260px;
        width: 100%;
        height: auto;
        filter: drop-shadow(0 12px 40px rgba(91,27,178,0.45));
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
            <h2 class="fw-semibold" style="letter-spacing:.3px;">Welcome Back</h2>
            <p class="text-white-50 mb-0">Sign in to continue to GPR</p>
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
                <h1 class="h4 mb-1 fw-semibold">Sign In</h1>
                <p class="text-white-50 mb-0">Please login to your account</p>
              </div>

              <form method="POST" action="{{ route('login') }}" class="mb-3">
                @csrf
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input type="email" id="email" name="email" class="form-control bg-glass" placeholder="you@example.com" required autofocus>
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input type="password" id="password" name="password" class="form-control bg-glass" placeholder="••••••••" required>
                </div>
                <div class="d-grid gap-2">
                  <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
              </form>

              <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
                @if (Route::has('password.request'))
                  <a href="{{ route('password.request') }}" class="link-faded">Forgot Password?</a>
                @else
                  <a href="/forgot-password" class="link-faded">Forgot Password?</a>
                @endif

                @if (Route::has('register'))
                  <a href="{{ route('register') }}" class="link-faded">Register</a>
                @else
                  <a href="/register" class="link-faded">Register</a>
                @endif
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

