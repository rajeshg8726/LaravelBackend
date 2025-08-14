<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jobs extends Model
{
    

    protected $table = 'jobs'; 

    protected $fillable = [
        'title',
        'role',
        'pay',
        'location',
        'description',
        'eligibility',
        'rolesAndResponsibilities',
        'requirements',
        'niceToHave',
        'jobtype',
        'jobbyrole',
        'jobbycity',
        'batch1',
        'batch2',
        'batch3',
        'jobpayrange', // Optional field for job pay range
        'jobexplevel', // for job experience level like intern, fresher,
        'joblink',
        'batches',
        'image'
    ];
}
