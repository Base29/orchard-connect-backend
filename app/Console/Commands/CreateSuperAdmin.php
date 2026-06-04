<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:superadmin 
                            {--name= : The name of the superadmin}
                            {--email= : The email of the superadmin}
                            {--password= : The password of the superadmin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Super Admin user and assign the Super Admin Spatie role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=========================================');
        $this->info('        Orchard Connect SuperAdmin       ');
        $this->info('=========================================');

        // 1. Ensure Super Admin role exists
        $role = Role::findOrCreate('Super Admin', 'web');

        // 2. Gather Inputs
        $name = $this->option('name') ?: $this->ask('Enter Name');
        
        $email = $this->option('email');
        while (!$email) {
            $input = $this->ask('Enter Email Address');
            
            $validator = Validator::make(['email' => $input], [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                $this->error('Invalid email format. Please try again.');
                continue;
            }

            $email = $input;
        }

        // Check if user already exists
        $user = User::where('email', $email)->first();
        if ($user) {
            $this->warn("A user with email '{$email}' already exists.");
            if ($this->confirm('Would you like to assign the "Super Admin" role to this existing user?')) {
                $user->assignRole($role);
                $this->info("Successfully assigned 'Super Admin' role to {$user->name}!");
                return self::SUCCESS;
            } else {
                $this->error('Operation cancelled.');
                return self::FAILURE;
            }
        }

        $password = $this->option('password');
        while (!$password) {
            $input = $this->secret('Enter Secure Password');
            
            if (strlen($input) < 8) {
                $this->error('Password must be at least 8 characters long. Please try again.');
                continue;
            }

            $confirm = $this->secret('Confirm Password');
            if ($input !== $confirm) {
                $this->error('Passwords do not match. Please try again.');
                continue;
            }

            $password = $input;
        }

        // 3. Create the Super Admin User
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'active',
        ]);

        // 4. Assign the Spatie Role
        $user->assignRole($role);

        $this->info('=========================================');
        $this->info("Super Admin '{$name}' successfully created!");
        $this->info("Email: {$email}");
        $this->info("You can now log in at http://localhost:8080/admin/login");
        $this->info('=========================================');

        return self::SUCCESS;
    }
}
