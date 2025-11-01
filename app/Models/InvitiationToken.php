<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitiationToken extends Model
{
    protected $table = 'invitation_tokens';

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used',
    ];
}
