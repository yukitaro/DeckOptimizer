<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InviteUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invite:user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $token = Str::random(40);

        InvitationToken::updateOrCreate(
            ['email' => $email],
            ['token' => $token, 'expires_at' => now()->addDays(7), 'used' => false]
        );

        $this->info("Invite link: " . url("/register?token={$token}"));
    }
}
