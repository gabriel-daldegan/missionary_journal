<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TenantCreationService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin-user {--email=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an admin user to be used for logging into the app.';

    /**
     * Execute the console command.
     */
    public function handle(TenantCreationService $tenantCreationService)
    {
        $email = $this->option('email');

        if (! $email) {
            $email = $this->ask('What is the email address of the admin user you want to create?');
        }

        $password = $this->option('password');

        if (! $password) {
            $password = Str::random();
        }

        try {
            $user = User::create([
                'name' => $email,
                'email' => $email,
                'password' => bcrypt($password),
                'is_admin' => true,
            ]);

            $tenantCreationService->createTenant($user);
        } catch (Throwable $e) {
            $this->error('Error creating admin user: '.$e->getMessage());

            return;
        }

        // add role "admin" to the user
        $user->assignRole('admin');

        $this->info('Admin user created successfully.');
        $this->info('Email: '.$email);
        $this->info('Password: '.$password);
    }
}
