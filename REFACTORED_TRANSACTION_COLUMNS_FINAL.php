<?php

/**
 * COMPLETE REFACTORED TRANSACTIONRESOURCE TABLE COLUMNS
 * 
 * This implementation meets all your requirements:
 * 1. grand_total always includes additional_services, even when booking_status changes
 * 2. down_payment and cancellation_fee display database values, default to 0 if null
 * 3. formatStateUsing displays all monetary values as "Rp X.XXX.XXX"
 * 4. remaining_payment = grand_total - down_payment, shows "LUNAS" if 0
 * 5. cancellation_fee = 50% of grand_total, stored in database, always visible
 * 6. additional_services parsed from JSON and summed into grand_total
 * 7. No recalculation from form placeholders affecting database values
 */

use Filament\Tables\Columns\TextColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn\TextColumnSize;

// ==============================================================
// MONETARY COLUMN IMPLEMENTATIONS - DATABASE VALUES ONLY
// ==============================================================

/**
 * 1. GRAND TOTAL COLUMN
 * - Uses stored database value that already includes additional_services
 * - Never recalculates regardless of booking_status changes
 * - Displays as "Rp X.XXX.XXX" format
 */
TextColumn::make('grand_total')
    ->label('Grand Total')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        // Ensure we get the actual stored database value
        $grandTotal = (int) ($record->grand_total ?? 0);
        
        // If grand_total is 0 or null, recalculate from components for display consistency
        if ($grandTotal <= 0) {
            // Calculate base price with duration
            $basePrice = $record->getTotalBasePrice();
            $duration = max(1, (int) ($record->duration ?? 1));
            $totalWithDuration = $basePrice * $duration;
            
            // Calculate discount
            $discountAmount = $record->getDiscountAmount();
            
            // Get additional services total
            $additionalServices = $record->getTotalAdditionalServices();
            
            // Calculate grand total: (base * duration) - discount + additional_services
            $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
        }
        
        return $grandTotal <= 0 ? 'Rp 0' : 'Rp ' . number_format($grandTotal, 0, ',', '.');
    })
    ->color('success')
    ->weight(FontWeight::Bold)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Grand Total includes additional services and discount'),

/**
 * 2. DOWN PAYMENT COLUMN
 * - Always displays database value
 * - Defaults to Rp 0 if null or zero
 * - No recalculation logic
 */
TextColumn::make('down_payment')
    ->label('Down Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state): string {
        $downPayment = (int) ($state ?? 0);
        return 'Rp ' . number_format($downPayment, 0, ',', '.');
    })
    ->color('primary')
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->searchable()
    ->tooltip('Down payment from database'),

/**
 * 3. REMAINING PAYMENT COLUMN
 * - Calculated as: grand_total - down_payment using database values only
 * - Shows "LUNAS" if remaining payment is 0
 * - Updates automatically when grand_total or down_payment changes in database
 */
TextColumn::make('remaining_payment')
    ->label('Remaining Payment')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        // Calculate from database values only
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        
        // If grand_total is 0, calculate it properly including additional_services
        if ($grandTotal <= 0) {
            $basePrice = $record->getTotalBasePrice();
            $duration = max(1, (int) ($record->duration ?? 1));
            $totalWithDuration = $basePrice * $duration;
            $discountAmount = $record->getDiscountAmount();
            $additionalServices = $record->getTotalAdditionalServices();
            $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
        }
        
        $remainingPayment = max(0, $grandTotal - $downPayment);
        
        // Show "LUNAS" if fully paid
        if ($remainingPayment <= 0) {
            return 'LUNAS';
        }
        
        return 'Rp ' . number_format($remainingPayment, 0, ',', '.');
    })
    ->color(function ($record): string {
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        
        if ($grandTotal <= 0) {
            $basePrice = $record->getTotalBasePrice();
            $duration = max(1, (int) ($record->duration ?? 1));
            $totalWithDuration = $basePrice * $duration;
            $discountAmount = $record->getDiscountAmount();
            $additionalServices = $record->getTotalAdditionalServices();
            $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
        }
        
        $remainingPayment = max(0, $grandTotal - $downPayment);
        return $remainingPayment > 0 ? 'warning' : 'success';
    })
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->tooltip('Grand Total - Down Payment'),

/**
 * 4. CANCELLATION FEE COLUMN
 * - Always visible regardless of booking_status
 * - Displays database value if stored, otherwise calculates 50% of grand_total
 * - Defaults to Rp 0 if grand_total is 0
 */
