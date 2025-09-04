@extends('layouts.app')

@section('title', 'Import Customer')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-upload me-2"></i>Import Customer dari Excel
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Instructions -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-1"></i>Petunjuk Import:</h6>
                        <ol class="mb-0">
                            <li>Download template Excel dengan mengklik tombol "Download Template" di bawah</li>
                            <li>Isi data customer sesuai format yang tersedia</li>
                            <li>Upload file Excel yang sudah diisi</li>
                            <li>Sistem akan memvalidasi dan mengimpor data secara otomatis</li>
                        </ol>
                    </div>

                    <!-- Download Template Button -->
                    <div class="mb-4">
                        <a href="{{ route('customers.import.template') }}"
                            class="btn btn-success">
                            <i class="fas fa-download me-1"></i>Download Template Excel
                        </a>
                    </div>

                    <!-- Import Form -->
                    <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="excel_file" class="form-label">
                                        <strong>File Excel Customer <span class="text-danger">*</span></strong>
                                    </label>
                                    <input type="file"
                                        class="form-control @error('excel_file') is-invalid @enderror"
                                        id="excel_file"
                                        name="excel_file"
                                        accept=".xlsx,.xls,.csv"
                                        required>
                                    <small class="form-text text-muted">
                                        Format yang didukung: .xlsx, .xls, .csv (Maksimal 2MB)
                                    </small>
                                    @error('excel_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        id="update_existing"
                                        name="update_existing"
                                        value="1"
                                        {{ old('update_existing') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="update_existing">
                                        Update data customer yang sudah ada (berdasarkan email)
                                    </label>
                                    <small class="form-text text-muted">
                                        Jika tidak dicentang, customer dengan email yang sudah terdaftar akan diabaikan
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>Import Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Expected Format Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>Format Data Yang Diharapkan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr class="table-light">
                                    <th>Kolom</th>
                                    <th>Keterangan</th>
                                    <th>Contoh</th>
                                    <th>Wajib</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>nama_lengkap</code></td>
                                    <td>Nama lengkap customer</td>
                                    <td>John Doe</td>
                                    <td><span class="badge bg-danger">Ya</span></td>
                                </tr>
                                <tr>
                                    <td><code>email</code></td>
                                    <td>Email customer (harus unik)</td>
                                    <td>john@example.com</td>
                                    <td><span class="badge bg-danger">Ya</span></td>
                                </tr>
                                <tr>
                                    <td><code>nomor_hp_1</code></td>
                                    <td>Nomor HP utama</td>
                                    <td>081234567890</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>nomor_hp_2</code></td>
                                    <td>Nomor HP kedua</td>
                                    <td>087654321098</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>jenis_kelamin</code></td>
                                    <td>Jenis kelamin: male, female</td>
                                    <td>male</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>status</code></td>
                                    <td>Status: active, inactive, blacklist</td>
                                    <td>active</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>alamat</code></td>
                                    <td>Alamat lengkap</td>
                                    <td>Jl. Contoh No. 123, Jakarta</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>pekerjaan</code></td>
                                    <td>Pekerjaan customer</td>
                                    <td>Software Developer</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>alamat_kantor</code></td>
                                    <td>Alamat kantor</td>
                                    <td>Jl. Kantor No. 456, Jakarta</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>instagram</code></td>
                                    <td>Username Instagram</td>
                                    <td>johndoe</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>kontak_emergency</code></td>
                                    <td>Nama kontak darurat</td>
                                    <td>Jane Doe</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>hp_emergency</code></td>
                                    <td>HP kontak darurat</td>
                                    <td>081987654321</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                                <tr>
                                    <td><code>sumber_info</code></td>
                                    <td>Sumber informasi</td>
                                    <td>Website, Instagram, Referral</td>
                                    <td><span class="badge bg-secondary">Tidak</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <h6><i class="fas fa-exclamation-triangle me-1"></i>Catatan Penting:</h6>
                        <ul class="mb-0">
                            <li>Nomor HP akan otomatis diformat ke format Indonesia (+62)</li>
                            <li>Jenis kelamin yang didukung: laki-laki, perempuan, male, female, m, f, dll</li>
                            <li>Status yang didukung: active, inactive, blacklist (default: blacklist)</li>
                            <li>Instagram username tidak perlu @, akan dihapus otomatis</li>
                            <li>Password default untuk semua customer yang diimport: password123</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('excel_file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const fileSize = file.size / 1024 / 1024; // in MB
            if (fileSize > 2) {
                alert('File terlalu besar! Maksimal 2MB.');
                e.target.value = '';
            }

            const validTypes = ['application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv'
            ];
            if (!validTypes.includes(file.type)) {
                alert('Tipe file tidak didukung! Gunakan .xlsx, .xls, atau .csv');
                e.target.value = '';
            }
        }
    });
</script>
@endpush