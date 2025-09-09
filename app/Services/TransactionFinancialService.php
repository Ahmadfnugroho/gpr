<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\DetailTransaction;
use Carbon\Carbon;

class TransactionFinancialService
{
    /**
     * Calculate comprehensive financial breakdown for a transaction
     */
    public function calculateFinancialBreakdown(Transaction $transaction): array
    {
        return [
            'base_price' => $this->calculateBasePrice($transaction),
            'duration' => max(1, (int)($transaction->duration ?? 1)),
            'total_with_duration' => $this->calculateTotalWithDuration($transaction),
            'discount_amount' => $this->calculateDiscountAmount($transaction),
            'additional_services' => $this->calculateAdditionalServices($transaction),
            'actual_grand_total' => $this->calculateActualGrandTotal($transaction),
            'stored_grand_total' => (int)($transaction->grand_total ?? 0),
            'is_cancelled' => $transaction->booking_status === 'cancel',
            'cancellation_fee' => $this->calculateCancellationFee($transaction)
        ];
    }

    /**
     * Calculate base price from all detail transactions
     */
    public function calculateBasePrice(Transaction $transaction): int
    {
        $total = 0;
        
        foreach ($transaction->detailTransactions as $detail) {
            $price = 0;
            $quantity = (int)($detail->quantity ?? 1);
            
            if ($detail->bundling_id && $detail->bundling) {
                $price = (int)$detail->bundling->price;
            } elseif ($detail->product_id && $detail->product) {
                $price = (int)$detail->product->price;
            }
            
            $total += $price * $quantity;
        }
        
        return $total;
    }

    /**
     * Calculate total with duration applied
     */
    public function calculateTotalWithDuration(Transaction $transaction): int
    {
        $basePrice = $this->calculateBasePrice($transaction);
        $duration = max(1, (int)($transaction->duration ?? 1));
        
        return $basePrice * $duration;
    }

    /**
     * Calculate discount amount using PromoCalculationService
     */
    public function calculateDiscountAmount(Transaction $transaction): int
    {
        if (!$transaction->promo_id || !$transaction->promo) {
            return 0;
        }
        
        $totalBasePrice = $this->calculateBasePrice($transaction);
        $duration = (int)($transaction->duration ?? 1);
        
        $promoService = new PromoCalculationService();
        $result = $promoService->calculateDiscount($transaction->promo_id, $totalBasePrice, $duration);
        
        return (int)($result['discountAmount'] ?? 0);
    }

    /**
     * Calculate total additional services fees
     */
    public function calculateAdditionalServices(Transaction $transaction): int
    {
        $total = 0;
        
        // New additional services structure
        if ($transaction->additional_services && is_array($transaction->additional_services)) {
            foreach ($transaction->additional_services as $service) {
                if (is_array($service) && isset($service['amount'])) {
                    $total += (int)($service['amount'] ?? 0);
                }
            }
        }
        
        // Legacy additional fees structure
        $total += (int)($transaction->additional_fee_1_amount ?? 0);
        $total += (int)($transaction->additional_fee_2_amount ?? 0);
        $total += (int)($transaction->additional_fee_3_amount ?? 0);
        
        return $total;
    }

    /**
     * Calculate actual grand total
     */
    public function calculateActualGrandTotal(Transaction $transaction): int
    {
        $totalWithDuration = $this->calculateTotalWithDuration($transaction);
        $discountAmount = $this->calculateDiscountAmount($transaction);
        $additionalServices = $this->calculateAdditionalServices($transaction);
        
        // For cancelled transactions, use cancellation fee instead of full amount
        if ($transaction->booking_status === 'cancel') {
            $cancellationFee = $this->calculateCancellationFee($transaction);
            return $cancellationFee + $additionalServices;
        }
        
        return max(0, $totalWithDuration - $discountAmount + $additionalServices);
    }

    /**
     * Calculate cancellation fee
     */
    public function calculateCancellationFee(Transaction $transaction): int
    {
        if ($transaction->booking_status !== 'cancel') {
            return 0;
        }
        
        // Use stored cancellation fee if available
        if ($transaction->cancellation_fee && $transaction->cancellation_fee > 0) {
            return (int)$transaction->cancellation_fee;
        }
        
        // Calculate 50% of grand total
        $grandTotal = $transaction->grand_total ?: $this->calculateActualGrandTotal($transaction);
        return (int)floor($grandTotal * 0.5);
    }

