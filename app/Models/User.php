<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'password',  // Make sure this is included for password
        'role',
        'location'
    ];
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites');
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // For password hashing, use the following method if using Auth:
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
}
