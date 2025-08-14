<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpLevelCat extends Model
{
    use HasFactory;
    protected $table = 'jobsbyexplevel'; // Specify the table name where data is saved
    protected $fillable = ['name'];
}
