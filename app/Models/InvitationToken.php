<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitationToken extends Model
{
    protected $table = 'invitation_tokens';

    protected $casts = [
        'used' => 'boolean',
    ];

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used',
    ];
}
