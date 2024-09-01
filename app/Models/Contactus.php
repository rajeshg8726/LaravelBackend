<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contactus extends Model
{
    protected $table = 'contactus'; // Specify the table name where data is saved
    protected $fillable = ['name', 'email', 'message'];

}
