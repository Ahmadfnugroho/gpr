<?php

/**
 * Complete Example: TransactionResource Table Columns 
 * These columns display database values only without any recalculation
 */

// Add these imports at the top of your TransactionResource.php
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Illuminate\Support\HtmlString;

// Replace your existing columns with these implementations:

/**
 * 1. GRAND TOTAL COLUMN
 * - Displays the exact value from database grand_total field
 * - Includes additional_services (already calculated and stored)
 * - Does not recalculate based on booking_status
 */
TextColumn::make('grand_total')
    ->label('Grand Total')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?string $state): string {
        // Handle null or zero values
        if (!$state || $state == '0') {
            return 'Rp 0';
        }
        
        // Display database value directly without any recalculation
        // This value already includes: (base_price * duration) - discount + additional_services
        return 'Rp ' . number_format((int) $state, 0, ',', '.');
    })
    ->color('success')
    ->weight(FontWeight::Bold)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Grand Total from database (includes all additional services)');

/**
 * 2. DOWN PAYMENT COLUMN  
 * - Displays the exact value from database down_payment field
 * - No multiplication or recalculation
 * - Shows actual stored value
 */
TextColumn::make('down_payment')
    ->label('Down Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?string $state): string {
        // Handle null or zero values
        if (!$state || $state == '0') {
            return 'Rp 0';
        }
        
        // Display database value directly without any recalculation
        return 'Rp ' . number_format((int) $state, 0, ',', '.');
    })
    ->color('primary')
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Down Payment from database');

/**
 * 3. REMAINING PAYMENT COLUMN
 * - Calculates from database values only: grand_total - down_payment
 * - Does not use form state or live calculations
 * - Consistent with stored database values
 */
TextColumn::make('remaining_payment')
    ->label('Remaining Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?string $state, $record): string {
        // Calculate from database values only
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        $remainingPayment = max(0, $grandTotal - $downPayment);
        
        // Show LUNAS if fully paid
        if ($remainingPayment == 0) {
            return 'LUNAS';
        }
        
        // Format as Rupiah
        return 'Rp ' . number_format($remainingPayment, 0, ',', '.');
    })
    ->color(function ($record): string {
        // Color based on payment status
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        $remainingPayment = max(0, $grandTotal - $downPayment);
        
        return $remainingPayment > 0 ? 'warning' : 'success';
    })
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->tooltip('Calculated from database: Grand Total - Down Payment');

/**
 * OPTIONAL: Payment Status Column for better UX
 * Shows the overall payment status
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
        
        if ($remainingPayment == 0 && $downPayment > 0) {
            return 'Fully Paid';
        } elseif ($downPayment > 0 && $remainingPayment > 0) {
            $percentage = round(($downPayment / $grandTotal) * 100, 1);
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
        
        if ($remainingPayment == 0 && $downPayment > 0) {
            return 'success';
        } elseif ($downPayment > 0 && $remainingPayment > 0) {
            return 'warning';
        } else {
            return 'gray';
        }
    });

/**
 * KEY PRINCIPLES IMPLEMENTED:
 * 
 * 1. DATABASE VALUES ONLY
 *    - grand_total: Direct from database field (already includes additional_services)
 *    - down_payment: Direct from database field (no recalculation)
 *    - remaining_payment: Simple subtraction of database values
 * 
 * 2. NO FORM STATE DEPENDENCY
 *    - Does not use live reactive calculations
 *    - Does not depend on form placeholders or getStateUsing with model methods
 *    - Pure database value display
 * 
 * 3. CONSISTENT FORMATTING
 *    - All monetary values formatted as "Rp X.XXX.XXX"
 *    - Proper handling of null/zero values
 *    - Consistent alignment and styling
 * 
 * 4. PERFORMANCE OPTIMIZED
 *    - No additional model method calls
 *    - No complex calculations in table rendering
 *    - Direct field access only
 * 
 * 5. STATUS INDICATORS
 *    - Color coding based on payment status
 *    - LUNAS indicator for fully paid transactions
 *    - Proper badge display for payment status
 */
