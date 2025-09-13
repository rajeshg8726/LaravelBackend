<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmail extends Model
{
    protected $table = 'useremail'; // Specify the table name where data is saved
    protected $fillable = ['email'];
}
