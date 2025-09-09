<?php

/**
 * TRANSACTION ENHANCEMENTS USAGE EXAMPLES
 * 
 * This file demonstrates how to use the new enhanced Transaction methods
 * for better financial calculations and display.
 */

use App\Models\Transaction;

// Example transaction ID (replace with actual ID)
$transactionId = 1;
$transaction = Transaction::with(['detailTransactions.product', 'detailTransactions.bundling', 'promo'])
    ->find($transactionId);

// =====================================================
// 1. DETAILED BREAKDOWN FOR FORMS (Requirement 1)
// =====================================================

/**
 * Get detailed breakdown of total before discount
 * Format: "Product Name: unit price × quantity × duration = subtotal"
 */
$breakdown = $transaction->getTotalBeforeDiscountBreakdown();

echo "=== TOTAL SEBELUM DISKON BREAKDOWN ===\n";
foreach ($breakdown['items'] as $item) {
    echo $item['formatted'] . "\n";
    // Output: "Sony A7C: Rp 300.000 × 2 × 3 hari = Rp 1.800.000"
}
echo "Total: " . $breakdown['formatted_total'] . "\n";

// =====================================================
// 2. GRAND TOTAL CALCULATION (Requirements 2 & 3)
// =====================================================

/**
 * Get comprehensive grand total breakdown
 */
$grandTotalBreakdown = $transaction->getGrandTotalBreakdown();

echo "\n=== GRAND TOTAL BREAKDOWN ===\n";
echo "Base Total: " . $grandTotalBreakdown['formatted']['base_total'] . "\n";
echo "Discount: -" . $grandTotalBreakdown['formatted']['discount_amount'] . "\n";
echo "Additional Services: +" . $grandTotalBreakdown['formatted']['additional_services'] . "\n";
echo "GRAND TOTAL: " . $grandTotalBreakdown['formatted']['grand_total'] . "\n";

// =====================================================
// 3. DOWN PAYMENT HANDLING (Requirement 3)
// =====================================================

/**
 * Get actual down payment from database
 */
$actualDownPayment = $transaction->getDownPaymentAmount();
echo "\n=== PAYMENT INFORMATION ===\n";
echo "Down Payment (from DB): " . $transaction->formatCurrency($actualDownPayment) . "\n";

$remainingPayment = $transaction->getRemainingPaymentAmount();
echo "Remaining Payment: " . $transaction->formatCurrency($remainingPayment) . "\n";

// =====================================================
// 4. CANCELLATION FEE (Requirement 4)
// =====================================================

/**
 * Get cancellation fee (always calculated, regardless of status)
 */
$cancellationFee = $transaction->getCancellationFeeAmount();
echo "\n=== CANCELLATION FEE ===\n";
echo "Cancellation Fee (50%): " . $transaction->formatCurrency($cancellationFee) . "\n";
echo "Status: {$transaction->booking_status}\n";

// =====================================================
// USAGE IN BLADE TEMPLATES
// =====================================================

/**
 * In your Blade templates, you can use:
 * 
 * FOR FORMS (detailed breakdown):
 * @php $breakdown = $record->getTotalBeforeDiscountBreakdown(); @endphp
 * @foreach($breakdown['items'] as $item)
 *     <div>{{ $item['formatted'] }}</div>
 * @endforeach
 * <strong>Total: {{ $breakdown['formatted_total'] }}</strong>
 * 
 * FOR TABLES (grand total only):
 * {{ $record->formatCurrency($record->calculateActualGrandTotal()) }}
 * 
 * FOR PDF (cancellation fee always shown):
 * <tr>
 *     <td>Biaya Pembatalan (50%):</td>
 *     <td>{{ $record->formatCurrency($record->getCancellationFeeAmount()) }}</td>
 * </tr>
 * 
 * FOR DOWN PAYMENT:
 * {{ $record->formatCurrency($record->getDownPaymentAmount()) }}
 */

// =====================================================
// FILAMENT FORM USAGE
// =====================================================

/**
 * In TransactionResource form, use these methods for calculations:
 * 
 * Placeholder::make('total_before_discount_breakdown')
 *     ->content(function (Get $get) use ($record) {
 *         if (!$record) return 'Creating new transaction...';
 *         
 *         $breakdown = $record->getTotalBeforeDiscountBreakdown();
 *         $html = '';
 *         foreach ($breakdown['items'] as $item) {
 *             $html .= '<div>' . $item['formatted'] . '</div>';
 *         }
 *         $html .= '<strong>Total: ' . $breakdown['formatted_total'] . '</strong>';
 *         
 *         return new HtmlString($html);
 *     })
 * 
 * TextInput::make('down_payment')
 *     ->default(fn($record) => $record ? $record->getDownPaymentAmount() : 0)
 */

// =====================================================
// FILAMENT TABLE USAGE
// =====================================================

