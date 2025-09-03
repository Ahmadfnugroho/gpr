<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- Title -->
    <title>@yield('title', 'Global Photo Rental')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUa6/Ps4FHRMNbvOHTJkHhZyJfJx8dJyMT6e7y6lKRq8eoZp1g1Q5dK0sY7P" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        .navbar-brand {
            font-weight: 700;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #f8f9fa;
        }
        .nav-link:hover {
            background: rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .nav-link.active {
            background: #0d6efd;
            color: white !important;
            border-radius: 4px;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0,0,0,0.125);
        }
        .table th {
            border-top: none;
        }
        .btn-sm {
            font-size: 0.875rem;
        }
        .badge {
            font-size: 0.75em;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Loading animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Alert styles */
        .alert {
            border: none;
            border-radius: 8px;
        }
        
        /* Form styles */
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        /* Custom button styles */
        .btn-outline-primary:hover {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        /* Modal improvements */
        .modal-header {
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-footer {
            border-top: 1px solid #dee2e6;
        }
        
        /* Pagination styles */
        .pagination {
            margin-bottom: 0;
        }
        
        /* Responsive table */
        .table-responsive {
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
        }
    </style>
    
    @stack('styles')
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-camera me-2"></i>Global Photo Rental
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('customers*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                            <i class="fas fa-users me-1"></i>Customers
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/admin') }}">
                                <i class="fas fa-cog me-1"></i>Admin Panel
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar d-md-block collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('customers') && !request()->has('search') && !request()->has('status') ? 'active' : '' }}" 
                               href="{{ route('customers.index') }}">
                                <i class="fas fa-list me-1"></i>All Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('customers/create') ? 'active' : '' }}" 
                               href="{{ route('customers.create') }}">
                                <i class="fas fa-plus me-1"></i>Add Customer
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('customers/import*') ? 'active' : '' }}" 
                               href="{{ route('customers.import.form') }}">
                                <i class="fas fa-upload me-1"></i>Import Excel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('customers.export') }}">
                                <i class="fas fa-download me-1"></i>Export Excel
                            </a>
                        </li>
                        <li><hr class="my-3"></li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->get('status') === 'active' ? 'active' : '' }}" 
                               href="{{ route('customers.index', ['status' => 'active']) }}">
                                <i class="fas fa-check-circle me-1 text-success"></i>Active Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->get('status') === 'inactive' ? 'active' : '' }}" 
                               href="{{ route('customers.index', ['status' => 'inactive']) }}">
                                <i class="fas fa-pause-circle me-1 text-warning"></i>Inactive Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->get('status') === 'blacklist' ? 'active' : '' }}" 
                               href="{{ route('customers.index', ['status' => 'blacklist']) }}">
                                <i class="fas fa-ban me-1 text-danger"></i>Blacklisted
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('title', 'Dashboard')</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        @yield('toolbar')
                    </div>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i>{{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-times-circle me-1"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('import_errors'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-exclamation-triangle me-1"></i>Import Errors:</h6>
                    <ul class="mb-0">
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <!-- Main Content -->
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Hidden logout form -->
    <form id="logout-form" action="#" method="POST" class="d-none">
        @csrf
    </form>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });

        // Loading state for buttons
        function showLoading(button) {
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
            button.disabled = true;
            
            setTimeout(function() {
                button.innerHTML = originalContent;
                button.disabled = false;
            }, 3000);
        }

        // Confirm delete
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Format phone numbers
        function formatPhoneNumber(phone) {
            if (!phone) return '';
            
            let cleaned = phone.replace(/\D/g, '');
            
            if (cleaned.startsWith('62')) {
                return '+' + cleaned;
            } else if (cleaned.startsWith('0')) {
                return '+62' + cleaned.substring(1);
            } else if (cleaned) {
                return '+62' + cleaned;
            }
            
            return phone;
        }

        // Toast notifications
        function showToast(message, type = 'success') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
    </script>

    @stack('scripts')
</body>

</html>
