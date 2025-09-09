<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PromoCalculationService;
use App\Models\Promo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PromoCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PromoCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromoCalculationService();
    }

    /** @test */
    public function it_returns_zero_discount_when_no_promo_id_provided()
    {
        $result = $this->service->calculateDiscount(null, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('No promo applied', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_returns_zero_discount_when_promo_not_found()
    {
        $result = $this->service->calculateDiscount(999, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('Promo not found or inactive', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_returns_zero_discount_when_promo_is_inactive()
    {
        $promo = Promo::create([
            'name' => 'Inactive Promo',
            'type' => 'percentage',
            'rules' => [
                [
                    'percentage' => 10
                ]
            ],
            'active' => false
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('Promo not found or inactive', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_returns_zero_discount_when_promo_has_no_rules()
    {
        $promo = Promo::create([
            'name' => 'No Rules Promo',
            'type' => 'percentage',
            'rules' => [],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('No promo rules defined', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_calculates_percentage_discount_correctly()
    {
        $promo = Promo::create([
            'name' => '15% Discount',
            'type' => 'percentage',
            'rules' => [
                [
                    'percentage' => 15
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 3);

        $this->assertEquals(15000, $result['discountAmount']); // 15% of 100000
        $this->assertEquals('Discount 15% applied', $result['details']);
        $this->assertEquals(85000, $result['finalAmount']);
    }

    /** @test */
    public function it_applies_percentage_discount_only_on_specific_days()
    {
        // Mock current day to be Monday
        Carbon::setTestNow(Carbon::create(2024, 1, 1)); // Monday

        $promo = Promo::create([
            'name' => 'Monday Special',
            'type' => 'percentage',
            'rules' => [
                [
                    'percentage' => 20,
                    'days' => ['Monday', 'Wednesday']
                ]
            ],
            'active' => true
        ]);

        // Should apply on Monday
        $result = $this->service->calculateDiscount($promo->id, 100000, 3);
        $this->assertEquals(20000, $result['discountAmount']);
        $this->assertEquals('Discount 20% applied', $result['details']);

        // Mock current day to be Tuesday
        Carbon::setTestNow(Carbon::create(2024, 1, 2)); // Tuesday

        // Should not apply on Tuesday
        $result = $this->service->calculateDiscount($promo->id, 100000, 3);
        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals(100000, $result['finalAmount']);

        Carbon::setTestNow(); // Reset time
    }

    /** @test */
    public function it_calculates_nominal_discount_correctly()
    {
        $promo = Promo::create([
            'name' => '25k Off',
            'type' => 'nominal',
            'rules' => [
                [
                    'nominal' => 25000
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 5);

        $this->assertEquals(25000, $result['discountAmount']); // Fixed 25k regardless of duration
        $this->assertEquals('Fixed discount Rp 25,000 applied', $result['details']);
        $this->assertEquals(75000, $result['finalAmount']);
    }

    /** @test */
    public function it_applies_nominal_discount_only_on_specific_days()
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 1)); // Monday

        $promo = Promo::create([
            'name' => 'Weekend Special',
            'type' => 'nominal',
            'rules' => [
                [
                    'nominal' => 30000,
                    'days' => ['Saturday', 'Sunday']
                ]
            ],
            'active' => true
        ]);

        // Should not apply on Monday
        $result = $this->service->calculateDiscount($promo->id, 100000, 3);
        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals(100000, $result['finalAmount']);

        Carbon::setTestNow(); // Reset time
    }

    /** @test */
    public function it_caps_nominal_discount_at_total_amount()
    {
        $promo = Promo::create([
            'name' => 'Big Discount',
            'type' => 'nominal',
            'rules' => [
                [
                    'nominal' => 150000
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 2);

        // Discount should be capped at total amount
        $this->assertEquals(100000, $result['discountAmount']);
        $this->assertEquals(0, $result['finalAmount']);
    }

    /** @test */
    public function it_calculates_day_based_discount_correctly()
    {
        $promo = Promo::create([
            'name' => 'Rent 7 Pay 5',
            'type' => 'day_based',
            'rules' => [
                [
                    'group_size' => 7,
                    'pay_days' => 5
                ]
            ],
            'active' => true
        ]);

        // Test with exactly 7 days (1 complete group)
        $result = $this->service->calculateDiscount($promo->id, 100000, 7);
        
        // Should pay for 5 days out of 7, so discount = (7-5)/7 * total = 2/7 * 100000
        $expectedDiscount = (int)round(100000 * (2/7));
        $this->assertEquals($expectedDiscount, $result['discountAmount']);
        $this->assertStringContainsString('Rent 7 days, pay 5 days applied 1 time(s)', $result['details']);
    }

    /** @test */
    public function it_calculates_day_based_discount_with_multiple_complete_groups()
    {
        $promo = Promo::create([
            'name' => 'Rent 3 Pay 2',
            'type' => 'day_based',
            'rules' => [
                [
                    'group_size' => 3,
                    'pay_days' => 2
                ]
            ],
            'active' => true
        ]);

        // Test with 9 days (3 complete groups of 3 days each)
        $result = $this->service->calculateDiscount($promo->id, 90000, 9);
        
        // Should pay for 6 days out of 9 (3 groups × 2 pay days)
        // So discount = (9-6)/9 * total = 3/9 * 90000 = 30000
        $expectedDiscount = (int)round(90000 * (3/9));
        $this->assertEquals($expectedDiscount, $result['discountAmount']);
        $this->assertEquals(60000, $result['finalAmount']);
        $this->assertStringContainsString('Rent 3 days, pay 2 days applied 3 time(s)', $result['details']);
    }

    /** @test */
    public function it_calculates_day_based_discount_with_remaining_days()
    {
        $promo = Promo::create([
            'name' => 'Rent 5 Pay 3',
            'type' => 'day_based',
            'rules' => [
                [
                    'group_size' => 5,
                    'pay_days' => 3
                ]
            ],
            'active' => true
        ]);

        // Test with 12 days (2 complete groups + 2 remaining days)
        $result = $this->service->calculateDiscount($promo->id, 120000, 12);
        
        // Should pay for: (2 groups × 3 pay days) + 2 remaining days = 8 days total
        // So discount = (12-8)/12 * total = 4/12 * 120000 = 40000
        $expectedDiscount = (int)round(120000 * (4/12));
        $this->assertEquals($expectedDiscount, $result['discountAmount']);
        $this->assertEquals(80000, $result['finalAmount']);
        $this->assertStringContainsString('applied 2 time(s) + 2 day(s) at full price', $result['details']);
    }

    /** @test */
    public function it_returns_zero_discount_for_day_based_when_duration_less_than_group_size()
    {
        $promo = Promo::create([
            'name' => 'Rent 7 Pay 5',
            'type' => 'day_based',
            'rules' => [
                [
                    'group_size' => 7,
                    'pay_days' => 5
                ]
            ],
            'active' => true
        ]);

        // Test with only 3 days (less than group size of 7)
        $result = $this->service->calculateDiscount($promo->id, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('Minimum 7 days required for this promo', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_handles_invalid_day_based_configuration()
    {
        $promo = Promo::create([
            'name' => 'Invalid Config',
            'type' => 'day_based',
            'rules' => [
                [
                    'group_size' => 5,
                    'pay_days' => 7 // pay_days > group_size, invalid
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 10);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('Invalid day-based promo configuration', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_handles_unknown_promo_type()
    {
        $promo = Promo::create([
            'name' => 'Unknown Type',
            'type' => 'unknown_type',
            'rules' => [
                [
                    'some_rule' => 'some_value'
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('Unknown promo type', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_ensures_discount_does_not_exceed_total_amount()
    {
        $promo = Promo::create([
            'name' => '200% Discount',
            'type' => 'percentage',
            'rules' => [
                [
                    'percentage' => 200 // 200% discount, should be capped
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 2);

        // Discount should be capped at the total amount
        $this->assertEquals(100000, $result['discountAmount']);
        $this->assertEquals(0, $result['finalAmount']);
    }

    /** @test */
    public function it_gets_promo_details_correctly()
    {
        $promo = Promo::create([
            'name' => 'Test Percentage Promo',
            'type' => 'percentage',
            'rules' => [
                [
                    'percentage' => 25,
                    'days' => ['Monday', 'Tuesday']
                ]
            ],
            'active' => true
        ]);

        $details = $this->service->getPromoDetails($promo->id);

        $this->assertEquals('Test Percentage Promo', $details['name']);
        $this->assertEquals('percentage', $details['type']);
        $this->assertStringContainsString('Discount 25%', $details['description']);
    }

    /** @test */
    public function it_gets_promo_details_for_nominal_type()
    {
        $promo = Promo::create([
            'name' => 'Fixed 50k Off',
            'type' => 'nominal',
            'rules' => [
                [
                    'nominal' => 50000
                ]
            ],
            'active' => true
        ]);

        $details = $this->service->getPromoDetails($promo->id);

        $this->assertEquals('Fixed 50k Off', $details['name']);
        $this->assertEquals('nominal', $details['type']);
        $this->assertEquals('Fixed discount Rp 50,000', $details['description']);
    }

    /** @test */
    public function it_gets_promo_details_for_day_based_type()
    {
        $promo = Promo::create([
            'name' => 'Weekly Special',
            'type' => 'day_based',
            'rules' => [
                [
                    'group_size' => 7,
                    'pay_days' => 5
                ]
            ],
            'active' => true
        ]);

        $details = $this->service->getPromoDetails($promo->id);

        $this->assertEquals('Weekly Special', $details['name']);
        $this->assertEquals('day_based', $details['type']);
        $this->assertEquals('Rent 7 days, pay 5 days', $details['description']);
    }

    /** @test */
    public function it_handles_null_promo_id_in_get_details()
    {
        $details = $this->service->getPromoDetails(null);

        $this->assertEquals('No promo', $details['name']);
        $this->assertNull($details['type']);
        $this->assertEquals('No discount applied', $details['description']);
    }

    /** @test */
    public function it_handles_invalid_promo_id_in_get_details()
    {
        $details = $this->service->getPromoDetails(999);

        $this->assertEquals('Invalid promo', $details['name']);
        $this->assertNull($details['type']);
        $this->assertEquals('Promo not found', $details['description']);
    }

    /** @test */
    public function it_works_with_zero_percentage_discount()
    {
        $promo = Promo::create([
            'name' => 'Zero Percent',
            'type' => 'percentage',
            'rules' => [
                [
                    'percentage' => 0
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('Discount 0% applied', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }

    /** @test */
    public function it_works_with_zero_nominal_discount()
    {
        $promo = Promo::create([
            'name' => 'Zero Nominal',
            'type' => 'nominal',
            'rules' => [
                [
                    'nominal' => 0
                ]
            ],
            'active' => true
        ]);

        $result = $this->service->calculateDiscount($promo->id, 100000, 3);

        $this->assertEquals(0, $result['discountAmount']);
        $this->assertEquals('Fixed discount Rp 0 applied', $result['details']);
        $this->assertEquals(100000, $result['finalAmount']);
    }
}