    /**
     * Get payment status with detailed information
     */
    public function getPaymentStatus(Transaction $transaction): array
    {
        $actualGrandTotal = $this->calculateActualGrandTotal($transaction);
        $downPayment = (int)($transaction->down_payment ?? 0);
        $remainingPayment = max(0, $actualGrandTotal - $downPayment);
        
        $status = 'unpaid';
        $statusLabel = 'Belum Lunas';
        $statusColor = 'danger';
        
        if ($transaction->booking_status === 'cancel') {
            $status = 'cancelled';
            $statusLabel = 'Dibatalkan';
            $statusColor = 'warning';
        } elseif ($remainingPayment <= 0) {
            $status = 'paid';
            $statusLabel = 'Lunas';
            $statusColor = 'success';
        } elseif ($downPayment > 0) {
            $status = 'partial';
            $statusLabel = 'DP Diterima';
            $statusColor = 'info';
        }
        
        return [
            'status' => $status,
            'label' => $statusLabel,
            'color' => $statusColor,
            'down_payment' => $downPayment,
            'remaining_payment' => $remainingPayment,
            'total_amount' => $actualGrandTotal,
            'percentage_paid' => $actualGrandTotal > 0 ? round(($downPayment / $actualGrandTotal) * 100, 1) : 0
        ];
    }

    /**
     * Get formatted list of additional services
     */
    public function getAdditionalServicesList(Transaction $transaction): array
    {
        $services = [];
        
        // New additional services structure
        if ($transaction->additional_services && is_array($transaction->additional_services)) {
            foreach ($transaction->additional_services as $service) {
                if (is_array($service) && isset($service['name']) && isset($service['amount']) && $service['amount'] > 0) {
                    $services[] = [
                        'name' => $service['name'],
                        'amount' => (int)$service['amount']
                    ];
                }
            }
        }
        
        // Legacy additional fees structure
        if ($transaction->additional_fee_1_amount && $transaction->additional_fee_1_amount > 0) {
            $services[] = [
                'name' => $transaction->additional_fee_1_name ?: 'Additional Fee 1',
                'amount' => (int)$transaction->additional_fee_1_amount
            ];
        }
        
        if ($transaction->additional_fee_2_amount && $transaction->additional_fee_2_amount > 0) {
            $services[] = [
                'name' => $transaction->additional_fee_2_name ?: 'Additional Fee 2',
                'amount' => (int)$transaction->additional_fee_2_amount
            ];
        }
        
        if ($transaction->additional_fee_3_amount && $transaction->additional_fee_3_amount > 0) {
            $services[] = [
                'name' => $transaction->additional_fee_3_name ?: 'Additional Fee 3',
                'amount' => (int)$transaction->additional_fee_3_amount
            ];
        }
        
        return $services;
    }

    /**
     * Validate payment amount against business rules
     */
    public function validatePaymentAmount(Transaction $transaction, int $paymentAmount): array
    {
        $actualGrandTotal = $this->calculateActualGrandTotal($transaction);
        $minPayment = max(0, floor($actualGrandTotal * 0.5)); // 50% minimum
        
        $errors = [];
        
        if ($paymentAmount < 0) {
            $errors[] = 'Payment amount cannot be negative';
        }
        
        if ($paymentAmount > $actualGrandTotal) {
            $errors[] = "Payment amount cannot exceed grand total of Rp " . number_format($actualGrandTotal, 0, ',', '.');
        }
        
        if ($paymentAmount > 0 && $paymentAmount < $minPayment) {
            $errors[] = "Minimum payment is 50% of grand total: Rp " . number_format($minPayment, 0, ',', '.');
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'min_payment' => $minPayment,
            'max_payment' => $actualGrandTotal
        ];
    }

    /**
     * Update transaction booking status based on payment
     */
    public function updateBookingStatusBasedOnPayment(Transaction $transaction, int $downPayment): string
    {
        $actualGrandTotal = $this->calculateActualGrandTotal($transaction);
        
        if ($actualGrandTotal <= 0) {
            return 'cancel';
        }
        
        if ($downPayment <= 0) {
            return 'cancel';
        } elseif ($downPayment >= $actualGrandTotal) {
            // Full payment - keep existing status if it's on_rented/done, otherwise set to paid
            if (!in_array($transaction->booking_status, ['on_rented', 'done'])) {
                return 'paid';
            }
            return $transaction->booking_status;
        } else {
            // Partial payment
            $minPayment = max(0, floor($actualGrandTotal * 0.5));
            if ($downPayment >= $minPayment) {
                return 'booking';
            } else {
                return 'cancel';
            }
        }
    }

    /**
     * Format currency for display
     */
    public function formatCurrency(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get discount explanation text
     */
    public function getDiscountExplanation(Transaction $transaction): string
    {
        if (!$transaction->promo) {
            return 'No discount applied';
        }
        
        $discountDetails = $transaction->getDiscountDetails();
        
        if (isset($discountDetails['explanation'])) {
            return $discountDetails['explanation'];
        }
        
        // Fallback explanation based on promo type
        $promo = $transaction->promo;
        $rules = $promo->rules ?? [];
        
        return match($promo->type) {
            'percentage' => 'Discount ' . ($rules['percentage'] ?? 0) . '% applied',
            'nominal' => 'Fixed discount of ' . $this->formatCurrency($rules['nominal'] ?? 0),
            'day_based' => 'Day-based discount applied',
            default => 'Promo discount applied'
        };
    }
}
