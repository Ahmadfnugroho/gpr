<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class BlogPostsChart extends ChartWidget

{
    use HasWidgetShield;

    protected static ?string $heading = 'Omset Bulanan';
    public ?string $filter = 'today';
    protected static bool $isLazy = false;
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';





    protected function getData(): array
    {
        $monthlyEarnings = Transaction::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(round(grand_total/100000,2)) as total')
            ->whereYear('created_at', now()->year)
            ->whereIn('booking_status', ['pending', 'paid', 'rented', 'finished'])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // Inisialisasi data bulan (1-12) dengan nilai 0
        $earnings = array_fill(1, 12, 0);

        // Mengisi data bulan dengan nilai omset
        foreach ($monthlyEarnings as $month => $total) {
            $earnings[$month] = (int) $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omset (dalam ribuan)',
                    'data' => array_values($earnings),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                    'fill' => true, // Menambahkan efek kurva pada garis
                ],
            ],
            'labels' => [
                'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
