<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Io238\ISOCountries\Models\Language as IsoLanguage;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Languages available in the ISO reference database.
        $isoLanguages = [
            'en' => ['region' => 'Global', 'sort_order' => 1],
            'sw' => ['region' => 'East Africa', 'sort_order' => 2],
            'fr' => ['region' => 'Global', 'sort_order' => 3],
            'de' => ['region' => 'Global', 'sort_order' => 4],
            'es' => ['region' => 'Global', 'sort_order' => 5],
            'ar' => ['region' => 'Global', 'sort_order' => 6],
            'zh' => ['region' => 'Global', 'sort_order' => 7],
            'pt' => ['region' => 'Global', 'sort_order' => 8],
            'ki' => ['region' => 'East Africa', 'sort_order' => 9],
        ];

        foreach ($isoLanguages as $code => $details) {
            $isoLanguage = IsoLanguage::findOrFail($code);

            Language::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $isoLanguage->name,
                    'native_name' => $isoLanguage->native_name,
                    'region' => $details['region'],
                    'is_active' => true,
                    'sort_order' => $details['sort_order'],
                ]
            );

            // Additional Kenyan languages not covered by ISO 639-1.
            $kenyanLanguages = [
                [
                    'name' => 'Dholuo',
                    'code' => 'luo',
                    'native_name' => 'Dholuo',
                    'sort_order' => 10,
                ],
                [
                    'name' => 'Luhya',
                    'code' => 'luy',
                    'native_name' => 'Luhya',
                    'sort_order' => 11,
                ],
                [
                    'name' => 'Kalenjin',
                    'code' => 'kln',
                    'native_name' => 'Kalenjin',
                    'sort_order' => 12,
                ],
                [
                    'name' => 'Maasai',
                    'code' => 'mas',
                    'native_name' => 'Maa',
                    'sort_order' => 13,
                ],
            ];

            foreach ($kenyanLanguages as $language) {
                Language::updateOrCreate(
                    ['code' => $language['code']],
                    [
                        ...$language,
                        'region' => 'Kenya',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
