<?php

/**
 * COMPLETE REFACTORED TRANSACTIONRESOURCE TABLE COLUMNS
 * 
 * This implementation ensures full consistency between form, table, and database
 * by using database values only in table columns and avoiding runtime recalculations
 */

use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Illuminate\Support\HtmlString;

// =============================
// TABLE COLUMNS IMPLEMENTATION
// =============================

/**
 * 1. GRAND TOTAL COLUMN
 * - Uses stored database value (already includes additional_services)
 * - Never recalculates based on booking_status
 * - Properly formatted as Rupiah
 */
TextColumn::make('grand_total')
    ->label('Grand Total')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state): string {
        if (!$state || $state <= 0) {
            return 'Rp 0';
        }
        
        // Direct database value - already includes additional_services
        return 'Rp ' . number_format($state, 0, ',', '.');
    })
    ->color('success')
    ->weight(FontWeight::Bold)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Grand Total from database (includes additional services)'),

/**
 * 2. DOWN PAYMENT COLUMN  
 * - Uses exact database value without any recalculation
 * - Reflects actual stored value regardless of booking_status changes
 * - Properly formatted as Rupiah
 */
TextColumn::make('down_payment')
    ->label('Down Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state): string {
        if (!$state || $state <= 0) {
            return 'Rp 0';
        }
        
        // Direct database value - no recalculation
        return 'Rp ' . number_format($state, 0, ',', '.');
    })
    ->color('primary')
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Down Payment from database'),

/**
 * 3. REMAINING PAYMENT COLUMN
 * - Calculates from database values only: grand_total - down_payment
 * - Shows "LUNAS" when remaining is 0
 * - Uses database values to prevent inconsistencies
 */
TextColumn::make('remaining_payment')
    ->label('Remaining Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        // Calculate from database values only
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        $remainingPayment = max(0, $grandTotal - $downPayment);
        
        // Show LUNAS if fully paid
        if ($remainingPayment <= 0) {
            return 'LUNAS';
        }
        
        // Format as Rupiah
        return 'Rp ' . number_format($remainingPayment, 0, ',', '.');
    })
    ->color(function ($record): string {
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        $remainingPayment = max(0, $grandTotal - $downPayment);
        
        return $remainingPayment > 0 ? 'warning' : 'success';
    })
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->tooltip('Calculated: Grand Total - Down Payment'),

/**
 * 4. CANCELLATION FEE COLUMN
 * - Always displays in table regardless of booking_status
 * - Uses stored database value (50% of grand_total including additional_services)
 * - Ensures PDF consistency by always being available
 */
TextColumn::make('cancellation_fee')
    ->label('Cancellation Fee')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        // Use stored cancellation fee if available
        if ($state && $state > 0) {
            return 'Rp ' . number_format($state, 0, ',', '.');
        }
        
        // Calculate 50% of grand total (fallback)
        $grandTotal = (int) ($record->grand_total ?? 0);
        $cancellationFee = (int) floor($grandTotal * 0.5);
        
        if ($cancellationFee <= 0) {
            return 'Rp 0';
        }
        
        return 'Rp ' . number_format($cancellationFee, 0, ',', '.');
    })
    ->color('danger')
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->tooltip('50% of Grand Total (always shown for PDF consistency)'),

/**
 * 5. ADDITIONAL SERVICES DISPLAY COLUMN (Optional)
 * - Shows summary of additional services
 * - Helps verify what's included in grand_total
 */
TextColumn::make('additional_services_display')
    ->label('Additional Services')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function ($record): string {
        $services = [];
        $total = 0;
        
        // New additional services structure
        if ($record->additional_services && is_array($record->additional_services)) {
            foreach ($record->additional_services as $service) {
                if (is_array($service) && isset($service['name']) && isset($service['amount']) && $service['amount'] > 0) {
                    $services[] = $service['name'] . ': Rp ' . number_format($service['amount'], 0, ',', '.');
                    $total += (int) $service['amount'];
                }
            }
        }
        
        // Legacy additional fees
        $legacyFees = [
            ['name' => $record->additional_fee_1_name, 'amount' => $record->additional_fee_1_amount],
            ['name' => $record->additional_fee_2_name, 'amount' => $record->additional_fee_2_amount],
            ['name' => $record->additional_fee_3_name, 'amount' => $record->additional_fee_3_amount],
        ];
        
        foreach ($legacyFees as $fee) {
            if ($fee['name'] && $fee['amount'] > 0) {
                $services[] = $fee['name'] . ': Rp ' . number_format($fee['amount'], 0, ',', '.');
                $total += (int) $fee['amount'];
            }
        }
        
        if (empty($services)) {
            return '-';
        }
        
        $display = implode('<br>', $services);
        if (count($services) > 1) {
            $display .= '<br><strong>Total: Rp ' . number_format($total, 0, ',', '.') . '</strong>';
        }
        
        return $display;
    })
    ->html()
    ->wrap()
    ->tooltip('Additional services included in Grand Total'),

/**
 * 6. PAYMENT STATUS COLUMN
 * - Shows overall payment status based on database values
 * - Provides quick visual indicator
 */
