<?php

namespace App\Services;

use App\Models\Promo;
use Illuminate\Support\Facades\Log;

class PromoCalculationService
{
    /**
     * Calculate total discount amount for a transaction
     *
     * @param int|null $promoId
     * @param int $totalBeforeDiscount Base amount before any discounts
     * @param int $duration Number of rental days
     * @return array ['discountAmount' => int, 'finalAmount' => int, 'details' => string]
     */
    public function calculateDiscount(?int $promoId, int $totalBeforeDiscount, int $duration): array
    {
        // Default no discount
        if (!$promoId || $totalBeforeDiscount <= 0 || $duration <= 0) {
            return [
                'discountAmount' => 0,
                'finalAmount' => $totalBeforeDiscount,
                'details' => 'No promo applied'
            ];
        }

        $promo = Promo::find($promoId);
        if (!$promo || !$promo->active) {
            return [
                'discountAmount' => 0,
                'finalAmount' => $totalBeforeDiscount,
                'details' => 'Promo not found or inactive'
            ];
        }
        
        if (!is_array($promo->rules) || empty($promo->rules)) {
            return [
                'discountAmount' => 0,
                'finalAmount' => $totalBeforeDiscount,
                'details' => 'No promo rules defined'
            ];
        }

        $originalTotal = $totalBeforeDiscount;
        $rules = $promo->rules;
        
        // Get the promo type, ensuring it's properly set
        $promoType = trim((string) $promo->type);

        $result = match ($promoType) {
            'percentage' => $this->calculatePercentageDiscount($rules, $totalBeforeDiscount, $duration),
            'nominal' => $this->calculateNominalDiscount($rules, $totalBeforeDiscount, $duration),
            'day_based' => $this->calculateDayBasedDiscount($rules, $totalBeforeDiscount, $duration),
            default => ['discountAmount' => 0, 'finalAmount' => $originalTotal, 'details' => 'Unknown promo type']
        };

        // Ensure discount doesn't exceed original total
        $discountAmount = min($result['discountAmount'], $originalTotal);
        $finalAmount = max(0, $originalTotal - $discountAmount);

        return [
            'discountAmount' => $discountAmount,
            'finalAmount' => $finalAmount,
            'details' => $result['details'] ?? 'Discount applied'
        ];
    }

    /**
     * Calculate percentage-based discount
     */
    private function calculatePercentageDiscount(array $rules, int $totalBeforeDiscount, int $duration): array
    {
        $percentage = max(0, min(100, (float)($rules[0]['percentage'] ?? 0)));
        
        // Check if day restriction applies
        if (!empty($rules[0]['days']) && is_array($rules[0]['days'])) {
            $currentDay = now()->format('l'); // Get current day name (e.g., 'Monday')
            
            if (!in_array($currentDay, $rules[0]['days'])) {
                return [
                    'discountAmount' => 0,
                    'finalAmount' => $totalBeforeDiscount,
                    'details' => "Discount not applicable today ({$currentDay})"
                ];
            }
        }
        
        $discountAmount = (int)(($totalBeforeDiscount * $percentage) / 100);

        return [
            'discountAmount' => $discountAmount,
            'finalAmount' => $totalBeforeDiscount - $discountAmount,
            'details' => "Discount {$percentage}% applied"
        ];
    }

    /**
     * Calculate nominal (fixed amount) discount
     */
    private function calculateNominalDiscount(array $rules, int $totalBeforeDiscount, int $duration): array
    {
        $nominalDiscount = max(0, (int)($rules[0]['nominal'] ?? 0));
        
        // Check if day restriction applies
        if (!empty($rules[0]['days']) && is_array($rules[0]['days'])) {
            $currentDay = now()->format('l'); // Get current day name
            
            if (!in_array($currentDay, $rules[0]['days'])) {
                return [
                    'discountAmount' => 0,
                    'finalAmount' => $totalBeforeDiscount
                ];
            }
        }
        
        // Nominal discount is applied once, regardless of duration
        $discountAmount = min($nominalDiscount, $totalBeforeDiscount);

        return [
            'discountAmount' => $discountAmount,
            'finalAmount' => $totalBeforeDiscount - $discountAmount,
            'details' => "Fixed discount Rp " . number_format($nominalDiscount, 0, '.', ',') . " applied"
        ];
    }

