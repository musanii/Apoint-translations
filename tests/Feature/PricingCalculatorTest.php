<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\PricingRule;
use App\Models\Service;
use App\Services\PricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_per_word_pricing(): void
    {
        $service = Service::create([
            'name' => 'Document Translation',
            'slug' => 'document-translation',
            'description' => 'Professional document translation.',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'currency_id' => $currency->id,
            'service_id' => $service->id,
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => 0.10,
            'minimum_charge' => 5.00,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $result = $calculator->calculate($pricingRule, 100);

        $this->assertEquals(10.00, $result['total']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals(100, $result['quantity']);
    }


    public function test_it_applies_the_minimum_charge(): void
    {
        $service = Service::create([
            'name' => 'Document Translation',
            'slug' => 'document-translation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'service_id' => $service->id,
            'currency_id' => $currency->id,
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => 0.10,
            'minimum_charge' => 5.00,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $result = $calculator->calculate($pricingRule, 20);

        $this->assertEquals(2.00, $result['subtotal']);
        $this->assertEquals(5.00, $result['total']);
    }

    public function test_it_rejects_zero_quantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $pricingRule = new PricingRule([
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => 0.10,
        ]);

        $calculator = app(PricingCalculator::class);

        $calculator->calculate($pricingRule, 0);
    }


    public function test_it_calculates_per_page_pricing(): void
    {
        $service = Service::create([
            'name' => 'Certified Translation',
            'slug' => 'certified-translation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'service_id' => $service->id,
            'currency_id' => $currency->id,
            'pricing_type' => 'per_page',
            'unit' => 'page',
            'rate' => 15.00,
            'minimum_charge' => null,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $result = $calculator->calculate($pricingRule, 4);

        $this->assertEquals(60.00, $result['total']);
        $this->assertEquals('page', $result['unit']);
    }


    public function test_it_calculates_fixed_pricing(): void
    {
        $service = Service::create([
            'name' => 'Document Handling',
            'slug' => 'document-handling',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'service_id' => $service->id,
            'currency_id' => $currency->id,
            'pricing_type' => 'fixed',
            'unit' => 'job',
            'rate' => 25.00,
            'minimum_charge' => null,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $result = $calculator->calculate($pricingRule, 3);

        $this->assertEquals(25.00, $result['total']);
        $this->assertEquals('fixed', $result['pricing_type']);
    }


    public function test_it_respects_currency_decimal_places(): void
    {
        $service = Service::create([
            'name' => 'Document Translation',
            'slug' => 'document-translation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = Currency::create([
            'code' => 'JPY',
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'decimal_places' => 0,
            'is_active' => true,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'service_id' => $service->id,
            'currency_id' => $currency->id,
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => 1.75,
            'minimum_charge' => null,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $result = $calculator->calculate($pricingRule, 3);

        $this->assertEquals(5.25, $result['subtotal']);
        $this->assertEquals(5.0, $result['total']);
    }

    public function test_it_rejects_unsupported_pricing_types(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $pricingRule = new PricingRule([
            'pricing_type' => 'hourly',
            'unit' => 'hour',
            'rate' => 50.00,
        ]);

        $calculator = app(PricingCalculator::class);

        $calculator->calculate($pricingRule, 2);
    }

    public function test_it_rejects_inactive_pricing_rules(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $pricingRule = new PricingRule([
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => 0.10,
            'is_active' => false,
        ]);

        $calculator = app(PricingCalculator::class);

        $calculator->calculate($pricingRule, 100);
    }


    public function test_it_rejects_inactive_currencies(): void
    {
        $service = Service::create([
            'name' => 'Document Translation',
            'slug' => 'document-translation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => false,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'service_id' => $service->id,
            'currency_id' => $currency->id,
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => 0.10,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $this->expectException(\InvalidArgumentException::class);

        $calculator->calculate($pricingRule, 100);
    }


    public function test_it_rejects_negative_rates(): void
    {
        $service = Service::create([
            'name' => 'Document Translation',
            'slug' => 'document-translation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'service_id' => $service->id,
            'currency_id' => $currency->id,
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => -0.10,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $this->expectException(\InvalidArgumentException::class);

        $calculator->calculate($pricingRule, 100);
    }


    public function test_it_rejects_negative_minimum_charges(): void
    {
        $service = Service::create([
            'name' => 'Document Translation',
            'slug' => 'document-translation',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);

        $pricingRule = PricingRule::create([
            'service_id' => $service->id,
            'currency_id' => $currency->id,
            'pricing_type' => 'per_word',
            'unit' => 'word',
            'rate' => 0.10,
            'minimum_charge' => -5.00,
            'is_active' => true,
        ]);

        $calculator = app(PricingCalculator::class);

        $this->expectException(\InvalidArgumentException::class);

        $calculator->calculate($pricingRule, 100);
    }

    public function test_it_rejects_pricing_rules_for_inactive_services(): void
{
    $service = Service::create([
        'name' => 'Certified Translation',
        'slug' => 'certified-translation',
        'is_active' => false,
        'sort_order' => 1,
    ]);

    $currency = Currency::create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => false,
    ]);

    $pricingRule = PricingRule::create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 0.10,
        'is_active' => true,
    ]);

    $calculator = app(PricingCalculator::class);

    $this->expectException(\InvalidArgumentException::class);

    $calculator->calculate($pricingRule, 100);
}
}
