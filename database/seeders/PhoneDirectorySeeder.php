<?php

namespace Database\Seeders;

use App\Models\PhoneDirectory;
use Illuminate\Database\Seeder;

class PhoneDirectorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contacts = [
            // Emergency / Security
            [
                'name' => 'Bahria Security Control Room',
                'phone_number' => '+92 42 111 002 003',
                'description' => '24/7 central security monitoring and response team for Bahria Orchard.',
                'category' => 'Security',
                'order' => 1,
            ],
            [
                'name' => 'Main Gate Security Checkpost',
                'phone_number' => '+92 42 35978121',
                'description' => 'Visitor entry coordination and gate clearance inquiries.',
                'category' => 'Security',
                'order' => 2,
            ],
            [
                'name' => 'Bahria Orchard Police Chowki',
                'phone_number' => '+92 300 8443311',
                'description' => 'Local police post located near the main entrance.',
                'category' => 'Security',
                'order' => 3,
            ],

            // Health / Medical
            [
                'name' => 'Bahria Orchard Hospital Emergency',
                'phone_number' => '+92 42 111 515 515',
                'description' => '24/7 Trauma and emergency medical services. Ambulances available on call.',
                'category' => 'Emergency & Health',
                'order' => 1,
            ],
            [
                'name' => 'Orchard Pharmacy & Clinic',
                'phone_number' => '+92 42 35978180',
                'description' => 'Located in Phase 1 Commercial Area. Home delivery of medicines.',
                'category' => 'Emergency & Health',
                'order' => 2,
            ],
            [
                'name' => 'Rescue 1122 Helpline',
                'phone_number' => '1122',
                'description' => 'National emergency rescue, ambulance, and fire services.',
                'category' => 'Emergency & Health',
                'order' => 3,
            ],

            // Utilities & Maintenance
            [
                'name' => 'LESCO Electricity Complaints',
                'phone_number' => '+92 42 111 000 118',
                'description' => 'LESCO power outage and electrical line fault complaints.',
                'category' => 'Utilities',
                'order' => 1,
            ],
            [
                'name' => 'Sui Northern Gas Emergency',
                'phone_number' => '1199',
                'description' => 'Gas leakage reports and emergency maintenance line.',
                'category' => 'Utilities',
                'order' => 2,
            ],
            [
                'name' => 'Bahria Water Supply Dept',
                'phone_number' => '+92 42 35978135',
                'description' => 'Water tank delivery requests, bore updates, and pressure complaints.',
                'category' => 'Utilities',
                'order' => 3,
            ],
            [
                'name' => 'Plumbing & Electrician Dispatch',
                'phone_number' => '+92 42 35978140',
                'description' => 'Society maintenance office for booking plumber/electrician home visits.',
                'category' => 'Utilities',
                'order' => 4,
            ],

            // Administration & Office
            [
                'name' => 'Bahria Orchard Head Office',
                'phone_number' => '+92 42 35978150',
                'description' => 'General queries, transfers, and registry department.',
                'category' => 'Administration',
                'order' => 1,
            ],
            [
                'name' => 'Society Billing & Maintenance Office',
                'phone_number' => '+92 42 35978155',
                'description' => 'Maintenance bills, charges disputes, and payments processing center.',
                'category' => 'Administration',
                'order' => 2,
            ],
            [
                'name' => 'Horticulture & Waste Management',
                'phone_number' => '+92 42 35978160',
                'description' => 'Garbage collection scheduling, street cleaning, and park maintenance queries.',
                'category' => 'Administration',
                'order' => 3,
            ],
        ];

        foreach ($contacts as $contact) {
            PhoneDirectory::updateOrCreate(
                ['name' => $contact['name']],
                $contact
            );
        }
    }
}
