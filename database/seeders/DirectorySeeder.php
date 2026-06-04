<?php

namespace Database\Seeders;

use App\Models\DirectoryCategory;
use App\Models\DirectoryListing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DirectorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Supermarkets & Groceries',
                'slug' => 'supermarkets-groceries',
                'icon' => 'heroicon-o-shopping-bag',
                'listings' => [
                    [
                        'name' => 'Al-Fatah Supermarket',
                        'description' => 'Your one-stop destination for all grocery needs, imported goods, cosmetics, and household essentials. Located centrally in Bahria Orchard.',
                        'address' => 'Commercial Area, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+9242111123456',
                        'whatsapp' => '+923001234567',
                        'is_verified' => true,
                    ],
                    [
                        'name' => 'Green Valley Grocery',
                        'description' => 'Fresh organic vegetables, fruits, daily dairy items, and basic grocery products at reasonable prices.',
                        'address' => 'Block B, Phase 2, Bahria Orchard, Lahore',
                        'contact_phone' => '+923214567890',
                        'whatsapp' => '+923214567890',
                        'is_verified' => true,
                    ],
                ]
            ],
            [
                'name' => 'Restaurants & Cafes',
                'slug' => 'restaurants-cafes',
                'icon' => 'heroicon-o-cake',
                'listings' => [
                    [
                        'name' => 'The Orchard Cafe',
                        'description' => 'Cozy cafe offering premium blends of coffee, fresh pizzas, burgers, pasta, and desserts. Outdoor seating is available.',
                        'address' => 'Civic Center, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+923009876543',
                        'whatsapp' => '+923009876543',
                        'is_verified' => true,
                    ],
                    [
                        'name' => 'Gourmet Bakers & Sweets',
                        'description' => 'Delicious cakes, pastries, traditional Pakistani sweets, fresh bread, and savory snacks.',
                        'address' => 'Main Boulevard, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+924235467890',
                        'whatsapp' => null,
                        'is_verified' => true,
                    ],
                ]
            ],
            [
                'name' => 'Healthcare & Clinics',
                'slug' => 'healthcare-clinics',
                'icon' => 'heroicon-o-heart',
                'listings' => [
                    [
                        'name' => 'Orchard Medical Complex',
                        'description' => '24/7 emergency care, family physicians, pediatricians, gynecologists, and an attached pharmacy for all your medical needs.',
                        'address' => 'Central Plaza, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+924235123456',
                        'whatsapp' => '+923151122334',
                        'is_verified' => true,
                    ],
                    [
                        'name' => 'Life Care Pharmacy',
                        'description' => 'Authentic medicines, skincare products, baby food, and basic diagnostic equipment available. Free home delivery within Bahria Orchard.',
                        'address' => 'Commercial Area, Phase 2, Bahria Orchard, Lahore',
                        'contact_phone' => '+923005556677',
                        'whatsapp' => '+923005556677',
                        'is_verified' => true,
                    ],
                ]
            ],
            [
                'name' => 'Hardware & Maintenance',
                'slug' => 'hardware-maintenance',
                'icon' => 'heroicon-o-wrench',
                'listings' => [
                    [
                        'name' => 'QuickFix Electricians & Plumbers',
                        'description' => 'Professional repair services for home electrical fittings, water pumps, plumbing issues, and AC servicing.',
                        'address' => 'Service Block, Phase 3, Bahria Orchard, Lahore',
                        'contact_phone' => '+923120001122',
                        'whatsapp' => '+923120001122',
                        'is_verified' => true,
                    ],
                    [
                        'name' => 'Al-Makkah Paint & Hardware',
                        'description' => 'High-quality paints, sanitary equipment, construction tools, keys, locks, and general hardware items.',
                        'address' => 'Block C, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+923223334455',
                        'whatsapp' => null,
                        'is_verified' => true,
                    ],
                ]
            ],
            [
                'name' => 'Education & Schools',
                'slug' => 'education-schools',
                'icon' => 'heroicon-o-academic-cap',
                'listings' => [
                    [
                        'name' => 'Orchard Grammar School',
                        'description' => 'Modern curriculum school from Playgroup to Matric/O-Levels, with spacious play areas and qualified teaching staff.',
                        'address' => 'Educational Zone, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+924235999000',
                        'whatsapp' => null,
                        'is_verified' => true,
                    ],
                ]
            ],
        ];

        foreach ($categories as $catData) {
            $category = DirectoryCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'icon' => $catData['icon'],
                ]
            );

            foreach ($catData['listings'] as $listData) {
                DirectoryListing::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $listData['name']
                    ],
                    [
                        'description' => $listData['description'],
                        'address' => $listData['address'],
                        'contact_phone' => $listData['contact_phone'],
                        'whatsapp' => $listData['whatsapp'],
                        'is_verified' => $listData['is_verified'],
                        'logo_url' => null,
                    ]
                );
            }
        }
    }
}
