<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Document Translation',
                'description' => 'Professional translation of personal, academic and business documents.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Certified Translation',
                'description' => 'Certified translations for official, legal and institutional purposes.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Business & Legal Translation',
                'description' => 'Translation of contracts, agreements, reports and corporate documents.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Website & Software Localization',
                'description' => 'Localization of websites, applications and digital products.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Virtual Assistance',
                'description' => 'Help with administrative and customer service related tasks.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Report Writing',
                'description' => 'Compiling your quartely and yearly reports.',
                'sort_order' => 6,
            ],

        ];


        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => Str::slug($service['name'])],
                [
                    ...$service,
                    'slug' => Str::slug($service['name']),
                    'is_active' => true,
                ]
            );
        }
    }
}
