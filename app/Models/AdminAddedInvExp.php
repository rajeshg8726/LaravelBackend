<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAddedInvExp extends Model
{
    use HasFactory;

    protected $table = 'adminaddedinterviews';
    
    protected $fillable = [
        'email',
        'name',
        'companyName',
        'title',
        'rounds',
        'experience',
        'jobRole',
        'details',
        'companyOption',
        'roleOption',
        'workOption',
        'anonymous',
    ];
}
