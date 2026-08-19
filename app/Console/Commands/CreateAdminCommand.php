<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {email=admin@sudoshz.ir} {password=ShirazAdmin2026!} {--name=مدیر}';
    protected $description = 'Create or update admin user';
    public function handle(): int
    {
        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->option('name'),
                'password' => Hash::make($this->argument('password')),
            ]
        );
        $this->info("Admin ready: {$user->email}");
        return self::SUCCESS;
    }
}
