<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Language;
use App\Models\PricingRule;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $kes = Currency::where('code', 'KES')->firstOrFail();

        $services = Service::whereIn('slug', [
            'document-translation',
            'certified-translation',
            'business-legal-translation',
            'website-software-localization',
            'virtual-assistance',
            'report-writing',
        ])->get()->keyBy('slug');

        $english = Language::where('code', 'en')->firstOrFail();
        $swahili = Language::where('code', 'sw')->firstOrFail();

        $rules = [
            [
                'service' => 'document-translation',
                'type' => 'per_word',
                'unit' => 'word',
                'rate' => 8,
                'minimum' => 800,
                'source' => 'en',
                'target' => 'sw',
                'description' => 'Demo rate for English to Swahili document translation.',
            ],
            [
                'service' => 'document-translation',
                'type' => 'per_word',
                'unit' => 'word',
                'rate' => 8,
                'minimum' => 800,
                'source' => 'sw',
                'target' => 'en',
                'description' => 'Demo rate for Swahili to English document translation.',
            ],
            [
                'service' => 'certified-translation',
                'type' => 'per_page',
                'unit' => 'page',
                'rate' => 1500,
                'minimum' => 1500,
                'source' => 'en',
                'target' => 'sw',
                'description' => 'Demo certified translation rate per page.',
            ],
            [
                'service' => 'business-legal-translation',
                'type' => 'per_word',
                'unit' => 'word',
                'rate' => 15,
                'minimum' => 2500,
                'source' => 'en',
                'target' => 'sw',
                'description' => 'Demo business and legal translation rate.',
            ],
            [
                'service' => 'website-software-localization',
                'type' => 'per_word',
                'unit' => 'word',
                'rate' => 12,
                'minimum' => 3000,
                'source' => 'en',
                'target' => 'sw',
                'description' => 'Demo website and software localization rate.',
            ],
            [
                'service' => 'virtual-assistance',
                'type' => 'fixed',
                'unit' => 'project',
                'rate' => 5000,
                'minimum' => null,
                'source' => null,
                'target' => null,
                'description' => 'Demo fixed starting rate for virtual assistance.',
            ],
            [
                'service' => 'report-writing',
                'type' => 'per_word',
                'unit' => 'word',
                'rate' => 10,
                'minimum' => 1500,
                'source' => 'en',
                'target' => 'sw',
                'description' => 'Demo report writing rate per word.',
            ],
        ];

        foreach ($rules as $rule) {
            $service = $services->get($rule['service']);

            if (! $service) {
                continue;
            }

            $sourceId = match ($rule['source']) {
                'en' => $english->id,
                'sw' => $swahili->id,
                default => null,
            };

            $targetId = match ($rule['target']) {
                'en' => $english->id,
                'sw' => $swahili->id,
                default => null,
            };

            PricingRule::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'currency_id' => $kes->id,
                    'source_language_id' => $sourceId,
                    'target_language_id' => $targetId,
                    'pricing_type' => $rule['type'],
                ],
                [
                    'unit' => $rule['unit'],
                    'rate' => $rule['rate'],
                    'minimum_charge' => $rule['minimum'],
                    'description' => $rule['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