TextColumn::make('payment_status')
    ->label('Payment Status')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function ($record): string {
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        $remainingPayment = max(0, $grandTotal - $downPayment);
        
        if ($record->booking_status === 'cancel') {
            return 'Cancelled';
        }
        
        if ($remainingPayment <= 0 && $downPayment > 0) {
            return 'Fully Paid';
        } elseif ($downPayment > 0 && $remainingPayment > 0) {
            $percentage = $grandTotal > 0 ? round(($downPayment / $grandTotal) * 100, 1) : 0;
            return "Partial ({$percentage}%)";
        } else {
            return 'Unpaid';
        }
    })
    ->badge()
    ->color(function ($record): string {
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        $remainingPayment = max(0, $grandTotal - $downPayment);
        
        if ($record->booking_status === 'cancel') {
            return 'danger';
        }
        
        if ($remainingPayment <= 0 && $downPayment > 0) {
            return 'success';
        } elseif ($downPayment > 0 && $remainingPayment > 0) {
            return 'warning';
        } else {
            return 'gray';
        }
    })
    ->tooltip('Payment status based on database values');

// =============================
// FORM PLACEHOLDERS (DISPLAY ONLY)
// =============================

/**
 * Form placeholders can remain reactive for user experience
 * but should NOT interfere with database storage
 */

use Filament\Forms\Components\Placeholder;

// Grand Total Display (Form)
Placeholder::make('grand_total_display')
    ->label('Grand Total (Live Calculation)')
    ->live()
    ->content(function (Get $get) {
        $grandTotal = static::calculateGrandTotal($get);
        return 'Rp ' . number_format($grandTotal, 0, ',', '.');
    })
    ->extraAttributes(['class' => 'text-lg font-bold text-green-600']);

// Down Payment Display (Form)
Placeholder::make('down_payment_display')
    ->label('Current Down Payment')
    ->live()
    ->content(function (Get $get, $record) {
        if ($record && $record->down_payment) {
            return 'Rp ' . number_format((int)$record->down_payment, 0, ',', '.') . ' (Stored)';
        }
        
        $grandTotal = static::calculateGrandTotal($get);
        $defaultDp = max(0, floor($grandTotal * 0.5));
        return 'Rp ' . number_format($defaultDp, 0, ',', '.') . ' (50% default)';
    })
    ->extraAttributes(['class' => 'text-lg font-medium text-blue-600']);

// Remaining Payment Display (Form)
Placeholder::make('remaining_payment_display')
    ->label('Remaining Payment (Live)')
    ->live()
    ->content(function (Get $get) {
        $grandTotal = static::calculateGrandTotal($get);
        $downPayment = (int)($get('down_payment') ?? 0);
        $remaining = max(0, $grandTotal - $downPayment);
        
        if ($remaining <= 0) {
            return '<span class="text-green-600 font-bold">LUNAS</span>';
        }
        
        return '<span class="text-orange-600 font-bold">Rp ' . number_format($remaining, 0, ',', '.') . '</span>';
    })
    ->html()
    ->extraAttributes(['class' => 'text-lg']);

// Cancellation Fee Display (Form)
Placeholder::make('cancellation_fee_display')
    ->label('Cancellation Fee (50%)')
    ->live()
    ->content(function (Get $get, $record) {
        if ($record && $record->cancellation_fee) {
            return 'Rp ' . number_format((int)$record->cancellation_fee, 0, ',', '.') . ' (Stored)';
        }
        
        $grandTotal = static::calculateGrandTotal($get);
        $cancellationFee = (int) floor($grandTotal * 0.5);
        return 'Rp ' . number_format($cancellationFee, 0, ',', '.') . ' (50% of Grand Total)';
    })
    ->extraAttributes(['class' => 'text-md text-gray-600']);

// =============================
// ADDITIONAL SERVICES REPEATER
// =============================

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

Repeater::make('additional_services')
    ->schema([
        TextInput::make('name')
            ->label('Service Name')
            ->placeholder('e.g., Cleaning Service, Insurance')
            ->required()
            ->columnSpan(1),
        TextInput::make('amount')
            ->label('Service Amount')
            ->numeric()
            ->default(0)
            ->minValue(0)
            ->live(debounce: 300)
            ->prefix('Rp')
            ->step(1000)
            ->formatStateUsing(fn($state) => $state ? number_format($state, 0, '', '') : '')
            ->dehydrateStateUsing(fn($state) => (int) str_replace(',', '', $state))
            ->afterStateUpdated(function ($state, $set, $get) {
                // Trigger grand total recalculation
                $grandTotal = static::calculateGrandTotal($get);
                $set('../../grand_total', $grandTotal);
                
                // Recalculate remaining payment
                $downPayment = (int)($get('../../down_payment') ?? 0);
                $remaining = max(0, $grandTotal - $downPayment);
                $set('../../remaining_payment', $remaining);
            })
            ->columnSpan(1),
    ])
    ->columns(2)
    ->addActionLabel('Add Additional Service')
    ->defaultItems(1)
    ->collapsible()
    ->columnSpanFull();

/**
 * KEY IMPLEMENTATION PRINCIPLES:
 * 
 * 1. DATABASE CONSISTENCY
 *    - All table columns use direct database values
 *    - No runtime recalculation in table display
 *    - Consistent formatting across all monetary fields
 * 
 * 2. GRAND TOTAL INTEGRITY
 *    - Always includes additional_services
 *    - Never recalculates when booking_status changes
 *    - Uses stored database value in table
 * 
 * 3. CANCELLATION FEE ALWAYS VISIBLE
 *    - Shows in table regardless of booking_status
 *    - Uses stored value (50% of grand_total)
 *    - Ensures PDF consistency
 * 
 * 4. FORM VS TABLE SEPARATION
 *    - Form placeholders can be reactive for UX
 *    - Table columns use database values only
 *    - No interference between display and storage
 * 
 * 5. PROPER INTEGER CASTING
 *    - All monetary fields cast as integers
 *    - additional_services cast as array
 *    - Consistent data types throughout
 */
