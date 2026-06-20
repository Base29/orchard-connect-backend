<?php

namespace Database\Seeders;

use App\Models\DirectoryCategory;
use Illuminate\Database\Seeder;

class DirectoryCategorySeeder extends Seeder
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
            ],
            [
                'name' => 'Restaurants & Cafes',
                'slug' => 'restaurants-cafes',
                'icon' => 'heroicon-o-cake',
            ],
            [
                'name' => 'Healthcare & Clinics',
                'slug' => 'healthcare-clinics',
                'icon' => 'heroicon-o-heart',
            ],
            [
                'name' => 'Hardware & Maintenance',
                'slug' => 'hardware-maintenance',
                'icon' => 'heroicon-o-wrench',
            ],
            [
                'name' => 'Education & Schools',
                'slug' => 'education-schools',
                'icon' => 'heroicon-o-academic-cap',
            ],
            [
                'name' => 'Real Estate & Builders',
                'slug' => 'real-estate-builders',
                'icon' => 'heroicon-o-home',
            ],
            [
                'name' => 'Beauty Salons & Spas',
                'slug' => 'salons-spas',
                'icon' => 'heroicon-o-sparkles',
            ],
            [
                'name' => 'Automobile Services',
                'slug' => 'automobile-services',
                'icon' => 'heroicon-o-cog',
            ],
            [
                'name' => 'Gyms & Fitness Centers',
                'slug' => 'gyms-fitness',
                'icon' => 'heroicon-o-bolt',
            ],
            [
                'name' => 'Laundry & Dry Cleaning',
                'slug' => 'laundry-dry-cleaning',
                'icon' => 'heroicon-o-scissors',
            ],
        ];

        foreach ($categories as $category) {
            DirectoryCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                ]
            );
        }
    }
}
