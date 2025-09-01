<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Analisis Omset';
    protected static bool $isLazy = false;
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        return [
            $this->createCard(
                'Omset Harian',
                $this->getDailyRevenue(),
                $this->getDailyChange(),
                $this->getDailyChart(),
            ),
            $this->createCard(
                'Omset Mingguan',
                $this->getWeeklyRevenue(),
                $this->getWeeklyChange(),
                $this->getWeeklyChart(),
            ),
            $this->createCard(
                'Omset Bulanan',
                $this->getMonthlyRevenue(),
                $this->getMonthlyChange(),
                $this->getMonthlyChart(),
            ),
            $this->createCard(
                'Omset Tahunan',
                $this->getYearlyRevenue(),
                $this->getYearlyChange(),
                $this->getYearlyChart(),
            ),
        ];
    }

    protected function createCard(string $title, int $value, array $change, array $chart): Stat
    {
        return Stat::make($title, 'Rp ' . number_format($value))
            ->description($change['text'])
            ->descriptionIcon($change['icon'])
            ->chart($chart)
            ->color($change['color']);
    }

    protected function getDailyRevenue()
    {
        $result = Transaction::whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total');

        return round($result / 100, 2);
    }

    protected function getDailyChange()
    {
        $today = round($this->getDailyRevenue() / 100, 2);

        $yesterday = round(Transaction::whereBetween('created_at', [
            today()->subDay()->startOfDay(),
            today()->subDay()->endOfDay(),
        ])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total') / 100, 2);

        return $this->calculateChange($today, $yesterday);
    }

    protected function getDailyChart()
    {
        return $this->generateChartData('daily');
    }

    protected function getWeeklyRevenue()
    {
        return round(Transaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total') / 100, 2);
    }

    protected function getWeeklyChange()
    {
        $thisWeek = $this->getWeeklyRevenue();

        $lastWeek = round(Transaction::whereBetween('created_at', [
            now()->startOfWeek()->subWeek(),
            now()->endOfWeek()->subWeek(),
        ])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total') / 100, 2);

        return $this->calculateChange($thisWeek, $lastWeek);
    }

    protected function getWeeklyChart()
    {
        return $this->generateChartData('weekly');
    }

    protected function getMonthlyRevenue()
    {
        return round(Transaction::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total') / 100, 2);
    }

    protected function getMonthlyChange()
    {
        $thisMonth = $this->getMonthlyRevenue();

        $lastMonth = round(Transaction::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth(),
        ])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total') / 100, 2);

        return $this->calculateChange($thisMonth, $lastMonth);
    }

    protected function getMonthlyChart()
    {
        return $this->generateChartData('monthly');
    }

    protected function getYearlyRevenue()
    {
        return round(Transaction::whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total') / 100, 2);
    }

    protected function getYearlyChange()
    {
        $thisYear = $this->getYearlyRevenue();

        $lastYear = round(Transaction::whereBetween('created_at', [
            now()->subYear()->startOfYear(),
            now()->subYear()->endOfYear(),
        ])
            ->whereIn('booking_status', ['booking', 'paid', 'on_rented', 'done'])
            ->sum('grand_total') / 100, 2);

        return $this->calculateChange($thisYear, $lastYear);
    }

    protected function getYearlyChart()
    {
        return $this->generateChartData('yearly');
    }

    protected function calculateChange($current, $previous): array
    {
        if ($previous == 0) {
            return [
                'text' => '+100%',
                'icon' => 'heroicon-m-arrow-trending-up',
                'color' => 'success',
            ];
        }

        $percentage = round((($current - $previous) / $previous) * 100, 2);
        $isPositive = $percentage >= 0;

        return [
            'text' => ($isPositive ? '+' : '') . $percentage . '%',
            'icon' => $isPositive ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
            'color' => $isPositive ? 'success' : 'danger',
        ];
    }

    protected function generateChartData(string $scope): array
    {
        // Placeholder untuk metode chart – pastikan diimplementasi sesuai kebutuhan Anda
        return [];
    }
}
