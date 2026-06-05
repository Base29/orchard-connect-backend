<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ResidentProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateDemoUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:demo-users 
                            {--count=5 : The number of demo users to create}
                            {--password=password123 : The password for all created users}
                            {--email-prefix=demo : The email prefix for generated users}
                            {--clean : Wipe existing demo users matching the prefix before creating new ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create verified resident demo users for testing the platform';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================================');
        $this->info('        Orchard Connect Demo User Generator         ');
        $this->info('====================================================');

        $count = (int) $this->option('count');
        $password = $this->option('password');
        $emailPrefix = $this->option('email-prefix');
        $clean = $this->option('clean');

        if ($count <= 0) {
            $this->error('The count option must be a positive integer.');
            return self::FAILURE;
        }

        $emailPattern = $emailPrefix . '_%@orchardconnect.pk';

        // 1. Optional cleanup
        if ($clean) {
            $this->warn("Cleaning up existing demo users matching pattern: {$emailPattern}...");
            try {
                // Cascading delete is set up on resident_profiles, but let's do this safely
                $deletedCount = User::where('email', 'like', $emailPattern)->delete();
                $this->info("Successfully deleted {$deletedCount} existing demo user(s).");
            } catch (\Exception $e) {
                $this->error("Failed to clean up existing demo users: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        // 2. Resolve a Super Admin for verification field
        $verifierId = null;
        try {
            $superAdminRole = Role::where('name', 'Super Admin')->first();
            if ($superAdminRole) {
                $superAdmin = User::role('Super Admin')->first();
                if ($superAdmin) {
                    $verifierId = $superAdmin->id;
                    $this->line("Using Super Admin '{$superAdmin->name}' ({$superAdmin->email}) as the verifier.");
                }
            }
        } catch (\Throwable $e) {
            // Role table might not exist/be seeded, proceed with null verifier
            $this->line("No Super Admin found or Spatie roles not seeded. Profiling will proceed without verifier_by association.");
        }

        $createdUsers = [];
        $skippedCount = 0;

        // Seed data lists
        $phases = ['Phase 1', 'Phase 2', 'Phase 3', 'Phase 4'];
        $blocks = ['Block A', 'Block B', 'Block C', 'Block D', 'Block E', 'Block F', 'Block G'];
        $userTypes = ['owner', 'tenant'];

        // Pakistani name components
        $pakistaniFirstNames = [
            // Male
            'Muhammad', 'Ahmed', 'Ali', 'Hamza', 'Bilal', 'Usman', 'Faisal', 'Zain', 'Omer', 'Mustafa', 
            'Saad', 'Asad', 'Haris', 'Salman', 'Zeeshan', 'Adnan', 'Fawad', 'Imran', 'Tariq', 'Yasir', 
            'Shahzad', 'Babar', 'Rizwan', 'Hassan', 'Hussain', 'Junaid', 'Atif', 'Farhan', 'Kamran', 'Waqas',
            // Female
            'Aisha', 'Fatima', 'Amna', 'Zainab', 'Sana', 'Mariam', 'Hira', 'Sara', 'Anum', 'Ayesha', 
            'Nida', 'Maria', 'Sidra', 'Kiran', 'Sadia', 'Mahnoor', 'Iqra', 'Bushra', 'Alisha', 'Fiza', 
            'Hina', 'Saba', 'Javeria', 'Areeba', 'Rimsha', 'Sonia', 'Maryam', 'Noreen', 'Khadija', 'Tayyaba'
        ];

        $pakistaniLastNames = [
            'Khan', 'Ahmed', 'Ali', 'Hussain', 'Sheikh', 'Malik', 'Butt', 'Syed', 'Shah', 'Bajwa', 
            'Chaudhry', 'Gill', 'Dar', 'Abbasi', 'Siddiqui', 'Qureshi', 'Mughal', 'Farooqi', 'Naqvi', 'Zaidi', 
            'Jatoi', 'Lodhi', 'Rehman', 'Iqbal', 'Latif', 'Mirza', 'Bhatti', 'Raza', 'Hashmi', 'Rasheed'
        ];

        $this->info("\nGenerating {$count} verified demo users...");
        $this->output->progressStart($count);

        for ($i = 1; $i <= $count; $i++) {
            $email = "{$emailPrefix}_{$i}@orchardconnect.pk";

            // Prevent duplicate emails
            if (User::where('email', $email)->exists()) {
                $skippedCount++;
                $this->output->progressAdvance();
                continue;
            }

            // Generate realistic Pakistani names
            $firstName = fake()->randomElement($pakistaniFirstNames);
            $lastName = fake()->randomElement($pakistaniLastNames);
            while ($firstName === $lastName) {
                $lastName = fake()->randomElement($pakistaniLastNames);
            }
            $name = $firstName . ' ' . $lastName;

            DB::transaction(function () use ($name, $email, $password, $phases, $blocks, $userTypes, $verifierId, &$createdUsers) {
                // Create user
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'status' => 'active',
                ]);
                $user->email_verified_at = now();
                $user->save();

                // Generate random addresses
                $phase = fake()->randomElement($phases);
                $block = fake()->randomElement($blocks);
                $houseNumber = fake()->numberBetween(1, 500) . '-' . fake()->randomElement(['A', 'B', 'C', '']);
                $houseNumber = rtrim($houseNumber, '-');
                $streetNumber = 'Street ' . fake()->numberBetween(1, 25);
                $userType = fake()->randomElement($userTypes);

                // Create verified resident profile
                $profile = ResidentProfile::create([
                    'user_id' => $user->id,
                    'phase' => $phase,
                    'block' => $block,
                    'house_number' => $houseNumber,
                    'street_number' => $streetNumber,
                    'user_type' => $userType,
                    'document_path' => "documents/demo/{$user->id}/bill.pdf",
                    'status' => 'approved',
                    'is_verified' => true,
                    'verified_by' => $verifierId,
                    'verified_at' => now(),
                ]);

                $createdUsers[] = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'residency' => "{$phase}, {$block}, {$streetNumber}, House {$houseNumber}",
                    'type' => ucfirst($userType),
                ];
            });

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        if ($skippedCount > 0) {
            $this->warn("Skipped {$skippedCount} user(s) because their emails already exist in the database. Use --clean to reset.");
        }

        if (count($createdUsers) > 0) {
            $this->info("\nSuccessfully Created Users:");
            $this->table(
                ['Name', 'Email', 'Residency Details', 'Type'],
                $createdUsers
            );
            $this->info("Password for all users: '{$password}'");
        } else {
            $this->warn("\nNo new demo users were created.");
        }

        $this->info('====================================================');
        return self::SUCCESS;
    }
}
