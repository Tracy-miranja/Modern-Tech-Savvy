<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MakeSuperAdmin extends Command
{
    protected $signature = 'make:super-admin {email} {--name=} {--password=}';
    protected $description = 'Create or upgrade a user to the super-admin role';

    public function handle(): int
    {
        $email = trim($this->argument('email'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("'{$email}' is not a valid email address.");
            return self::FAILURE;
        }

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $user = User::where('email', $email)->first();
        $generatedPassword = null;

        if ($user) {
            $this->info("Found existing user #{$user->id} ({$user->name}) - upgrading to super-admin.");

            if ($password = $this->option('password')) {
                $user->password = Hash::make($password);
            }
        } else {
            $name = $this->option('name') ?: $this->ask('Full name for this new super-admin account');
            if (!$name) {
                $this->error('A name is required to create a new user.');
                return self::FAILURE;
            }

            $generatedPassword = $this->option('password') ?: Str::password(16);

            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($generatedPassword),
            ]);
        }

        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();

        if (!$user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }

        $this->info("'{$email}' now holds the super-admin role.");

        if ($generatedPassword) {
            $this->warn('Generated password (shown once, not stored anywhere): ' . $generatedPassword);
            $this->line('Have them log in and change it immediately.');
        }

        return self::SUCCESS;
    }
}
