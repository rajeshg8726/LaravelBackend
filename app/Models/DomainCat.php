<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainCat extends Model
{
    use HasFactory;
    protected $table = 'jobsbydomain'; // Specify the table name where data is saved
    protected $fillable = ['name'];
}
