<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPhoneNumber;
use App\Services\CustomerImportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    protected $importExportService;

    public function __construct(CustomerImportExportService $importExportService)
    {
        $this->importExportService = $importExportService;
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $query = Customer::with(['customerPhoneNumbers', 'customerPhotos']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhereHas('customerPhoneNumbers', function($subQ) use ($search) {
                      $subQ->where('phone_number', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $customers = $query->paginate(15);

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone_numbers' => 'nullable|array',
            'phone_numbers.*' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'status' => 'nullable|in:active,inactive,blacklist',
            'address' => 'nullable|string',
            'job' => 'nullable|string|max:255',
            'office_address' => 'nullable|string',
            'instagram_username' => 'nullable|string|max:255',
            'facebook_username' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:20',
            'source_info' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        DB::beginTransaction();

        try {
            // Create customer
            $customerData = $request->except('phone_numbers');
            $customerData['password'] = bcrypt('password123'); // Default password
            $customerData['status'] = $customerData['status'] ?? Customer::STATUS_BLACKLIST;
            
            $customer = Customer::create($customerData);

            // Add phone numbers
            if ($request->filled('phone_numbers')) {
                foreach ($request->phone_numbers as $phoneNumber) {
                    if (!empty($phoneNumber)) {
                        CustomerPhoneNumber::create([
                            'customer_id' => $customer->id,
                            'phone_number' => $phoneNumber
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('customers.index')
                           ->with('success', 'Customer berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Display the specified customer
     */
    public function show(Customer $customer)
    {
        $customer->load(['customerPhoneNumbers', 'customerPhotos']);
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer
     */
    public function edit(Customer $customer)
    {
        $customer->load(['customerPhoneNumbers']);
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone_numbers' => 'nullable|array',
            'phone_numbers.*' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'status' => 'nullable|in:active,inactive,blacklist',
            'address' => 'nullable|string',
            'job' => 'nullable|string|max:255',
            'office_address' => 'nullable|string',
            'instagram_username' => 'nullable|string|max:255',
            'facebook_username' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:20',
            'source_info' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        DB::beginTransaction();

        try {
            // Update customer
            $customerData = $request->except('phone_numbers');
            $customer->update($customerData);

            // Update phone numbers
            $customer->customerPhoneNumbers()->delete();
            
            if ($request->filled('phone_numbers')) {
                foreach ($request->phone_numbers as $phoneNumber) {
                    if (!empty($phoneNumber)) {
                        CustomerPhoneNumber::create([
                            'customer_id' => $customer->id,
                            'phone_number' => $phoneNumber
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('customers.index')
                           ->with('success', 'Customer berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy(Customer $customer)
    {
        try {
            DB::beginTransaction();
            
            // Delete related data
            $customer->customerPhoneNumbers()->delete();
            $customer->customerPhotos()->delete();
            
            // Delete customer
            $customer->delete();
            
            DB::commit();

            return redirect()->route('customers.index')
                           ->with('success', 'Customer berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                           ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return view('customers.import');
    }

    /**
     * Process import from Excel
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:15360', // 15MB max
            'update_existing' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $file = $request->file('excel_file');
            $updateExisting = $request->boolean('update_existing', false);

            // Validate file structure first
            $validation = $this->importExportService->validateFileStructure($file);
            
            if (!$validation['valid']) {
                return redirect()->back()
                               ->withErrors($validation['errors'])
                               ->withInput();
            }

            // Use SYNC-ONLY import with extreme optimization (NO QUEUE NEEDED)
            
            // Set PROGRESSIVE limits based on file size for optimal performance
            $fileSize = $file->getSize();
            $estimatedRows = intval($fileSize / 250); // Rough estimate: 250 bytes per row
            
            \Log::info('Starting customer import', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => round($fileSize / 1024 / 1024, 2) . 'MB',
                'estimated_rows' => $estimatedRows,
                'user_id' => auth()->id()
            ]);
            
            // Set AGGRESSIVE limits based on file size
            if ($fileSize > 10 * 1024 * 1024) { // > 10MB
                ini_set('memory_limit', '3G');
                ini_set('max_execution_time', '1800'); // 30 minutes
                set_time_limit(1800);
            } elseif ($fileSize > 5 * 1024 * 1024) { // > 5MB
                ini_set('memory_limit', '2G');
                ini_set('max_execution_time', '900'); // 15 minutes
                set_time_limit(900);
            } elseif ($fileSize > 2 * 1024 * 1024) { // > 2MB
                ini_set('memory_limit', '1G');
                ini_set('max_execution_time', '600'); // 10 minutes
                set_time_limit(600);
            } elseif ($fileSize > 1024 * 1024) { // > 1MB
                ini_set('memory_limit', '512M');
                ini_set('max_execution_time', '300'); // 5 minutes
                set_time_limit(300);
            } else {
                ini_set('memory_limit', '256M');
                ini_set('max_execution_time', '120'); // 2 minutes
                set_time_limit(120);
            }
            
            // Ignore user abort and connection issues
            ignore_user_abort(true);
            
            // Track performance
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Import with optimized sync method
            $results = $this->importExportService->importCustomers($file, $updateExisting);
            
            // Calculate performance metrics
            $endTime = microtime(true);
            $endMemory = memory_get_peak_usage(true);
            $executionTime = $endTime - $startTime;
            
            \Log::info('Customer import completed', [
                'execution_time' => round($executionTime, 2) . 's',
                'memory_peak' => round($endMemory / 1024 / 1024, 2) . 'MB',
                'result' => $results
            ]);
            
            $message = "Import selesai! ";
            $message .= "Total: {$results['total']}, ";
            $message .= "Berhasil: {$results['success']}, ";
            $message .= "Diperbarui: {$results['updated']}, ";
            $message .= "Gagal: {$results['failed']}";
            $message .= " (Waktu: " . round($executionTime, 1) . "s, Memory: " . round($endMemory / 1024 / 1024, 2) . "MB)";
            
            if (isset($results['processing_time'])) {
                $message .= " - Detail: {$results['processing_time']}";
            }
            
            if (!empty($results['errors'])) {
                return redirect()->route('customers.index')
                               ->with('warning', $message)
                               ->with('import_errors', $results['errors']);
            }
            
            return redirect()->route('customers.index')
                           ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Export customers to Excel
     */
    public function export(Request $request)
    {
        try {
            $customerIds = null;
            
            // If specific customers selected
            if ($request->filled('selected_customers')) {
                $customerIds = $request->selected_customers;
            }

            $filePath = $this->importExportService->exportCustomers($customerIds);
            $filename = basename($filePath);

            return response()->download($filePath, $filename)->deleteFileAfterSend();

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Terjadi kesalahan saat export: ' . $e->getMessage());
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        try {
            $filePath = $this->importExportService->generateTemplate();
            $filename = 'template_import_customer.xlsx';

            return response()->download($filePath, $filename)->deleteFileAfterSend();

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Terjadi kesalahan saat membuat template: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete,activate,deactivate,blacklist',
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $count = 0;
            
            switch ($request->action) {
                case 'delete':
                    $customers = Customer::whereIn('id', $request->customer_ids)->get();
                    foreach ($customers as $customer) {
                        $customer->customerPhoneNumbers()->delete();
                        $customer->customerPhotos()->delete();
                        $customer->delete();
                        $count++;
                    }
                    $message = "{$count} customer berhasil dihapus";
                    break;

                case 'activate':
                    $count = Customer::whereIn('id', $request->customer_ids)
                                   ->update(['status' => Customer::STATUS_ACTIVE]);
                    $message = "{$count} customer berhasil diaktifkan";
                    break;

                case 'deactivate':
                    $count = Customer::whereIn('id', $request->customer_ids)
                                   ->update(['status' => Customer::STATUS_INACTIVE]);
                    $message = "{$count} customer berhasil dinonaktifkan";
                    break;

                case 'blacklist':
                    $count = Customer::whereIn('id', $request->customer_ids)
                                   ->update(['status' => Customer::STATUS_BLACKLIST]);
                    $message = "{$count} customer berhasil di-blacklist";
                    break;
            }

            DB::commit();

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
