<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\InvitationToken;
use App\Mail\UserInvitationMail;

class SendUserInvitation extends Command
{
    protected $signature = 'invite:send {email}';
    protected $description = 'Send an invitation to a user via email';

    public function handle()
    {
        $email = $this->argument('email');
        $token = Str::uuid();

        $invitation = InvitationToken::create([
            'email' => $email,
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new UserInvitationMail($invitation));

        $this->info("Invitation sent to {$email}");
    }
}