/**
 * In TransactionResource table, use for clean display:
 * 
 * TextColumn::make('grand_total_display')
 *     ->label('Grand Total')
 *     ->getStateUsing(fn($record) => $record->formatCurrency($record->calculateActualGrandTotal()))
 * 
 * TextColumn::make('down_payment_display')
 *     ->label('Down Payment')
 *     ->getStateUsing(fn($record) => $record->formatCurrency($record->getDownPaymentAmount()))
 */

?>

<!--
=====================================================
FORM PLACEHOLDER EXAMPLE (for breakdown display)
=====================================================

This is how the enhanced form placeholder should look:

Placeholder::make('total_before_discount')
    ->label('Total Sebelum Diskon - Breakdown')
    ->content(function (Get $get) {
        $details = $get('detailTransactions');
        $duration = max(1, (int)($get('duration') ?? 1));

        if (!$details || !is_array($details)) {
            return new HtmlString('<div style="color: #6b7280; font-style: italic;">Tidak ada produk dipilih</div>');
        }

        $breakdown = [];
        $totalBeforeDiscount = 0;

        foreach ($details as $item) {
            $isBundling = (bool)($item['is_bundling'] ?? false);
            $customId = $isBundling ? ($item['bundling_id'] ?? '') : ($item['product_id'] ?? '');

            if (!$customId) continue;

            $name = '';
            $unitPrice = 0;
            $quantity = (int)($item['quantity'] ?? 1);

            if ($isBundling) {
                $bundling = \App\Models\Bundling::find($customId);
                if ($bundling) {
                    $name = $bundling->name;
                    $unitPrice = (int)$bundling->price;
                }
            } else {
                $product = \App\Models\Product::find($customId);
                if ($product) {
                    $name = $product->name;
                    $unitPrice = (int)$product->price;
                }
            }

            if ($name && $unitPrice > 0) {
                $subtotal = $unitPrice * $quantity * $duration;
                $breakdown[] = "{$name}: Rp " . number_format($unitPrice, 0, ',', '.') . 
                              " × {$quantity} × {$duration} hari = Rp " . number_format($subtotal, 0, ',', '.');
                $totalBeforeDiscount += $subtotal;
            }
        }

        $html = '<div style="background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">';
        
        if (!empty($breakdown)) {
            $html .= '<div style="margin-bottom: 8px; font-size: 12px; color: #64748b;">Detail Perhitungan:</div>';
            foreach ($breakdown as $line) {
                $html .= '<div style="font-size: 11px; margin-bottom: 4px; color: #334155;">• ' . $line . '</div>';
            }
            $html .= '<hr style="margin: 8px 0; border: none; border-top: 1px solid #e2e8f0;">';
            $html .= '<div style="font-weight: bold; font-size: 13px; color: #1e293b;">Total: Rp ' . number_format($totalBeforeDiscount, 0, ',', '.') . '</div>';
        } else {
            $html .= '<div style="color: #6b7280; font-style: italic;">Tidak ada produk yang valid dipilih</div>';
        }
        
        $html .= '</div>';

        return new HtmlString($html);
    })
    ->reactive()
    ->columnSpanFull()

=====================================================
TABLE COLUMN EXAMPLE (for grand total display)
=====================================================

TextColumn::make('financial_summary')
    ->label('Grand Total')
    ->html()
    ->size(TextColumnSize::ExtraSmall)
    ->getStateUsing(function ($record) {
        if (!$record) return '';
        
        $breakdown = $record->getFinancialBreakdown();
        
        // Only show grand total after discount and additional services
        $color = $breakdown['is_cancelled'] ? '#dc2626' : '#059669';
        $label = $breakdown['is_cancelled'] ? 'Cancel Fee' : 'Grand Total';
        
        $html = '<div style="font-size: 13px; font-weight: bold; color: ' . $color . '; text-align: center;">';
        $html .= '<div style="margin-bottom: 2px; font-size: 10px; color: #64748b;">' . $label . '</div>';
        $html .= 'Rp ' . number_format($breakdown['actual_grand_total'], 0, ',', '.');
        $html .= '</div>';
        
        return new HtmlString($html);
    })
    ->wrap()

=====================================================
PDF TEMPLATE EXAMPLE (for cancellation fee)
=====================================================

{{-- Always show cancellation fee regardless of status --}}
<tr style="background-color: #ffe6e6; color: #d63031;">
    <td class="summary-label font-semibold">Biaya Pembatalan (50%):</td>
    <td class="summary-value font-semibold">Rp{{ number_format($record->getCancellationFeeAmount(), 0, ',', '.') }}</td>
</tr>

{{-- Use actual down payment from database --}}
<tr>
    <td class="summary-label">Down Payment:</td>
    <td class="summary-value">Rp{{ number_format($record->getDownPaymentAmount(), 0, ',', '.') }}</td>
</tr>

-->
