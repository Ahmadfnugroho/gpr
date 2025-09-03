@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Customer Management
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('customers.import.form') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-upload me-1"></i>Import Excel
                        </a>
                        <a href="{{ route('customers.export') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-download me-1"></i>Export Excel
                        </a>
                        <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Tambah Customer
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search & Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Cari nama, email, atau nomor HP..." 
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="blacklist" {{ request('status') == 'blacklist' ? 'selected' : '' }}>Blacklist</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="gender">
                                    <option value="">Semua Jenis Kelamin</option>
                                    <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="d-flex gap-1">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Bulk Actions Form -->
                    <form id="bulk-form" method="POST" action="{{ route('customers.bulk-action') }}">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex gap-2 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label" for="selectAll">
                                        Pilih Semua
                                    </label>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            type="button" 
                                            id="bulkActions" 
                                            data-bs-toggle="dropdown"
                                            disabled>
                                        Aksi Massal
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="submitBulkAction('activate')">
                                            <i class="fas fa-check text-success me-1"></i>Aktifkan
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="submitBulkAction('deactivate')">
                                            <i class="fas fa-pause text-warning me-1"></i>Nonaktifkan
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="submitBulkAction('blacklist')">
                                            <i class="fas fa-ban text-danger me-1"></i>Blacklist
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="submitBulkAction('delete')">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </a></li>
                                    </ul>
                                </div>
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm"
                                        onclick="exportSelected()"
                                        id="exportSelected"
                                        disabled>
                                    <i class="fas fa-download me-1"></i>Export Yang Dipilih
                                </button>
                            </div>
                            <div class="text-muted">
                                Total: {{ $customers->total() }} customer
                            </div>
                        </div>

                        <!-- Data Table -->
                        @if($customers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="selectAllTable" class="form-check-input">
                                        </th>
                                        <th>Foto</th>
                                        <th>Nama & Email</th>
                                        <th>Nomor HP</th>
                                        <th>Status</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Pekerjaan</th>
                                        <th>Tanggal Daftar</th>
                                        <th width="100">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customers as $customer)
                                    <tr>
                                        <td>
                                            <input type="checkbox" 
                                                   name="customer_ids[]" 
                                                   value="{{ $customer->id }}" 
                                                   class="form-check-input customer-checkbox">
                                        </td>
                                        <td>
                                            @if($customer->customerPhotos->count() > 0)
                                                <div class="position-relative">
                                                    <img src="{{ Storage::url($customer->customerPhotos->first()->photo_path) }}" 
                                                         alt="Photo" 
                                                         class="rounded-circle"
                                                         style="width: 40px; height: 40px; object-fit: cover;">
                                                    <button type="button" 
                                                            class="btn btn-link btn-sm p-0 position-absolute top-0 start-100 translate-middle"
                                                            onclick="showLargePhoto('{{ Storage::url($customer->customerPhotos->first()->photo_path) }}', '{{ $customer->name }}')"
                                                            title="Lihat Foto Besar">
                                                        <i class="fas fa-search-plus text-primary"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $customer->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $customer->email }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($customer->customerPhoneNumbers->count() > 0)
                                                @foreach($customer->customerPhoneNumbers as $phone)
                                                    <div>{{ $phone->phone_number }}</div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusClass = match($customer->status) {
                                                    'active' => 'bg-success',
                                                    'inactive' => 'bg-warning',
                                                    'blacklist' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                                $statusText = ucfirst($customer->status);
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                        </td>
                                        <td>
                                            {{ $customer->gender ? ucfirst($customer->gender) : '-' }}
                                        </td>
                                        <td>
                                            {{ $customer->job ?: '-' }}
                                        </td>
                                        <td>
                                            {{ $customer->created_at->format('d/m/Y') }}
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                                        type="button" 
                                                        data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}">
                                                        <i class="fas fa-eye me-1"></i>Detail
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="{{ route('customers.edit', $customer) }}">
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><form method="POST" action="{{ route('customers.destroy', $customer) }}" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="dropdown-item text-danger"
                                                                onclick="return confirm('Yakin ingin menghapus customer ini?')">
                                                            <i class="fas fa-trash me-1"></i>Hapus
                                                        </button>
                                                    </form></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Hidden inputs for bulk actions -->
                        <input type="hidden" name="action" id="bulk-action">
                        
                        @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada data customer</h5>
                            <p class="text-muted">Silakan tambah customer atau import dari Excel</p>
                            <div class="mt-3">
                                <a href="{{ route('customers.create') }}" class="btn btn-primary me-2">
                                    <i class="fas fa-plus me-1"></i>Tambah Customer
                                </a>
                                <a href="{{ route('customers.import.form') }}" class="btn btn-success">
                                    <i class="fas fa-upload me-1"></i>Import Excel
                                </a>
                            </div>
                        </div>
                        @endif
                    </form>

                    <!-- Pagination -->
                    @if($customers->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Menampilkan {{ $customers->firstItem() }} - {{ $customers->lastItem() }} 
                            dari {{ $customers->total() }} customer
                        </div>
                        {{ $customers->links() }}
                    </div>
                    @endif
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
// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    const bulkButton = document.getElementById('bulkActions');
    const exportButton = document.getElementById('exportSelected');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    
    updateBulkButtons();
});

document.getElementById('selectAllTable').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.customer-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    
    updateBulkButtons();
});

// Update bulk buttons state
document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkButtons);
});

function updateBulkButtons() {
    const checked = document.querySelectorAll('.customer-checkbox:checked');
    const bulkButton = document.getElementById('bulkActions');
    const exportButton = document.getElementById('exportSelected');
    
    if (checked.length > 0) {
        bulkButton.disabled = false;
        exportButton.disabled = false;
    } else {
        bulkButton.disabled = true;
        exportButton.disabled = true;
    }
}

// Bulk actions
function submitBulkAction(action) {
    const checked = document.querySelectorAll('.customer-checkbox:checked');
    
    if (checked.length === 0) {
        alert('Pilih minimal satu customer');
        return;
    }
    
    let confirmMessage = '';
    switch(action) {
        case 'delete':
            confirmMessage = `Yakin ingin menghapus ${checked.length} customer?`;
            break;
        case 'activate':
            confirmMessage = `Yakin ingin mengaktifkan ${checked.length} customer?`;
            break;
        case 'deactivate':
            confirmMessage = `Yakin ingin menonaktifkan ${checked.length} customer?`;
            break;
        case 'blacklist':
            confirmMessage = `Yakin ingin mem-blacklist ${checked.length} customer?`;
            break;
    }
    
    if (confirm(confirmMessage)) {
        document.getElementById('bulk-action').value = action;
        document.getElementById('bulk-form').submit();
    }
}

// Export selected
function exportSelected() {
    const checked = document.querySelectorAll('.customer-checkbox:checked');
    
    if (checked.length === 0) {
        alert('Pilih minimal satu customer');
        return;
    }
    
    const selectedIds = Array.from(checked).map(cb => cb.value);
    const url = new URL('{{ route("customers.export") }}');
    selectedIds.forEach(id => url.searchParams.append('selected_customers[]', id));
    
    window.location.href = url.toString();
}

// Show large photo
function showLargePhoto(photoUrl, customerName) {
    document.getElementById('largePhoto').src = photoUrl;
    document.querySelector('#photoModal .modal-title').textContent = `Foto ${customerName}`;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
}

// Auto-submit search form on filter change
document.querySelector('select[name="status"]').addEventListener('change', function() {
    this.form.submit();
});

document.querySelector('select[name="gender"]').addEventListener('change', function() {
    this.form.submit();
});
</script>
@endpush