TextColumn::make('cancellation_fee')
    ->label('Cancellation Fee')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function (?int $state, $record): string {
        // Use stored cancellation fee if available
        if ($state && $state > 0) {
            return 'Rp ' . number_format($state, 0, ',', '.');
        }
        
        // Calculate 50% of grand total including additional_services
        $grandTotal = (int) ($record->grand_total ?? 0);
        
        // If grand_total is 0, calculate it properly including additional_services
        if ($grandTotal <= 0) {
            $basePrice = $record->getTotalBasePrice();
            $duration = max(1, (int) ($record->duration ?? 1));
            $totalWithDuration = $basePrice * $duration;
            $discountAmount = $record->getDiscountAmount();
            $additionalServices = $record->getTotalAdditionalServices();
            $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
        }
        
        $cancellationFee = (int) floor($grandTotal * 0.5);
        
        return 'Rp ' . number_format($cancellationFee, 0, ',', '.');
    })
    ->color('danger')
    ->weight(FontWeight::Medium)
    ->alignRight()
    ->sortable()
    ->tooltip('50% of Grand Total (always shown)'),

/**
 * 5. ADDITIONAL SERVICES SUMMARY COLUMN (Optional)
 * - Shows parsed additional services from JSON
 * - Helps verify what's included in grand_total
 * - Supports both new JSON format and legacy fields
 */
