<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnumValue extends Model
{   
    protected $fillable = ['domain','slug','label','metadata','sort','active','version','created_by'];
    protected $casts = ['metadata' => 'array', 'active' => 'boolean'];
}
