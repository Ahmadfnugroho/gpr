@extends('layouts.app')

@section('title', 'Detail Customer - ' . $customer->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Detail Customer: {{ $customer->name }}
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Yakin ingin menghapus customer ini?')">
                                <i class="fas fa-trash me-1"></i>Hapus
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Photo Section -->
                        <div class="col-md-3 mb-4">
                            <div class="text-center">
                                @if($customer->customerPhotos->count() > 0)
                                    <div class="position-relative mb-3">
                                        <img src="{{ Storage::url($customer->customerPhotos->first()->photo_path) }}" 
                                             alt="Customer Photo" 
                                             class="img-fluid rounded-circle border"
                                             style="width: 150px; height: 150px; object-fit: cover;">
                                        <button type="button" 
                                                class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle"
                                                onclick="showLargePhoto('{{ Storage::url($customer->customerPhotos->first()->photo_path) }}', '{{ $customer->name }}')"
                                                title="Lihat Foto Besar"
                                                style="width: 40px; height: 40px;">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                    </div>
                                    @if($customer->customerPhotos->count() > 1)
                                        <small class="text-muted">{{ $customer->customerPhotos->count() }} foto tersimpan</small>
                                    @endif
                                @else
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3" 
                                         style="width: 150px; height: 150px;">
                                        <i class="fas fa-user fa-3x text-white"></i>
                                    </div>
                                    <small class="text-muted">Belum ada foto</small>
                                @endif
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-user-circle me-1"></i>Informasi Pribadi</h6>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td width="40%" class="fw-medium">Nama Lengkap</td>
                                                <td>: {{ $customer->name }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Email</td>
                                                <td>: {{ $customer->email }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Jenis Kelamin</td>
                                                <td>: {{ $customer->gender ? ucfirst($customer->gender) : '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Status</td>
                                                <td>: 
                                                    @php
                                                        $statusClass = match($customer->status) {
                                                            'active' => 'bg-success',
                                                            'inactive' => 'bg-warning',
                                                            'blacklist' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $statusClass }}">{{ ucfirst($customer->status) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Pekerjaan</td>
                                                <td>: {{ $customer->job ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Sumber Info</td>
                                                <td>: {{ $customer->source_info ?: '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-phone me-1"></i>Kontak & Alamat</h6>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td width="40%" class="fw-medium">Nomor HP</td>
                                                <td>:
                                                    @if($customer->customerPhoneNumbers->count() > 0)
                                                        @foreach($customer->customerPhoneNumbers as $phone)
                                                            <div>{{ $phone->phone_number }}</div>
                                                        @endforeach
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Alamat Rumah</td>
                                                <td>: {{ $customer->address ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Alamat Kantor</td>
                                                <td>: {{ $customer->office_address ?: '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-share-alt me-1"></i>Media Sosial</h6>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td width="40%" class="fw-medium">Instagram</td>
                                                <td>: 
                                                    @if($customer->instagram_username)
                                                        <a href="https://instagram.com/{{ $customer->instagram_username }}" target="_blank" class="text-decoration-none">
                                                            <i class="fab fa-instagram text-danger me-1"></i>@{{ $customer->instagram_username }}
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Facebook</td>
                                                <td>: 
                                                    @if($customer->facebook_username)
                                                        <a href="https://facebook.com/{{ $customer->facebook_username }}" target="_blank" class="text-decoration-none">
                                                            <i class="fab fa-facebook text-primary me-1"></i>{{ $customer->facebook_username }}
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-phone-alt me-1"></i>Kontak Darurat</h6>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td width="40%" class="fw-medium">Nama</td>
                                                <td>: {{ $customer->emergency_contact_name ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Nomor HP</td>
                                                <td>: {{ $customer->emergency_contact_number ?: '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="text-primary"><i class="fas fa-calendar me-1"></i>Informasi Registrasi</h6>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td width="20%" class="fw-medium">Tanggal Daftar</td>
                                                <td>: {{ $customer->created_at->format('d/m/Y H:i:s') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Terakhir Update</td>
                                                <td>: {{ $customer->updated_at->format('d/m/Y H:i:s') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-medium">Email Verified</td>
                                                <td>: 
                                                    @if($customer->email_verified_at)
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Verified ({{ $customer->email_verified_at->format('d/m/Y') }})
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock me-1"></i>Belum Verified
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Photos -->
                    @if($customer->customerPhotos->count() > 1)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-primary"><i class="fas fa-images me-1"></i>Galeri Foto ({{ $customer->customerPhotos->count() }} foto)</h6>
                            <div class="row">
                                @foreach($customer->customerPhotos as $photo)
                                <div class="col-md-2 col-sm-3 col-4 mb-3">
                                    <div class="position-relative">
                                        <img src="{{ Storage::url($photo->photo_path) }}" 
                                             alt="Customer Photo" 
                                             class="img-fluid rounded border"
                                             style="width: 100%; height: 100px; object-fit: cover; cursor: pointer;"
                                             onclick="showLargePhoto('{{ Storage::url($photo->photo_path) }}', '{{ $customer->name }}')">
                                        <button type="button" 
                                                class="btn btn-primary btn-sm position-absolute top-0 end-0 m-1 rounded-circle"
                                                onclick="showLargePhoto('{{ Storage::url($photo->photo_path) }}', '{{ $customer->name }}')"
                                                title="Lihat Foto Besar"
                                                style="width: 30px; height: 30px;">
                                            <i class="fas fa-search-plus fa-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Daftar
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('customers.export', ['selected_customers[]' => $customer->id]) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-download me-1"></i>Export Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="largePhoto" src="" alt="Customer Photo" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Show large photo
function showLargePhoto(photoUrl, customerName) {
    document.getElementById('largePhoto').src = photoUrl;
    document.querySelector('#photoModal .modal-title').textContent = `Foto ${customerName}`;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
}
</script>
@endpush