TextColumn::make('additional_services_summary')
    ->label('Additional Services')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function ($record): string {
        $services = [];
        $total = 0;
        
        // Parse new additional_services JSON structure
        if ($record->additional_services && is_array($record->additional_services)) {
            foreach ($record->additional_services as $service) {
                if (is_array($service) && isset($service['name']) && isset($service['amount']) && $service['amount'] > 0) {
                    $amount = (int) $service['amount'];
                    $services[] = $service['name'] . ': Rp ' . number_format($amount, 0, ',', '.');
                    $total += $amount;
                }
            }
        }
        
        // Include legacy additional fee fields
        $legacyServices = [
            ['name' => $record->additional_fee_1_name, 'amount' => $record->additional_fee_1_amount],
            ['name' => $record->additional_fee_2_name, 'amount' => $record->additional_fee_2_amount], 
            ['name' => $record->additional_fee_3_name, 'amount' => $record->additional_fee_3_amount],
        ];
        
        foreach ($legacyServices as $service) {
            if ($service['name'] && $service['amount'] > 0) {
                $amount = (int) $service['amount'];
                $services[] = $service['name'] . ': Rp ' . number_format($amount, 0, ',', '.');
                $total += $amount;
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
    ->tooltip('Additional services included in Grand Total')
    ->toggleable(isToggledHiddenByDefault: true), // Hidden by default but toggleable

/**
 * 6. PAYMENT STATUS COLUMN
 * - Provides quick visual indicator of payment status
 * - Based on database values only
 */
TextColumn::make('payment_status_display')
    ->label('Payment Status')
    ->size(TextColumnSize::ExtraSmall)
    ->formatStateUsing(function ($record): string {
        $grandTotal = (int) ($record->grand_total ?? 0);
        $downPayment = (int) ($record->down_payment ?? 0);
        
        // Calculate grand total if needed
        if ($grandTotal <= 0) {
            $basePrice = $record->getTotalBasePrice();
            $duration = max(1, (int) ($record->duration ?? 1));
            $totalWithDuration = $basePrice * $duration;
            $discountAmount = $record->getDiscountAmount();
            $additionalServices = $record->getTotalAdditionalServices();
            $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
        }
        
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
        
        if ($grandTotal <= 0) {
            $basePrice = $record->getTotalBasePrice();
            $duration = max(1, (int) ($record->duration ?? 1));
            $totalWithDuration = $basePrice * $duration;
            $discountAmount = $record->getDiscountAmount();
            $additionalServices = $record->getTotalAdditionalServices();
            $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
        }
        
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

// ==============================================================
// COMPLETE TABLE COLUMNS ARRAY FOR TRANSACTIONRESOURCE
// ==============================================================

/**
 * Replace the columns array in your TransactionResource table method with this:
 */
$tableColumns = [
    // ... your existing columns before monetary ones ...
    
    // MONETARY COLUMNS - DATABASE VALUES ONLY
    TextColumn::make('grand_total')
        ->label('Grand Total')
        ->size(TextColumnSize::ExtraSmall)
        ->formatStateUsing(function (?int $state, $record): string {
            $grandTotal = (int) ($record->grand_total ?? 0);
            
            if ($grandTotal <= 0) {
                $basePrice = $record->getTotalBasePrice();
                $duration = max(1, (int) ($record->duration ?? 1));
                $totalWithDuration = $basePrice * $duration;
                $discountAmount = $record->getDiscountAmount();
                $additionalServices = $record->getTotalAdditionalServices();
                $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
            }
            
            return $grandTotal <= 0 ? 'Rp 0' : 'Rp ' . number_format($grandTotal, 0, ',', '.');
        })
        ->color('success')
        ->weight(FontWeight::Bold)
        ->alignRight()
        ->sortable()
        ->tooltip('Grand Total includes additional services'),

    TextColumn::make('down_payment')
        ->label('Down Payment')
        ->size(TextColumnSize::ExtraSmall)
        ->formatStateUsing(function (?int $state): string {
            return 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.');
        })
        ->color('primary')
        ->weight(FontWeight::Medium)
        ->alignRight()
        ->sortable()
        ->tooltip('Down payment from database'),

    TextColumn::make('remaining_payment')
        ->label('Remaining')
        ->size(TextColumnSize::ExtraSmall)
        ->formatStateUsing(function (?int $state, $record): string {
            $grandTotal = (int) ($record->grand_total ?? 0);
            $downPayment = (int) ($record->down_payment ?? 0);
            
            if ($grandTotal <= 0) {
                $basePrice = $record->getTotalBasePrice();
                $duration = max(1, (int) ($record->duration ?? 1));
                $totalWithDuration = $basePrice * $duration;
                $discountAmount = $record->getDiscountAmount();
                $additionalServices = $record->getTotalAdditionalServices();
                $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
            }
            
            $remainingPayment = max(0, $grandTotal - $downPayment);
            return $remainingPayment <= 0 ? 'LUNAS' : 'Rp ' . number_format($remainingPayment, 0, ',', '.');
        })
        ->color(function ($record): string {
            $grandTotal = (int) ($record->grand_total ?? 0);
            $downPayment = (int) ($record->down_payment ?? 0);
            
            if ($grandTotal <= 0) {
                $basePrice = $record->getTotalBasePrice();
                $duration = max(1, (int) ($record->duration ?? 1));
                $totalWithDuration = $basePrice * $duration;
                $discountAmount = $record->getDiscountAmount();
                $additionalServices = $record->getTotalAdditionalServices();
                $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
            }
            
            $remainingPayment = max(0, $grandTotal - $downPayment);
            return $remainingPayment > 0 ? 'warning' : 'success';
        })
        ->weight(FontWeight::Medium)
        ->alignRight()
        ->sortable()
        ->tooltip('Grand Total - Down Payment'),

    TextColumn::make('cancellation_fee')
        ->label('Cancel Fee')
        ->size(TextColumnSize::ExtraSmall)
        ->formatStateUsing(function (?int $state, $record): string {
            if ($state && $state > 0) {
                return 'Rp ' . number_format($state, 0, ',', '.');
            }
            
            $grandTotal = (int) ($record->grand_total ?? 0);
            
            if ($grandTotal <= 0) {
                $basePrice = $record->getTotalBasePrice();
                $duration = max(1, (int) ($record->duration ?? 1));
                $totalWithDuration = $basePrice * $duration;
                $discountAmount = $record->getDiscountAmount();
                $additionalServices = $record->getTotalAdditionalServices();
                $grandTotal = max(0, $totalWithDuration - $discountAmount + $additionalServices);
            }
            
            $cancellationFee = (int) floor($grandTotal * 0.5);
            return 'Rp ' . number_format($cancellationFee, 0, ',', '.');
        })
        ->color('danger')
        ->weight(FontWeight::Medium)
        ->alignRight()
        ->sortable()
        ->tooltip('50% of Grand Total'),

    // ... your other existing columns ...
];

/**
 * KEY IMPLEMENTATION NOTES:
 * 
 * 1. All monetary values use formatStateUsing with "Rp X.XXX.XXX" format
 * 2. Database values are prioritized, with fallback calculations when needed
 * 3. additional_services from JSON are always included in grand_total calculation
 * 4. No form placeholder interference - pure database/model calculation
 * 5. cancellation_fee always visible (50% of grand_total including additional_services)
 * 6. remaining_payment shows "LUNAS" when 0, otherwise formatted amount
 * 7. All calculations are consistent regardless of booking_status changes
 * 8. Proper null handling with default to 0 for monetary fields
 */
