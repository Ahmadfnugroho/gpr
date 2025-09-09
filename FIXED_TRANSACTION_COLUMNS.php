<?php

// FIXED TransactionResource Table Columns - Uses unified calculation methods

use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn\TextColumnSize;

// 1. Grand Total column - uses unified calculation method
TextColumn::make('grand_total')
    ->label('Grand Total')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        // Use stored value if available, otherwise use unified calculation
        $grandTotal = $record->getGrandTotalWithFallback();
        return $grandTotal <= 0 ? 'Rp 0' : 'Rp ' . number_format($grandTotal, 0, ',', '.');
    })
    ->color('success')
    ->weight(FontWeight::Bold)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Grand Total includes additional services and discount');

// 2. Down Payment column - display DB value, default 0 if null
TextColumn::make('down_payment')
    ->label('Down Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(fn(?int $state) => 'Rp ' . number_format((int)($state ?? 0), 0, ',', '.'))
    ->color('primary')
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Down payment from database');

// 3. Remaining Payment column - uses unified calculation method
TextColumn::make('remaining_payment')
    ->label('Remaining Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        $remainingPayment = $record->getRemainingPayment();
        return $remainingPayment <= 0 ? 'LUNAS' : 'Rp ' . number_format($remainingPayment, 0, ',', '.');
    })
    ->color(function($record): string {
        $remainingPayment = $record->getRemainingPayment();
        return $remainingPayment > 0 ? 'warning' : 'success';
    })
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->tooltip('Grand Total - Down Payment');

// 4. Cancellation Fee column - uses model method
TextColumn::make('cancellation_fee')
    ->label('Cancellation Fee')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        $cancellationFee = $record->getCancellationFee();
        return $cancellationFee <= 0 ? 'Rp 0' : 'Rp ' . number_format($cancellationFee, 0, ',', '.');
    })
    ->color('danger')
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->tooltip('50% of Grand Total (for cancelled bookings)');

// 5. Additional Services column - uses model method
TextColumn::make('additional_services_display')
    ->label('Additional Services')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function ($record): string {
        $services = $record->getAdditionalServicesList();
        
        if (empty($services)) {
            return '-';
        }
        
        $display = [];
        $total = 0;
        
        foreach ($services as $service) {
            $display[] = $service['name'] . ': Rp ' . number_format($service['amount'], 0, ',', '.');
            $total += $service['amount'];
        }
        
        $output = implode('<br>', $display);
        
        if (count($services) > 1) {
            $output .= '<br><strong>Total: Rp ' . number_format($total, 0, ',', '.') . '</strong>';
        }
        
        return $output;
    })
    ->html()
    ->wrap()
    ->tooltip('Additional services included in Grand Total')
    ->toggleable(isToggledHiddenByDefault: true);

// 6. Payment Status column - uses model method
TextColumn::make('payment_status_display')
    ->label('Payment Status')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function ($record): string {
        $paymentStatus = $record->getPaymentStatus();
        
        // Enhanced status display with percentage for partial payments
        if ($paymentStatus['status'] === 'partial') {
            $grandTotal = $record->getGrandTotalWithFallback();
            $downPayment = (int) ($record->down_payment ?? 0);
            $percentage = $grandTotal > 0 ? round(($downPayment / $grandTotal) * 100, 1) : 0;
            return "Partial ({$percentage}%)";
        }
        
        return $paymentStatus['label'];
    })
    ->badge()
    ->color(function ($record): string {
        $paymentStatus = $record->getPaymentStatus();
        
        return match($paymentStatus['status']) {
            'paid' => 'success',
            'partial' => 'warning', 
            'cancelled' => 'danger',
            default => 'gray'
        };
    })
    ->tooltip('Payment status based on database values');

// 7. Financial Breakdown column (optional debug column)
TextColumn::make('financial_breakdown')
    ->label('Breakdown')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function ($record): string {
        $breakdown = $record->getFinancialBreakdown();
        
        $output = [];
        $output[] = 'Base: Rp ' . number_format($breakdown['base_price'], 0, ',', '.');
        $output[] = 'Duration: ' . $breakdown['duration'] . ' hari';
        $output[] = 'Subtotal: Rp ' . number_format($breakdown['total_with_duration'], 0, ',', '.');
        
        if ($breakdown['discount_amount'] > 0) {
            $output[] = 'Discount: -Rp ' . number_format($breakdown['discount_amount'], 0, ',', '.');
        }
        
        if ($breakdown['additional_services'] > 0) {
            $output[] = 'Add. Services: +Rp ' . number_format($breakdown['additional_services'], 0, ',', '.');
        }
        
        $output[] = '<strong>Total: Rp ' . number_format($breakdown['actual_grand_total'], 0, ',', '.') . '</strong>';
        
        if ($breakdown['stored_grand_total'] !== $breakdown['actual_grand_total']) {
            $output[] = '<em style="color: red;">DB: Rp ' . number_format($breakdown['stored_grand_total'], 0, ',', '.') . '</em>';
        }
        
        return implode('<br>', $output);
    })
    ->html()
    ->wrap()
    ->tooltip('Financial calculation breakdown')
    ->toggleable(isToggledHiddenByDefault: true);
