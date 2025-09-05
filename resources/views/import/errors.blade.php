<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Errors - {{ $importer_class }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .error-row:nth-child(even) { background-color: #fef2f2; }
        .error-row:nth-child(odd) { background-color: #fff; }
        .error-row:hover { background-color: #fee2e2; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                            Import Errors: {{ $importer_class }}
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Total {{ $total_failed }} baris gagal diimport pada {{ $timestamp }}
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ $download_url }}" 
                           class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Download Failed Rows
                        </a>
                        <button onclick="window.history.back()" 
                                class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Kembali
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="px-6 py-4 bg-red-50 border-b">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $total_failed }}</div>
                        <div class="text-sm text-gray-600">Total Failed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ count(array_unique(array_column($failed_rows, 'error_reason'))) }}</div>
                        <div class="text-sm text-gray-600">Unique Errors</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ count(array_unique(array_column($failed_rows, 'row_number'))) }}</div>
                        <div class="text-sm text-gray-600">Affected Rows</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ round((1 - $total_failed / ($total_failed + 169)) * 100, 1) }}%</div>
                        <div class="text-sm text-gray-600">Success Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Summary -->
        <div class="bg-white rounded-lg shadow-lg mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Error Summary</h2>
            </div>
            <div class="px-6 py-4">
                @php
                    $errorCounts = array_count_values(array_column($failed_rows, 'error_reason'));
                    arsort($errorCounts);
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach(array_slice($errorCounts, 0, 10) as $error => $count)
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm text-gray-900 font-medium">{{ $error }}</p>
                            </div>
                            <div class="ml-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ $count }} rows
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow-lg mb-6">
            <div class="px-6 py-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" 
                               id="searchInput"
                               placeholder="Cari error atau data baris..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <select id="errorFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Semua Error</option>
                        @foreach(array_keys($errorCounts) as $error)
                            <option value="{{ $error }}">{{ Str::limit($error, 50) }}</option>
                        @endforeach
                    </select>
                    <button onclick="clearFilters()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Clear Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Details Table -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Detail Errors</h2>
                <p class="text-sm text-gray-600">Showing <span id="displayedCount">{{ count($failed_rows) }}</span> of {{ $total_failed }} failed rows</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Row #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Error Reason
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Row Data Preview
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody id="errorTableBody" class="bg-white divide-y divide-gray-200">
                        @foreach($failed_rows as $index => $failedRow)
                            <tr class="error-row" data-error="{{ $failedRow['error_reason'] }}" data-row="{{ $failedRow['row_number'] }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $failedRow['row_number'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-red-600 font-medium">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        {{ $failedRow['error_reason'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs">
                                        @if(is_array($failedRow['row_data']) && !empty($failedRow['row_data']))
                                            @php
                                                $preview = array_slice($failedRow['row_data'], 0, 3);
                                                $previewText = collect($preview)->map(function($value, $key) {
                                                    return $key . ': ' . Str::limit($value, 20);
                                                })->implode(', ');
                                            @endphp
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded">
                                                {{ $previewText }}
                                                @if(count($failedRow['row_data']) > 3)
                                                    <span class="text-gray-500">... +{{ count($failedRow['row_data']) - 3 }} more</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-gray-500 text-xs">No data preview</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="showRowDetails({{ $index }})" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i>View Details
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination (if needed) -->
        @if(count($failed_rows) > 50)
        <div class="bg-white rounded-lg shadow-lg mt-6 px-6 py-4">
            <div class="flex justify-center">
                <p class="text-sm text-gray-600">
                    Showing first 1000 results. Download Excel file to see all {{ $total_failed }} failed rows.
                </p>
            </div>
        </div>
        @endif
    </div>

    <!-- Row Details Modal -->
    <div id="rowDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Row Details</h3>
                    <button onclick="hideRowDetails()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="rowDetailsContent" class="px-6 py-4">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Store failed rows data for JavaScript
        const failedRowsData = @json($failed_rows);
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            filterTable();
        });

        document.getElementById('errorFilter').addEventListener('change', function() {
            filterTable();
        });

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const errorFilter = document.getElementById('errorFilter').value;
            const rows = document.querySelectorAll('#errorTableBody .error-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const errorReason = row.getAttribute('data-error').toLowerCase();
                const rowData = row.textContent.toLowerCase();
                
                const matchesSearch = searchTerm === '' || rowData.includes(searchTerm);
                const matchesFilter = errorFilter === '' || errorReason.includes(errorFilter.toLowerCase());
                
                if (matchesSearch && matchesFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('displayedCount').textContent = visibleCount;
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('errorFilter').value = '';
            filterTable();
        }

        function showRowDetails(index) {
            const rowData = failedRowsData[index];
            const modal = document.getElementById('rowDetailsModal');
            const content = document.getElementById('rowDetailsContent');
            
            let html = `
                <div class="space-y-4">
                    <div class="bg-red-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-red-800">Error Information</h4>
                        <p class="text-red-600 mt-1">${rowData.error_reason}</p>
                        <p class="text-sm text-gray-600 mt-1">Row Number: ${rowData.row_number}</p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-3">Complete Row Data</h4>
                        <div class="space-y-2">
            `;
            
            if (rowData.row_data && typeof rowData.row_data === 'object') {
                Object.entries(rowData.row_data).forEach(([key, value]) => {
                    html += `
                        <div class="flex">
                            <span class="w-32 text-sm font-medium text-gray-600">${key}:</span>
                            <span class="text-sm text-gray-900">${value || '<empty>'}</span>
                        </div>
                    `;
                });
            } else {
                html += '<p class="text-gray-500">No detailed row data available</p>';
            }
            
            html += `
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-blue-800">Suggested Fix</h4>
                        <p class="text-blue-600 text-sm mt-1">
                            ${getSuggestedFix(rowData.error_reason)}
                        </p>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function hideRowDetails() {
            const modal = document.getElementById('rowDetailsModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function getSuggestedFix(errorReason) {
            if (errorReason.includes('sudah ada')) {
                return 'Data sudah exists. Enable "Update Existing" atau hapus data duplicate dari file Excel.';
            } else if (errorReason.includes('wajib diisi')) {
                return 'Field kosong. Pastikan semua field required sudah diisi dengan benar.';
            } else if (errorReason.includes('tidak ditemukan')) {
                return 'Data referensi tidak ada. Periksa ID/nama yang direferensi sudah benar dan ada di database.';
            } else if (errorReason.includes('format')) {
                return 'Format data salah. Periksa format data sesuai dengan yang diharapkan (tanggal, email, dll).';
            } else {
                return 'Periksa data pada baris ini dan pastikan sesuai dengan format yang diharapkan.';
            }
        }

        // Close modal when clicking outside
        document.getElementById('rowDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideRowDetails();
            }
        });
    </script>
</body>
</html>
