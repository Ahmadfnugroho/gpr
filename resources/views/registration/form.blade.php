<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrasi - Global Photo Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1f2937;
        }

        .registration-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2.5rem;
            margin: 2rem auto;
            max-width: 700px;
            border: 1px solid #e5e7eb;
        }

        .form-section {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .required {
            color: #dc2626;
        }

        .form-control,
        .form-select {
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .input-group-text {
            background: #f3f4f6;
            border: 1.5px solid #d1d5db;
            color: #6b7280;
            border-radius: 8px 0 0 8px;
        }

        .input-group .form-control {
            border-radius: 0 8px 8px 0;
        }

        .btn-primary {
            background: #3b82f6;
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 500;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .section-title {
            color: #1f2937;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-subtitle {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .form-check-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        .form-text {
            color: #6b7280;
            font-size: 0.825rem;
        }

        .invalid-feedback {
            color: #dc2626;
            font-size: 0.825rem;
        }

        .address-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .address-grid {
                grid-template-columns: 1fr;
            }

            .registration-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        .search-dropdown {
            position: relative;
        }

        .search-dropdown input {
            cursor: pointer;
        }

        .search-dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-dropdown-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s;
        }

        .search-dropdown-item:hover,
        .search-dropdown-item.selected {
            background-color: #f3f4f6;
        }

        .search-dropdown-item:last-child {
            border-bottom: none;
        }

        /* Select2 custom styling */
        .select2-container--default .select2-selection--single {
            height: 46px;
            padding: 0.5rem 0.5rem;
            font-size: 0.95rem;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
            color: #1f2937;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #3b82f6;
        }

        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 0 0 8px 8px;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 0.5rem;
        }

        .select2-container--default .select2-selection--single:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
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
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-user"></i>
                        Data Diri
                    </h4>
                    <p class="section-subtitle">Informasi pribadi dan kontak Anda</p>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div id="email-availability" class="mt-2" style="display: none;"></div>
                        @error('email')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                            @if(str_contains($message, 'sudah terdaftar'))
                            <div class="mt-2 p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    <strong class="text-info">Bantuan Customer Service</strong>
                                </div>
                                <div class="small">
                                    <div class="mb-1">
                                        <i class="fab fa-whatsapp text-success me-2"></i>
                                        <strong>WhatsApp:</strong>
                                        <a href="https://wa.me/6281212349564" target="_blank" class="text-decoration-none">
                                            +62 812-1234-9564
                                        </a>
                                    </div>
                                    <div>
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <strong>Email:</strong>
                                        <a href="mailto:global.photorental@gmail.com" class="text-decoration-none">
                                            global.photorental@gmail.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @enderror
                    </div>
                    <!-- Sumber Info -->
                    <div class="mb-3">
                        <label for="source_info" class="form-label">Mengetahui Global Photo Rental dari <span class="required">*</span></label>
                        <select class="form-select @error('source_info') is-invalid @enderror"
                            id="source_info" name="source_info" required>
                            <option value="">Pilih Sumber Info</option>
                            <option value="Instagram" {{ old('source_info') == 'Instagram' ? 'selected' : '' }}>Instagram</option>
                            <option value="TikTok" {{ old('source_info') == 'TikTok' ? 'selected' : '' }}>TikTok</option>
                            <option value="Teman" {{ old('source_info') == 'Teman' ? 'selected' : '' }}>Teman</option>
                            <option value="Google" {{ old('source_info') == 'Google' ? 'selected' : '' }}>Google</option>
                            <option value="Lainnya" {{ old('source_info') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('source_info')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="mb-3">
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
                    <div class="mb-3">
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

                    <!-- Alamat Bertingkat -->
                    <div class="mb-3">
                        <label class="form-label">Alamat Tinggal Sekarang <span class="required">*</span></label>

                        <!-- Provinsi -->
                        <div class="mb-2">
                            <select class="form-select @error('province') is-invalid @enderror"
                                id="province" name="province" required>
                                <option value="">Pilih Provinsi</option>
                            </select>
                            @error('province')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kab/Kota -->
                        <div class="mb-2">
                            <select class="form-select @error('city') is-invalid @enderror"
                                id="city" name="city" required disabled>
                                <option value="">Pilih Kab/Kota</option>
                            </select>
                            @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kecamatan -->
                        <div class="mb-2">
                            <select class="form-select @error('district') is-invalid @enderror"
                                id="district" name="district" required disabled>
                                <option value="">Pilih Kecamatan</option>
                            </select>
                            @error('district')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Kelurahan -->
                        <div class="mb-2">
                            <select class="form-select @error('village') is-invalid @enderror"
                                id="village" name="village" required disabled>
                                <option value="">Pilih Kelurahan</option>
                            </select>
                            @error('village')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Alamat Detail -->
                        <div class="mb-2">
                            <textarea class="form-control @error('address_detail') is-invalid @enderror"
                                id="address_detail" name="address_detail" rows="3"
                                placeholder="Tulis alamat lengkap (RT/RW, Nama Jalan, No. Rumah, dll)" required>{{ old('address_detail') }}</textarea>
                            @error('address_detail')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <!-- No HP 1 -->
                    <div class="mb-3">
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
                    <div class="mb-3">
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

                    <!-- Pekerjaan -->
                    <div class="mb-3">
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
                    <div class="mb-3">
                        <label for="instagram_username" class="form-label">Nama akun Instagram penyewa<span class="required">*</span></label>
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

                    <!-- Alamat Kantor -->
                    <div class="mb-3">
                        <label for="office_address" class="form-label">Alamat Kantor</label>
                        <textarea class="form-control @error('office_address') is-invalid @enderror"
                            id="office_address" name="office_address" rows="2">{{ old('office_address') }}</textarea>
                        @error('office_address')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Section: Upload Dokumen -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-images"></i>
                            Upload Dokumen Identitas
                        </h4>
                        <p class="section-subtitle">Unggah foto dokumen identitas yang diperlukan (3 dokumen wajib)</p>

                        <!-- Foto KTP -->
                        <div class="mb-3">
                            <label for="ktp_photo" class="form-label">1. Foto KTP <span class="required">*</span></label>
                            <input type="file" class="form-control image-upload @error('ktp_photo') is-invalid @enderror"
                                id="ktp_photo" name="ktp_photo" accept="image/*" required>
                            <div class="form-text">Format: JPG, JPEG, PNG, WebP. Maksimal 10MB. File akan dioptimalkan secara otomatis.</div>
                            <div id="ktp_photo-notification" class="alert alert-info mt-2" style="display: none;"></div>
                            <div id="ktp_photo-progress" class="mt-2" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Mengupload foto KTP...</small>
                                    <small id="ktp_photo-progress-text" class="text-muted">0%</small>
                                </div>
                                <div class="progress">
                                    <div id="ktp_photo-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            @error('ktp_photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Foto ID Tambahan 1 -->
                        <div class="mb-3">
                            <label for="id_photo" class="form-label">2. Foto ID Tambahan 1 <span class="required">*</span></label>
                            <select class="form-select mb-2 @error('id_type') is-invalid @enderror" id="id_type" name="id_type" required>
                                <option value="">Pilih Jenis ID</option>
                                <option value="KK" {{ old('id_type') == 'KK' ? 'selected' : '' }}>Kartu Keluarga (KK)</option>
                                <option value="SIM" {{ old('id_type') == 'SIM' ? 'selected' : '' }}>SIM (Surat Izin Mengemudi)</option>
                                <option value="NPWP" {{ old('id_type') == 'NPWP' ? 'selected' : '' }}>NPWP</option>
                                <option value="STNK" {{ old('id_type') == 'STNK' ? 'selected' : '' }}>STNK</option>
                                <option value="BPKB" {{ old('id_type') == 'BPKB' ? 'selected' : '' }}>BPKB</option>
                                <option value="Passport" {{ old('id_type') == 'Passport' ? 'selected' : '' }}>Passport</option>
                                <option value="BPJS" {{ old('id_type') == 'BPJS' ? 'selected' : '' }}>BPJS</option>
                                <option value="ID_Kerja" {{ old('id_type') == 'ID_Kerja' ? 'selected' : '' }}>ID Card Kerja</option>
                            </select>
                            @error('id_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <input type="file" class="form-control image-upload @error('id_photo') is-invalid @enderror"
                                id="id_photo" name="id_photo" accept="image/*" required>
                            <div class="form-text">Format: JPG, JPEG, PNG, WebP. Maksimal 10MB. File akan dioptimalkan secara otomatis.</div>
                            <div id="id_photo-notification" class="alert alert-info mt-2" style="display: none;"></div>
                            <div id="id_photo-progress" class="mt-2" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Mengupload foto ID tambahan 1...</small>
                                    <small id="id_photo-progress-text" class="text-muted">0%</small>
                                </div>
                                <div class="progress">
                                    <div id="id_photo-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            @error('id_photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Foto ID Tambahan 2 -->
                        <div class="mb-4">
                            <label for="id_photo_2" class="form-label">3. Foto ID Tambahan 2 <span class="required">*</span></label>
                            <select class="form-select mb-2 @error('id_type_2') is-invalid @enderror" id="id_type_2" name="id_type_2" required>
                                <option value="">Pilih Jenis ID</option>
                                <option value="KK" {{ old('id_type_2') == 'KK' ? 'selected' : '' }}>Kartu Keluarga (KK)</option>
                                <option value="SIM" {{ old('id_type_2') == 'SIM' ? 'selected' : '' }}>SIM (Surat Izin Mengemudi)</option>
                                <option value="NPWP" {{ old('id_type_2') == 'NPWP' ? 'selected' : '' }}>NPWP</option>
                                <option value="STNK" {{ old('id_type_2') == 'STNK' ? 'selected' : '' }}>STNK</option>
                                <option value="BPKB" {{ old('id_type_2') == 'BPKB' ? 'selected' : '' }}>BPKB</option>
                                <option value="Passport" {{ old('id_type_2') == 'Passport' ? 'selected' : '' }}>Passport</option>
                                <option value="BPJS" {{ old('id_type_2') == 'BPJS' ? 'selected' : '' }}>BPJS</option>
                                <option value="ID_Kerja" {{ old('id_type_2') == 'ID_Kerja' ? 'selected' : '' }}>ID Card Kerja</option>
                            </select>
                            @error('id_type_2')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <input type="file" class="form-control @error('id_photo_2') is-invalid @enderror"
                                id="id_photo_2" name="id_photo_2" accept="image/*" required>
                            <div class="form-text">Boleh diwatermark. Format: JPG, JPEG, PNG, WebP. Maksimal 10MB</div>
                            <div id="id_photo_2-notification" class="alert alert-info mt-2" style="display: none;"></div>
                            <div id="id_photo_2-progress" class="mt-2" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Mengupload foto ID tambahan 2...</small>
                                    <small id="id_photo_2-progress-text" class="text-muted">0%</small>
                                </div>
                                <div class="progress">
                                    <div id="id_photo_2-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            @error('id_photo_2')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <!-- Section: Kontak Emergency -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-phone-alt"></i>
                            Kontak Emergency <span class="required">*</span>
                        </h4>
                        <p class="section-subtitle">(Orang Tua/Suami/Istri/Saudara Kandung)</p>

                        <!-- Nama Kontak Emergency -->
                        <div class="mb-3">
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
                        <div class="mb-3">
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Function to check if browser supports WebP
        function browserSupportsWebP() {
            const elem = document.createElement('canvas');
            if (!!(elem.getContext && elem.getContext('2d'))) {
                // Was able or not to get WebP representation
                return elem.toDataURL('image/webp').indexOf('data:image/webp') === 0;
            }
            // Very old browser like IE 8, canvas not supported
            return false;
        }

        // File validation and compression function
        async function validateFile(file, inputElement) {
            const maxSize = 10 * 1024 * 1024; // 10MB in bytes
            const serverMaxSize = 1 * 1024 * 1024; // 1MB in bytes (server limit)
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];

            if (file.size > maxSize) {
                alert('Ukuran file tidak boleh lebih dari 10MB. Ukuran file Anda: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                inputElement.value = '';
                return false;
            }

            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung. Gunakan format JPG, JPEG, PNG, WebP, atau GIF.');
                inputElement.value = '';
                return false;
            }

            // Always compress images for optimal size, regardless of original size
            try {
                const compressedFile = await compressImage(file);

                // Replace the file in the input element with the compressed one
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                inputElement.files = dataTransfer.files;

                console.log(`File optimized: ${(file.size / 1024 / 1024).toFixed(2)}MB → ${(compressedFile.size / 1024 / 1024).toFixed(2)}MB`);

                // Show compression notification
                const fileId = inputElement.id;
                const notificationElement = document.getElementById(`${fileId}-notification`);

                // Calculate compression percentage
                const compressionPercent = Math.round((1 - (compressedFile.size / file.size)) * 100);
                const formatInfo = compressedFile.type.includes('webp') ? ' (WebP)' : '';
                const sizeInfo = `${(file.size / 1024 / 1024).toFixed(2)}MB → ${(compressedFile.size / 1024 / 1024).toFixed(2)}MB (${compressionPercent}% lebih kecil${formatInfo})`;



                return true;
            } catch (error) {
                console.error('Compression error:', error);
                // If compression fails, continue with original file if under 10MB
                return true;
            }
        }

        // Image compression function with WebP support and adaptive compression
        async function compressImage(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function(event) {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        const targetWidth = 800;

                        // Resize proporsional
                        if (width > targetWidth) {
                            const ratio = width / height;
                            width = targetWidth;
                            height = Math.round(targetWidth / ratio);
                        }

                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#FFFFFF';
                        ctx.fillRect(0, 0, width, height);
                        ctx.drawImage(img, 0, 0, width, height);

                        // Format: WebP jika didukung, else JPG
                        const supportsWebP = browserSupportsWebP();
                        const mimeType = supportsWebP ? 'image/webp' : 'image/jpeg';

                        // Fungsi rekursif untuk turunkan kualitas
                        function tryCompress(quality) {
                            canvas.toBlob(function(blob) {
                                if (!blob) return reject(new Error('Compression failed'));

                                if (blob.size < 900 * 1024 || quality <= 0.2) {
                                    // Sukses <900KB atau kualitas terlalu rendah
                                    const fileName = file.name.replace(/\.[^/.]+$/, '') + (supportsWebP ? '.webp' : '.jpg');
                                    const compressedFile = new File([blob], fileName, {
                                        type: mimeType,
                                        lastModified: Date.now()
                                    });
                                    resolve(compressedFile);
                                } else {
                                    // Coba lagi dengan kualitas lebih rendah
                                    tryCompress(quality - 0.1);
                                }
                            }, mimeType, quality);
                        }

                        tryCompress(0.7); // Mulai dari 70%
                    };
                    img.onerror = () => reject(new Error('Image load failed'));
                };
                reader.onerror = () => reject(new Error('File read failed'));
            });
        }

        // Preview dan validasi foto KTP
        document.getElementById('ktp_photo').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (file) {
                await validateFile(file, this);
                const reader = new FileReader();
                reader.onload = function(e) {
                    // File is valid and ready for upload
                };
                reader.readAsDataURL(file);
            }
        });

        // Validasi foto ID tambahan 1
        document.getElementById('id_photo').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (file) {
                await validateFile(file, this);
            }
        });

        // Validasi foto ID tambahan 2
        document.getElementById('id_photo_2').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (file) {
                await validateFile(file, this);
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

        // Email availability checking
        let emailCheckTimeout;
        document.getElementById('email').addEventListener('input', function() {
            clearTimeout(emailCheckTimeout);
            const email = this.value.trim();

            if (email && email.includes('@') && email.includes('.')) {
                emailCheckTimeout = setTimeout(() => {
                    checkEmailAvailability(email);
                }, 1000); // Check after 1 second of no typing
            } else {
                hideEmailAvailability();
            }
        });

        function checkEmailAvailability(email) {
            const availabilityDiv = document.getElementById('email-availability');
            availabilityDiv.innerHTML = '<div class="small text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Mengecek ketersediaan email...</div>';
            availabilityDiv.style.display = 'block';

            // Create a simple check by making a request to see if email exists
            fetch('/api/check-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="_token"]').value
                    },
                    body: JSON.stringify({
                        email: email
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        availabilityDiv.innerHTML = `
                        <div class="alert alert-warning small py-2 mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Email sudah terdaftar!</strong> 
                            Hubungi customer service untuk bantuan:
                            <div class="mt-1">
                                <a href="https://wa.me/6281212349564" target="_blank" class="btn btn-success btn-sm me-2">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                                <a href="mailto:global.photorental@gmail.com" class="btn btn-primary btn-sm">
                                    <i class="fas fa-envelope"></i> Email
                                </a>
                            </div>
                        </div>
                    `;
                    } else {
                        availabilityDiv.innerHTML = '<div class="small text-success"><i class="fas fa-check me-1"></i>Email tersedia</div>';
                    }
                })
                .catch(error => {
                    console.error('Error checking email:', error);
                    hideEmailAvailability();
                });
        }

        function hideEmailAvailability() {
            document.getElementById('email-availability').style.display = 'none';
        }

        // Enhanced upload progress tracking
        function showUploadProgress(inputId, show = true) {
            const progressContainer = document.getElementById(`${inputId}-progress`);
            const progressBar = document.getElementById(`${inputId}-progress-bar`);
            const progressText = document.getElementById(`${inputId}-progress-text`);

            if (progressContainer) {
                if (show) {
                    progressContainer.style.display = 'block';
                    updateUploadProgress(inputId, 0);
                } else {
                    progressContainer.style.display = 'none';
                }
            }
        }

        function updateUploadProgress(inputId, percentage) {
            const progressBar = document.getElementById(`${inputId}-progress-bar`);
            const progressText = document.getElementById(`${inputId}-progress-text`);

            if (progressBar && progressText) {
                progressBar.style.width = percentage + '%';
                progressText.textContent = percentage + '%';

                if (percentage >= 100) {
                    progressBar.classList.remove('progress-bar-animated');
                    setTimeout(() => {
                        showUploadProgress(inputId, false);
                    }, 1500);
                }
            }
        }

        // Form validation with enhanced image optimization and progress
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                // Add event listeners to image upload inputs for client-side compression
                const imageInputs = document.querySelectorAll('.image-upload');
                imageInputs.forEach(input => {
                    input.addEventListener('change', async function(event) {
                        if (this.files && this.files[0]) {
                            const inputId = this.id;

                            // Show progress bar
                            showUploadProgress(inputId, true);
                            updateUploadProgress(inputId, 20);

                            // Simulate upload progress
                            setTimeout(() => updateUploadProgress(inputId, 40), 200);
                            setTimeout(() => updateUploadProgress(inputId, 60), 400);

                            // Validate and compress the file
                            const isValid = await validateFile(this.files[0], this);

                            if (isValid) {
                                updateUploadProgress(inputId, 80);
                                setTimeout(() => updateUploadProgress(inputId, 100), 300);
                            } else {
                                // Hide progress if validation failed
                                showUploadProgress(inputId, false);
                            }
                        }
                    });
                });
            });
        })();

        // Region Dropdown API Integration
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = $('#province');
            const citySelect = $('#city');
            const districtSelect = $('#district');
            const villageSelect = $('#village');

            // Initialize Select2 for all region dropdowns
            provinceSelect.select2({
                placeholder: 'Pilih Provinsi',
                allowClear: false
            });

            citySelect.select2({
                placeholder: 'Pilih Kab/Kota',
                allowClear: false
            });

            districtSelect.select2({
                placeholder: 'Pilih Kecamatan',
                allowClear: false
            });

            villageSelect.select2({
                placeholder: 'Pilih Kelurahan',
                allowClear: false
            });

            // Load provinces on page load
            loadProvinces();

            // Province change event
            provinceSelect.on('change', function() {
                const provinceId = this.value.split('|')[0]; // Get ID from value
                if (provinceId) {
                    loadRegencies(provinceId);
                    resetDropdown(districtSelect, 'Pilih Kecamatan');
                    resetDropdown(villageSelect, 'Pilih Kelurahan');
                } else {
                    resetDropdown(citySelect, 'Pilih Kab/Kota');
                    resetDropdown(districtSelect, 'Pilih Kecamatan');
                    resetDropdown(villageSelect, 'Pilih Kelurahan');
                }
            });

            // City change event
            citySelect.on('change', function() {
                const cityId = this.value.split('|')[0];
                if (cityId) {
                    loadDistricts(cityId);
                    resetDropdown(villageSelect, 'Pilih Kelurahan');
                } else {
                    resetDropdown(districtSelect, 'Pilih Kecamatan');
                    resetDropdown(villageSelect, 'Pilih Kelurahan');
                }
            });

            // District change event
            districtSelect.on('change', function() {
                const districtId = this.value.split('|')[0];
                if (districtId) {
                    loadVillages(districtId);
                } else {
                    resetDropdown(villageSelect, 'Pilih Kelurahan');
                }
            });

            function loadProvinces() {
                console.log('Loading provinces...');
                fetch('/api/regions/provinces')
                    .then(response => {
                        console.log('Province response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Province data received:', data);
                        provinceSelect.empty().append('<option value="">Pilih Provinsi</option>');

                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(province => {
                                provinceSelect.append(`<option value="${province.id}|${province.name}">${province.name}</option>`);
                            });
                        } else {
                            console.warn('No province data or invalid format:', data);
                            provinceSelect.append('<option value="">Tidak ada data provinsi</option>');
                        }

                        provinceSelect.select2();
                        console.log('Provinces loaded successfully');
                    })
                    .catch(error => {
                        console.error('Error loading provinces:', error);
                        provinceSelect.empty().append('<option value="">Error loading provinces</option>').select2();
                    });
            }

            function loadRegencies(provinceId) {
                console.log('Loading regencies for province:', provinceId);
                citySelect.prop('disabled', true).empty().append('<option value="">Loading...</option>').select2();

                fetch(`/api/regions/regencies/${provinceId}`)
                    .then(response => {
                        console.log('Regency response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Regency data received:', data);
                        citySelect.empty().append('<option value="">Pilih Kab/Kota</option>');

                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(regency => {
                                citySelect.append(`<option value="${regency.id}|${regency.name}">${regency.name}</option>`);
                            });
                        } else {
                            console.warn('No regency data or invalid format:', data);
                            citySelect.append('<option value="">Tidak ada data kab/kota</option>');
                        }

                        citySelect.prop('disabled', false).select2();
                        console.log('Regencies loaded successfully');
                    })
                    .catch(error => {
                        console.error('Error loading regencies:', error);
                        citySelect.empty().append('<option value="">Error loading cities</option>').prop('disabled', false).select2();
                    });
            }

            function loadDistricts(regencyId) {
                districtSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>').select2();

                fetch(`/api/regions/districts/${regencyId}`)
                    .then(response => response.json())
                    .then(data => {
                        districtSelect.empty().append('<option value="">Pilih Kecamatan</option>');
                        data.forEach(district => {
                            districtSelect.append(`<option value="${district.id}|${district.name}">${district.name}</option>`);
                        });
                        districtSelect.prop('disabled', false).select2();
                    })
                    .catch(error => {
                        console.error('Error loading districts:', error);
                        districtSelect.empty().append('<option value="">Error loading districts</option>').prop('disabled', false).select2();
                    });
            }

            function loadVillages(districtId) {
                villageSelect.prop('disabled', true).empty().append('<option value="">Loading...</option>').select2();

                fetch(`/api/regions/villages/${districtId}`)
                    .then(response => response.json())
                    .then(data => {
                        villageSelect.empty().append('<option value="">Pilih Kelurahan</option>');
                        data.forEach(village => {
                            villageSelect.append(`<option value="${village.id}|${village.name}">${village.name}</option>`);
                        });
                        villageSelect.prop('disabled', false).select2();
                    })
                    .catch(error => {
                        console.error('Error loading villages:', error);
                        villageSelect.empty().append('<option value="">Error loading villages</option>').prop('disabled', false).select2();
                    });
            }

            function resetDropdown(selectElement, placeholder) {
                selectElement.empty().append(`<option value="">${placeholder}</option>`).prop('disabled', true).select2();
            }
        });
    </script>
</body>

</html>