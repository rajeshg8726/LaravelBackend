<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationCat extends Model
{
    use HasFactory;
    protected $table = 'jobsbylocation'; // Specify the table name where data is saved
    protected $fillable = ['name'];
}
