<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Worktypes extends Model
{
    use HasFactory;
    protected $table = 'work_type'; // Specify the table name where data is saved
    protected $fillable = ['name'];
}