    /**
     * Calculate day-based discount (rent X days, pay Y days)
     */
    private function calculateDayBasedDiscount(array $rules, int $totalBeforeDiscount, int $duration): array
    {
        $groupSize = max(1, (int)($rules[0]['group_size'] ?? 1));
        $payDays = max(0, (int)($rules[0]['pay_days'] ?? 0));

        // Validate configuration
        if ($payDays <= 0 || $payDays >= $groupSize) {
            return [
                'discountAmount' => 0,
                'finalAmount' => $totalBeforeDiscount,
                'details' => 'Invalid day-based promo configuration'
            ];
        }
        
        // Check minimum duration requirement
        if ($duration < $groupSize) {
            return [
                'discountAmount' => 0,
                'finalAmount' => $totalBeforeDiscount,
                'details' => "Minimum {$groupSize} days required for this promo"
            ];
        }

        // Calculate how many complete groups we have
        $fullGroups = intval($duration / $groupSize);
        $remainingDays = $duration % $groupSize;

        // Calculate days to pay: (full groups * pay_days) + remaining days
        $totalDaysToPay = ($fullGroups * $payDays) + $remainingDays;

        // Calculate discount as proportion of total
        // discount = (total_days - pay_days) / total_days * total_amount
        $discountAmount = (int)round($totalBeforeDiscount * (($duration - $totalDaysToPay) / $duration));
        $finalAmount = $totalBeforeDiscount - $discountAmount;

        $details = "Rent {$groupSize} days, pay {$payDays} days applied {$fullGroups} time(s)";
        if ($remainingDays > 0) {
            $details .= " + {$remainingDays} day(s) at full price";
        }

        return [
            'discountAmount' => $discountAmount,
            'finalAmount' => $finalAmount,
            'details' => $details
        ];
    }

    /**
     * Get promo details for display purposes
     *
     * @param int|null $promoId
     * @return array ['name' => string, 'type' => string|null, 'description' => string]
     */
    public function getPromoDetails(?int $promoId): array
    {
        if (!$promoId) {
            return [
                'name' => 'No promo',
                'type' => null,
                'description' => 'No discount applied'
            ];
        }

        $promo = Promo::find($promoId);
        if (!$promo) {
            return [
                'name' => 'Invalid promo',
                'type' => null,
                'description' => 'Promo not found'
            ];
        }

        $description = $this->generatePromoDescription($promo);

        return [
            'name' => $promo->name,
            'type' => $promo->type,
            'description' => $description
        ];
    }

    /**
     * Generate human-readable promo description
     */
    private function generatePromoDescription(Promo $promo): string
    {
        if (!is_array($promo->rules) || empty($promo->rules)) {
            return 'No rules defined';
        }

        $rules = $promo->rules[0] ?? [];

        return match ($promo->type) {
            'percentage' => "Discount {$rules['percentage']}%",
            'nominal' => "Fixed discount Rp " . number_format($rules['nominal'] ?? 0, 0, '.', ','),
            'day_based' => "Rent {$rules['group_size']} days, pay {$rules['pay_days']} days",
            default => 'Special discount'
        };
    }

    /**
     * Get human-readable explanation of the discount calculation
     */
    public function getDiscountExplanation(array $calculationDetails): string
    {
        if (($calculationDetails['discountAmount'] ?? 0) === 0) {
            return 'No discount applied';
        }

        $type = $calculationDetails['type'] ?? 'unknown';
        $details = $calculationDetails['details'] ?? [];
        $rules = $calculationDetails['rules'] ?? [];

        return match ($type) {
            'percentage' => "Discount {$details['percentage']}% from total Rp " . 
                           number_format($details['appliedTo'], 0, ',', '.'),
                           
            'nominal' => "Fixed discount Rp " . 
                        number_format($details['nominalAmount'], 0, ',', '.'),
                        
            'day_based' => "Rent {$rules['group_size']} days, pay {$rules['pay_days']} days. " .
                          "You pay for {$details['totalDaysToPay']} days total.",
                          
            default => 'Discount applied'
        };
    }
}
