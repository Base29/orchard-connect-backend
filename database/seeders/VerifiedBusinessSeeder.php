<?php

namespace Database\Seeders;

use App\Models\DirectoryCategory;
use App\Models\DirectoryListing;
use App\Models\DirectoryReview;
use App\Models\User;
use App\Models\ResidentProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VerifiedBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create realistic reviewer users (with approved/verified resident profiles)
        $reviewerNames = [
            'Ahmed Shah',
            'Zainab Malik',
            'Muhammad Khan',
            'Sana Qureshi',
            'Hamza Butt',
            'Fatima Syed',
            'Bilal Abbasi',
            'Aisha Siddiqui',
            'Usman Gill',
            'Mariam Naqvi'
        ];

        $reviewers = [];
        foreach ($reviewerNames as $index => $name) {
            $email = 'reviewer_' . ($index + 1) . '@orchardconnect.pk';
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]
            );

            ResidentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phase' => 'Phase ' . (($index % 4) + 1),
                    'block' => 'Block ' . chr(65 + ($index % 7)), // A to G
                    'house_number' => (100 + $index * 12) . '',
                    'street_number' => 'Street ' . (($index % 5) + 1),
                    'user_type' => $index % 2 === 0 ? 'owner' : 'tenant',
                    'document_path' => "documents/demo/{$user->id}/bill.pdf",
                    'status' => 'approved',
                    'is_verified' => true,
                    'verified_at' => now(),
                ]
            );

            $reviewers[] = $user;
        }

        // 2. Define business categories and verified listings
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
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'One of the best supermarkets in this area. Wide variety of products.'],
                            ['rating' => 4, 'comment' => 'Very convenient location, but can be crowded on weekends.'],
                            ['rating' => 5, 'comment' => 'Fresh bakery items and excellent customer service.'],
                        ]
                    ],
                    [
                        'name' => 'Green Valley Grocery',
                        'description' => 'Fresh organic vegetables, fruits, daily dairy items, and basic grocery products at reasonable prices.',
                        'address' => 'Block B, Phase 2, Bahria Orchard, Lahore',
                        'contact_phone' => '+923214567890',
                        'whatsapp' => '+923214567890',
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 4, 'comment' => 'Great place for fresh veggies. They stock daily.'],
                            ['rating' => 5, 'comment' => 'Very friendly staff and good pricing compared to others.'],
                        ]
                    ],
                    [
                        'name' => 'Orchard Cash & Carry',
                        'description' => 'Premium departmental store offering grocery, household items, baby care products, and daily essentials with free home delivery.',
                        'address' => 'Main Boulevard, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+923019876541',
                        'whatsapp' => '+923019876541',
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Free delivery is very prompt. Super useful for older residents.'],
                            ['rating' => 4, 'comment' => 'Clean store and well-organized aisles.'],
                        ]
                    ],
                    [
                        'name' => 'Punjab Cash & Carry',
                        'description' => 'Affordable grocery store offering wholesale prices on bulk shopping. Fresh meat, chicken, and farm vegetables available.',
                        'address' => 'Central Block, Phase 3, Bahria Orchard, Lahore',
                        'contact_phone' => '+923021234567',
                        'whatsapp' => null,
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 3, 'comment' => 'Good wholesale prices, but the fresh meat section needs cleaner management.'],
                            ['rating' => 4, 'comment' => 'Great discounts on monthly groceries! Recommended for families.'],
                        ]
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
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Perfect place to work or hang out. Excellent coffee quality.'],
                            ['rating' => 4, 'comment' => 'The burgers are amazing, but the outdoor seating can get dusty.'],
                            ['rating' => 5, 'comment' => 'Very peaceful ambiance and friendly staff.'],
                        ]
                    ],
                    [
                        'name' => 'Gourmet Bakers & Sweets',
                        'description' => 'Delicious cakes, pastries, traditional Pakistani sweets, fresh bread, and savory snacks.',
                        'address' => 'Main Boulevard, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+924235467890',
                        'whatsapp' => null,
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Always fresh bakery items. Their rusk and bread are standard purchases.'],
                            ['rating' => 4, 'comment' => 'Love their pineapple cake! Sweets are also high quality.'],
                        ]
                    ],
                    [
                        'name' => 'Kababish Grill',
                        'description' => 'Authentic Pakistani BBQ and traditional clay oven dishes. Popular for Seekh Kababs, Karahi, and fresh Naans.',
                        'address' => 'Block D, Phase 2, Bahria Orchard, Lahore',
                        'contact_phone' => '+923004561234',
                        'whatsapp' => '+923004561234',
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 4, 'comment' => 'Amazing barbecue! The spices are just right.'],
                            ['rating' => 3, 'comment' => 'Taste is great, but waiting times can be up to 45 minutes.'],
                        ]
                    ],
                    [
                        'name' => 'Cafe Orchard Delight',
                        'description' => 'A trendy cafe specializing in milkshakes, ice creams, waffles, and fast food bites. Perfect spot for kids and families.',
                        'address' => 'Block F, Phase 4, Bahria Orchard, Lahore',
                        'contact_phone' => '+923229988776',
                        'whatsapp' => '+923229988776',
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Great range of ice cream flavors. Waffles are crispy and fresh!'],
                        ]
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
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Prompt emergency support. The doctors are very professional.'],
                            ['rating' => 4, 'comment' => 'Good doctors, but the reception wait time should be improved.'],
                        ]
                    ],
                    [
                        'name' => 'Life Care Pharmacy',
                        'description' => 'Authentic medicines, skincare products, baby food, and basic diagnostic equipment available. Free home delivery within Bahria Orchard.',
                        'address' => 'Commercial Area, Phase 2, Bahria Orchard, Lahore',
                        'contact_phone' => '+923005556677',
                        'whatsapp' => '+923005556677',
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Always have authentic medicines in stock. Appreciate the home delivery service.'],
                            ['rating' => 5, 'comment' => 'Polite staff. They maintain the cold chain for vaccines.'],
                        ]
                    ],
                    [
                        'name' => 'Orchard Dental & Eye Clinic',
                        'description' => 'Modern dental care and comprehensive eye checkups under specialized consultants. Advanced sterilization and equipment.',
                        'address' => 'Block C, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+923129876543',
                        'whatsapp' => '+923129876543',
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Had a painless root canal here. Dr. Zain is fantastic!'],
                            ['rating' => 4, 'comment' => 'Professional setup. Clean and hygienic.'],
                        ]
                    ],
                    [
                        'name' => 'Shifa Family Clinic',
                        'description' => 'General health consultation, laboratory collection point, and minor trauma care. Conveniently located for Phase 3 residents.',
                        'address' => 'Block B, Phase 3, Bahria Orchard, Lahore',
                        'contact_phone' => '+923331122334',
                        'whatsapp' => null,
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 4, 'comment' => 'Good clinic for basic checkups and lab tests.'],
                        ]
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
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Sent a plumber within 30 minutes. Fixed our water pump issue quickly.'],
                            ['rating' => 4, 'comment' => 'Professional technicians. Fair rates compared to market.'],
                        ]
                    ],
                    [
                        'name' => 'Al-Makkah Paint & Hardware',
                        'description' => 'High-quality paints, sanitary equipment, construction tools, keys, locks, and general hardware items.',
                        'address' => 'Block C, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+923223334455',
                        'whatsapp' => null,
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 4, 'comment' => 'Huge variety of paint colors. The owner is very helpful in choosing.'],
                        ]
                    ],
                    [
                        'name' => 'Bright Light Electricals',
                        'description' => 'Retailer of premium LED lights, cables, switches, ceiling fans, and smart home automation equipment.',
                        'address' => 'Block G, Phase 2, Bahria Orchard, Lahore',
                        'contact_phone' => '+923055443322',
                        'whatsapp' => '+923055443322',
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Excellent collection of modern chandeliers and lights. Good warranty claims.'],
                        ]
                    ],
                    [
                        'name' => 'Orchard Plumbing & Sanitary',
                        'description' => 'Deals in standard PVC pipes, fittings, water geysers, kitchen sinks, bathroom fixtures, and structural piping.',
                        'address' => 'Commercial Area, Phase 1, Bahria Orchard, Lahore',
                        'contact_phone' => '+923146655443',
                        'whatsapp' => null,
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Standard sanitary goods. They replaced a faulty tap with no questions asked.'],
                        ]
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
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'Very satisfied with my children\'s progress here. Teachers are cooperative.'],
                            ['rating' => 4, 'comment' => 'Good infrastructure, but sports facilities could be expanded.'],
                        ]
                    ],
                    [
                        'name' => 'The City School (Orchard Campus)',
                        'description' => 'A branch of the renowned City School network, offering quality primary and secondary education with modern labs and campus.',
                        'address' => 'Phase 1, Block A, Bahria Orchard, Lahore',
                        'contact_phone' => '+924235112233',
                        'whatsapp' => null,
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 5, 'comment' => 'High-quality education and excellent extra-curricular clubs.'],
                            ['rating' => 5, 'comment' => 'Experienced faculty. Highly recommended for O-Level prep.'],
                        ]
                    ],
                    [
                        'name' => 'Beaconhouse School System',
                        'description' => 'World-class curriculum, modern classrooms, scientific laboratories, and highly trained teachers focusing on holistic child development.',
                        'address' => 'Educational Area, Phase 2, Bahria Orchard, Lahore',
                        'contact_phone' => '+924235445566',
                        'whatsapp' => null,
                        'is_verified' => true,
                        'reviews' => [
                            ['rating' => 4, 'comment' => 'Beautiful new campus. The management is professional.'],
                        ]
                    ],
                ]
            ],
        ];

        // 3. Insert categories, listings, and corresponding reviews
        foreach ($categories as $catData) {
            $category = DirectoryCategory::updateOrCreate(
                ['slug' => $catData['slug']],
                [
                    'name' => $catData['name'],
                    'icon' => $catData['icon'],
                ]
            );

            foreach ($catData['listings'] as $listData) {
                $listing = DirectoryListing::updateOrCreate(
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

                // Add corresponding reviews to the listing
                foreach ($listData['reviews'] as $reviewIndex => $reviewData) {
                    // Assign reviewer based on index loop to prevent duplication on the same listing
                    $reviewer = $reviewers[$reviewIndex % count($reviewers)];

                    DirectoryReview::updateOrCreate(
                        [
                            'directory_listing_id' => $listing->id,
                            'user_id' => $reviewer->id,
                        ],
                        [
                            'rating' => $reviewData['rating'],
                            'comment' => $reviewData['comment'],
                        ]
                    );
                }
            }
        }
    }
}
