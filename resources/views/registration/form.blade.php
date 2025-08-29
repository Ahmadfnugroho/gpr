<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
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
                <input type="file" class="form-control @error('ktp_photo') is-invalid @enderror"
                    id="ktp_photo" name="ktp_photo" accept="image/*" required>
                <div class="form-text">Format: JPG, JPEG, PNG. Maksimal 2MB</div>
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
                <input type="file" class="form-control @error('id_photo') is-invalid @enderror"
                    id="id_photo" name="id_photo" accept="image/*" required>
                <div class="form-text">Boleh diwatermark. Format: JPG, JPEG, PNG. Maksimal 2MB</div>
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
                <div class="form-text">Boleh diwatermark. Format: JPG, JPEG, PNG. Maksimal 2MB</div>
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
        // File validation function
        function validateFile(file, inputElement) {
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            
            if (file.size > maxSize) {
                alert('Ukuran file tidak boleh lebih dari 2MB. Ukuran file Anda: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                inputElement.value = '';
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung. Gunakan format JPG, JPEG, PNG, atau GIF.');
                inputElement.value = '';
                return false;
            }
            
            return true;
        }

        // Preview dan validasi foto KTP
        document.getElementById('ktp_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (validateFile(file, this)) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // File is valid and ready for upload
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Validasi foto ID tambahan 1
        document.getElementById('id_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                validateFile(file, this);
            }
        });

        // Validasi foto ID tambahan 2
        document.getElementById('id_photo_2').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                validateFile(file, this);
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
                fetch('/api/regions/provinces')
                    .then(response => response.json())
                    .then(data => {
                        provinceSelect.empty().append('<option value="">Pilih Provinsi</option>');
                        data.forEach(province => {
                            provinceSelect.append(`<option value="${province.id}|${province.name}">${province.name}</option>`);
                        });
                        provinceSelect.select2();
                    })
                    .catch(error => {
                        console.error('Error loading provinces:', error);
                        provinceSelect.empty().append('<option value="">Error loading provinces</option>').select2();
                    });
            }

            function loadRegencies(provinceId) {
                citySelect.prop('disabled', true).empty().append('<option value="">Loading...</option>').select2();

                fetch(`/api/regions/regencies/${provinceId}`)
                    .then(response => response.json())
                    .then(data => {
                        citySelect.empty().append('<option value="">Pilih Kab/Kota</option>');
                        data.forEach(regency => {
                            citySelect.append(`<option value="${regency.id}|${regency.name}">${regency.name}</option>`);
                        });
                        citySelect.prop('disabled', false).select2();
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