<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
class MakeAdmin extends Command
{
    protected $signature = 'apoint:make-admin {email : Existing account email}';
    protected $description = 'Grant administrator access to an existing Apoint account';
    public function handle(): int {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) { $this->error('No user exists with that email.'); return self::FAILURE; }
        if ($user->is_admin) { $this->info('Account is already an administrator.'); return self::SUCCESS; }
        $user->forceFill(['is_admin' => true])->save();
        $this->info("Administrator access granted to {$user->email}.");
        return self::SUCCESS;
    }
}
