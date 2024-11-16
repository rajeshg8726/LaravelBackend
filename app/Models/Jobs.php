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
        'jobtype',
        'jobbyrole',
        'jobbycity',
        'batch1',
        'batch2',
        'batch3',
        'joblink',
        'batches',
        'image'
    ];
}
