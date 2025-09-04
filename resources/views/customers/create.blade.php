@extends('layouts.app')

@section('title', 'Tambah Customer')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2"></i>Tambah Customer Baru
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <strong>Nama Lengkap <span class="text-danger">*</span></strong>
                                    </label>
                                    <input type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        id="name"
                                        name="name"
                                        value="{{ old('name') }}"
                                        placeholder="Masukkan nama lengkap"
                                        required>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <strong>Email <span class="text-danger">*</span></strong>
                                    </label>
                                    <input type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="Masukkan email"
                                        required>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="gender" class="form-label">Jenis Kelamin</label>
                                    <select class="form-select @error('gender') is-invalid @enderror"
                                        id="gender"
                                        name="gender">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                    @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror"
                                        id="status"
                                        name="status">
                                        <option value="blacklist" {{ old('status', 'blacklist') == 'blacklist' ? 'selected' : '' }}>Blacklist</option>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="job" class="form-label">Pekerjaan</label>
                                    <input type="text"
                                        class="form-control @error('job') is-invalid @enderror"
                                        id="job"
                                        name="job"
                                        value="{{ old('job') }}"
                                        placeholder="Masukkan pekerjaan">
                                    @error('job')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nomor HP</label>
                                    <div id="phone-container">
                                        <div class="input-group mb-2">
                                            <input type="text"
                                                class="form-control @error('phone_numbers.0') is-invalid @enderror"
                                                name="phone_numbers[]"
                                                value="{{ old('phone_numbers.0') }}"
                                                placeholder="Masukkan nomor HP">
                                            <button type="button"
                                                class="btn btn-success"
                                                onclick="addPhoneField()"
                                                title="Tambah Nomor HP">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        @error('phone_numbers.0')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Alamat Lengkap</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror"
                                        id="address"
                                        name="address"
                                        rows="3"
                                        placeholder="Masukkan alamat lengkap">{{ old('address') }}</textarea>
                                    @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="office_address" class="form-label">Alamat Kantor</label>
                                    <textarea class="form-control @error('office_address') is-invalid @enderror"
                                        id="office_address"
                                        name="office_address"
                                        rows="3"
                                        placeholder="Masukkan alamat kantor">{{ old('office_address') }}</textarea>
                                    @error('office_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Social Media -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="instagram_username" class="form-label">Instagram</label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text"
                                            class="form-control @error('instagram_username') is-invalid @enderror"
                                            id="instagram_username"
                                            name="instagram_username"
                                            value="{{ old('instagram_username') }}"
                                            placeholder="username">
                                    </div>
                                    @error('instagram_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>


                                <div class="mb-3">
                                    <label for="source_info" class="form-label">Sumber Informasi</label>
                                    <input type="text"
                                        class="form-control @error('source_info') is-invalid @enderror"
                                        id="source_info"
                                        name="source_info"
                                        value="{{ old('source_info') }}"
                                        placeholder="Website, Instagram, Referral, dll">
                                    @error('source_info')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emergency_contact_name" class="form-label">Nama Kontak Darurat</label>
                                    <input type="text"
                                        class="form-control @error('emergency_contact_name') is-invalid @enderror"
                                        id="emergency_contact_name"
                                        name="emergency_contact_name"
                                        value="{{ old('emergency_contact_name') }}"
                                        placeholder="Nama kontak darurat">
                                    @error('emergency_contact_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="emergency_contact_number" class="form-label">HP Kontak Darurat</label>
                                    <input type="text"
                                        class="form-control @error('emergency_contact_number') is-invalid @enderror"
                                        id="emergency_contact_number"
                                        name="emergency_contact_number"
                                        value="{{ old('emergency_contact_number') }}"
                                        placeholder="Nomor HP kontak darurat">
                                    @error('emergency_contact_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Simpan Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let phoneFieldCount = 1;

    function addPhoneField() {
        if (phoneFieldCount >= 10) {
            alert('Maksimal 10 nomor HP');
            return;
        }

        const container = document.getElementById('phone-container');
        const newField = document.createElement('div');
        newField.className = 'input-group mb-2';
        newField.innerHTML = `
        <input type="text" 
               class="form-control" 
               name="phone_numbers[]" 
               placeholder="Masukkan nomor HP">
        <button type="button" 
                class="btn btn-danger" 
                onclick="removePhoneField(this)"
                title="Hapus Nomor HP">
            <i class="fas fa-minus"></i>
        </button>
    `;

        container.appendChild(newField);
        phoneFieldCount++;
    }

    function removePhoneField(button) {
        button.closest('.input-group').remove();
        phoneFieldCount--;
    }

    // Phone number formatting
    document.addEventListener('DOMContentLoaded', function() {
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');

            if (value.startsWith('62')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                value = '+62' + value.substr(1);
            } else if (value && !value.startsWith('+')) {
                value = '+62' + value;
            }

            input.value = value;
        }

        // Apply formatting to existing phone fields
        document.addEventListener('input', function(e) {
            if (e.target.name === 'phone_numbers[]' || e.target.name === 'emergency_contact_number') {
                formatPhoneNumber(e.target);
            }
        });
    });
</script>
@endpush