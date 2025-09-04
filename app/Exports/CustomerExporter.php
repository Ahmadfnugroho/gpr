<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class CustomerExporter implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithProperties,
    ShouldAutoSize
{
    protected $selectedIds;
    protected $filters;

    public function __construct($selectedIds = null, $filters = [])
    {
        $this->selectedIds = $selectedIds;
        $this->filters = $filters;
    }

    /**
     * Return collection of customers to export
     */
    public function collection(): Collection
    {
        $query = Customer::with(['customerPhoneNumbers', 'customerPhotos', 'transactions']);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->whereIn('status', $this->filters['status']);
        }

        if (!empty($this->filters['gender'])) {
            $query->whereIn('gender', $this->filters['gender']);
        }

        if (!empty($this->filters['source_info'])) {
            $query->whereIn('source_info', $this->filters['source_info']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        // Apply selected IDs if provided
        if ($this->selectedIds && is_array($this->selectedIds) && count($this->selectedIds) > 0) {
            $query->whereIn('id', $this->selectedIds);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nama Lengkap',
            'Email',
            'Nomor HP 1',
            'Nomor HP 2',
            'Jenis Kelamin',
            'Status',
            'Alamat',
            'Pekerjaan',
            'Alamat Kantor',
            'Instagram',
            'Kontak Emergency',
            'HP Emergency',
            'Sumber Info',
            'Total Foto',
            'Total Transaksi',
            'Email Verified',
            'Tanggal Daftar',
            'Terakhir Update'
        ];
    }

    /**
     * Map data for each row
     */
    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $customer->customerPhoneNumbers->get(0)?->phone_number ?? '',
            $customer->customerPhoneNumbers->get(1)?->phone_number ?? '',
            match ($customer->gender) {
                'male' => 'Laki-laki',
                'female' => 'Perempuan',
                default => $customer->gender ?: ''
            },
            match ($customer->status) {
                'active' => 'Aktif',
                'inactive' => 'Tidak Aktif',
                'blacklist' => 'Blacklist',
                default => $customer->status ?: ''
            },
            $customer->address ?: '',
            $customer->job ?: '',
            $customer->office_address ?: '',
            $customer->instagram_username ? '@' . $customer->instagram_username : '',
            $customer->facebook_username ?: '',
            $customer->emergency_contact_name ?: '',
            $customer->emergency_contact_number ?: '',
            $customer->source_info ?: '',
            $customer->customerPhotos->count(),
            $customer->transactions->count(),
            $customer->email_verified_at ? 'Ya' : 'Tidak',
            $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '',
            $customer->updated_at ? $customer->updated_at->format('d/m/Y H:i') : ''
        ];
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ],
            // Data rows styling
            'A2:T' . ($this->collection()->count() + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true
                ]
            ]
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 25,  // Name
            'C' => 30,  // Email
            'D' => 18,  // Phone 1
            'E' => 18,  // Phone 2
            'F' => 15,  // Gender
            'G' => 12,  // Status
            'H' => 40,  // Address
            'I' => 20,  // Job
            'J' => 30,  // Office Address
            'K' => 15,  // Instagram
            'L' => 15,  // Facebook
            'M' => 20,  // Emergency Contact
            'N' => 18,  // Emergency Phone
            'O' => 12,  // Source Info
            'P' => 12,  // Total Photos
            'Q' => 15,  // Total Transactions
            'R' => 12,  // Email Verified
            'S' => 18,  // Created At
            'T' => 18   // Updated At
        ];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Data Customer';
    }

    /**
     * Set document properties
     */
    public function properties(): array
    {
        return [
            'creator' => 'Global Photo Rental System',
            'title' => 'Export Data Customer',
            'description' => 'Data customer dari sistem Global Photo Rental',
            'subject' => 'Customer Data Export',
            'keywords' => 'customer,export,gpr,global photo rental',
            'category' => 'Customer Management',
            'manager' => 'Admin GPR',
            'company' => 'Global Photo Rental',
        ];
    }
}
