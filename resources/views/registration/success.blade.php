<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Berhasil - Global Photo Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .success-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            margin: 2rem auto;
            max-width: 600px;
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h2 class="fw-bold mb-3">Registrasi Berhasil!</h2>
            
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('message'))
                <div class="alert alert-info border-0 shadow-sm">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('message') }}
                </div>
            @endif

            <div class="mb-4">
                <h5 class="text-primary mb-3">
                    <i class="fas fa-envelope me-2"></i>Langkah Selanjutnya
                </h5>
                <p class="text-muted">
                    1. Cek email Anda untuk verifikasi akun<br>
                    2. Klik link verifikasi di email tersebut<br>
                    3. Tunggu konfirmasi dari admin Global Photo Rental<br>
                    4. Setelah disetujui, Anda dapat mulai menyewa peralatan
                </p>
            </div>

            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Status akun Anda saat ini: PENDING</strong><br>
                <small>Admin akan meninjau data Anda dan mengubah status menjadi ACTIVE jika semua data valid.</small>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="{{ route('registration.form') }}" class="btn btn-outline-primary">
                    <i class="fas fa-plus me-2"></i>Daftar Lagi
                </a>
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Kembali ke Beranda
                </a>
            </div>

            <div class="mt-4 pt-4 border-top">
                <h6 class="text-primary">
                    <i class="fas fa-phone me-2"></i>Butuh Bantuan?
                </h6>
                <p class="text-muted small">
                    Hubungi kami di:<br>
                    WhatsApp: <a href="https://wa.me/6281234567890" class="text-decoration-none">+62 812-3456-7890</a><br>
                    Email: <a href="mailto:info@globalphotorental.com" class="text-decoration-none">info@globalphotorental.com</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
