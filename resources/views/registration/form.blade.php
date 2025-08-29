<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Global Photo Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .registration-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .required {
            color: #dc3545;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
        .section-title {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <div class="text-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-camera text-primary me-2"></i>
                    Global Photo Rental
                </h2>
                <p class="text-muted">Form Registrasi Penyewa</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('registration.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Section: Data Diri -->
                <h4 class="section-title">
                    <i class="fas fa-user me-2"></i>Data Diri
                </h4>

                <div class="row">
                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sumber Info -->
                    <div class="col-md-6 mb-3">
                        <label for="source_info" class="form-label">Mengetahui Global Photo Rental dari <span class="required">*</span></label>
                        <select class="form-select @error('source_info') is-invalid @enderror" 
                                id="source_info" name="source_info" required>
                            <option value="">Pilih Sumber Info</option>
                            <option value="Instagram" {{ old('source_info') == 'Instagram' ? 'selected' : '' }}>Instagram</option>
                            <option value="Teman" {{ old('source_info') == 'Teman' ? 'selected' : '' }}>Teman</option>
                            <option value="Google" {{ old('source_info') == 'Google' ? 'selected' : '' }}>Google</option>
                            <option value="Lainnya" {{ old('source_info') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('source_info')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <!-- Nama Lengkap -->
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Nama Lengkap (Sesuai KTP) <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jenis Kelamin <span class="required">*</span></label>
                        <div class="mt-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input @error('gender') is-invalid @enderror" 
                                       type="radio" name="gender" id="male" value="male" 
                                       {{ old('gender') == 'male' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="male">
                                    <i class="fas fa-male text-primary me-1"></i>Laki-laki
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input @error('gender') is-invalid @enderror" 
                                       type="radio" name="gender" id="female" value="female" 
                                       {{ old('gender') == 'female' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="female">
                                    <i class="fas fa-female text-danger me-1"></i>Perempuan
                                </label>
                            </div>
                        </div>
                        @error('gender')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Alamat -->
                <div class="mb-3">
                    <label for="address" class="form-label">Alamat Tinggal Sekarang (Ditulis Lengkap) <span class="required">*</span></label>
                    <textarea class="form-control @error('address') is-invalid @enderror" 
                              id="address" name="address" rows="3" required>{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <!-- No HP 1 -->
                    <div class="col-md-6 mb-3">
                        <label for="phone1" class="form-label">No. HP 1 <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control @error('phone1') is-invalid @enderror" 
                                   id="phone1" name="phone1" value="{{ old('phone1') }}" required>
                        </div>
                        @error('phone1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- No HP 2 -->
                    <div class="col-md-6 mb-3">
                        <label for="phone2" class="form-label">No. HP 2</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control @error('phone2') is-invalid @enderror" 
                                   id="phone2" name="phone2" value="{{ old('phone2') }}">
                        </div>
                        @error('phone2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <!-- Pekerjaan -->
                    <div class="col-md-6 mb-3">
                        <label for="job" class="form-label">Pekerjaan</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                            <input type="text" class="form-control @error('job') is-invalid @enderror" 
                                   id="job" name="job" value="{{ old('job') }}">
                        </div>
                        @error('job')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Instagram -->
                    <div class="col-md-6 mb-3">
                        <label for="instagram_username" class="form-label">Nama akun Instagram penyewa</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                            <input type="text" class="form-control @error('instagram_username') is-invalid @enderror" 
                                   id="instagram_username" name="instagram_username" value="{{ old('instagram_username') }}" 
                                   placeholder="@username">
                        </div>
                        @error('instagram_username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Alamat Kantor -->
                <div class="mb-3">
                    <label for="office_address" class="form-label">Alamat Kantor</label>
                    <textarea class="form-control @error('office_address') is-invalid @enderror" 
                              id="office_address" name="office_address" rows="2">{{ old('office_address') }}</textarea>
                    @error('office_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Foto KTP -->
                <div class="mb-4">
                    <label for="ktp_photo" class="form-label">Foto KTP <span class="required">*</span></label>
                    <input type="file" class="form-control @error('ktp_photo') is-invalid @enderror" 
                           id="ktp_photo" name="ktp_photo" accept="image/*" required>
                    <div class="form-text">Format: JPG, JPEG, PNG. Maksimal 2MB</div>
                    @error('ktp_photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Section: Kontak Emergency -->
                <h4 class="section-title">
                    <i class="fas fa-phone-alt me-2"></i>Kontak Emergency (Ortu/Suami/Istri/Saudara Kandung) <span class="required">*</span>
                </h4>

                <div class="row">
                    <!-- Nama Kontak Emergency -->
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_name" class="form-label">Nama Kontak Emergency <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-friends"></i></span>
                            <input type="text" class="form-control @error('emergency_contact_name') is-invalid @enderror" 
                                   id="emergency_contact_name" name="emergency_contact_name" 
                                   value="{{ old('emergency_contact_name') }}" required>
                        </div>
                        @error('emergency_contact_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- No HP Kontak Emergency -->
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_number" class="form-label">No. HP Kontak Emergency <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                            <input type="tel" class="form-control @error('emergency_contact_number') is-invalid @enderror" 
                                   id="emergency_contact_number" name="emergency_contact_number" 
                                   value="{{ old('emergency_contact_number') }}" required>
                        </div>
                        @error('emergency_contact_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-paper-plane me-2"></i>Daftar Sekarang
                    </button>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        Dengan mendaftar, Anda menyetujui syarat dan ketentuan Global Photo Rental
                    </small>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview foto KTP
        document.getElementById('ktp_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Bisa ditambahkan preview image jika diperlukan
                    console.log('File selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });

        // Auto format phone number
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.startsWith('62')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                value = '+62' + value.substring(1);
            }
            input.value = value;
        }

        document.getElementById('phone1').addEventListener('blur', function() {
            formatPhoneNumber(this);
        });

        document.getElementById('phone2').addEventListener('blur', function() {
            formatPhoneNumber(this);
        });

        document.getElementById('emergency_contact_number').addEventListener('blur', function() {
            formatPhoneNumber(this);
        });
    </script>
</body>
</html>
