<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsCategories extends Model
{
    protected $table = 'jobscategories'; // Specify the table name where data is saved
    protected $fillable = ['name'];
}
