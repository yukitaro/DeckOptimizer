<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\CollectionManagement;
use App\Models\DeckManagement;
use App\Notifications\PasswordResetLink;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
     use HasApiTokens, Notifiable, HasFactory;

    public function ownedDecks(): HasMany
    {
        return $this->hasMany(DeckManagement::class, 'deck_owner_id');
    }

    public function ownedCollections(): HasMany
    {
        return $this->hasMany(CollectionManagement::class, 'owner_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetLink($token));
    }

    public function roles() {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($roleName) {
        return $this->roles->contains('name', $roleName);
    }

    public function hasPermission($permissionName) {
        return $this->roles->flatMap->permissions->contains('name', $permissionName);
    }

    public function isSuperuser() {
        return $this->is_superuser === true;
    }

    public function permissions()
    {
        return $this->hasManyThrough(Permission::class, Role::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'alias',
        'is_superuser'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
