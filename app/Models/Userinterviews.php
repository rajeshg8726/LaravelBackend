<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Userinterviews extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'companyName',
        'rounds',
        'experience',
        'jobRole',
        'details',
        'anonymous',
    ];
}
