<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Produk - Global Photo Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .search-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 1200px;
        }
        
        .search-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .availability-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .available {
            background: #10b981;
            color: white;
        }
        
        .unavailable {
            background: #ef4444;
            color: white;
        }
        
        .btn-search {
            background: #3b82f6;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-search:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="search-container">
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">
                    <i class="fas fa-search me-2"></i>
                    Cari Ketersediaan Produk
                </h2>
                <p class="text-muted">Cari produk dan bundling yang tersedia berdasarkan tanggal sewa</p>
            </div>

            <!-- Search Form -->
            <form method="GET" class="search-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Produk/Bundling</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" placeholder="Nama produk atau bundling...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="{{ request('start_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="{{ request('end_date', now()->addDays(7)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-search btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Cari
                        </button>
                    </div>
                </div>
            </form>

            <!-- Search Results -->
            <div class="row g-4">
                @forelse($products as $product)
                <div class="col-md-6 col-lg-4">
                    <div class="product-card position-relative">
                        <div class="availability-badge {{ $product['available'] ? 'available' : 'unavailable' }}">
                            {{ $product['available'] ? '✓ Tersedia' : '✗ Tidak Tersedia' }}
                        </div>
                        
                        @if($product['thumbnail'])
                        <img src="{{ asset('storage/' . $product['thumbnail']) }}" 
                             alt="{{ $product['name'] }}" 
                             class="card-img-top" style="height: 200px; object-fit: cover;">
                        @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                             style="height: 200px;">
                            <i class="fas fa-camera fa-3x text-muted"></i>
                        </div>
                        @endif
                        
                        <div class="card-body">
                            <h5 class="card-title mb-2">{{ $product['name'] }}</h5>
                            <p class="text-primary fw-bold mb-2">
                                Rp {{ number_format($product['price'], 0, ',', '.') }}/hari
                            </p>
                            
                            @if($product['type'] === 'bundling')
                            <div class="mb-2">
                                <span class="badge bg-info">Bundling Package</span>
                            </div>
                            <small class="text-muted">
                                Termasuk: {{ implode(', ', $product['included_products']) }}
                            </small>
                            @else
                            <div class="mb-2">
                                <span class="badge bg-secondary">Produk Tunggal</span>
                            </div>
                            @endif
                            
                            @if($product['available'])
                            <div class="mt-3">
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    {{ $product['available_count'] }} unit tersedia
                                </small>
                            </div>
                            @else
                            <div class="mt-3">
                                <small class="text-danger">
                                    <i class="fas fa-times-circle me-1"></i>
                                    Tidak tersedia pada tanggal tersebut
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Tidak ada produk ditemukan</h4>
                        <p class="text-muted">Coba ubah kata kunci atau rentang tanggal pencarian Anda</p>
                    </div>
                </div>
                @endforelse
            </div>
            
            <!-- Pagination -->
            @if(isset($pagination))
            <div class="d-flex justify-content-center mt-4">
                {{ $pagination->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate end date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + 7); // Default 7 days
            
            document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
        });
    </script>
</body>
</html>
