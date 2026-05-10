<?php

// In app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- Crucial for tokens!

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'is_employer',
        'company_name',
        'profile_image',
        'resume',
        'phone',
        'location',
        'bio',
        'skills',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_employer' => 'boolean',
        'skills' => 'array', // Cast skills to array (stored as JSON in DB)
    ];
}



