<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchesCat extends Model
{
    use HasFactory;
    protected $table = 'jobsbybatches'; // Specify the table name where data is saved
    protected $fillable = ['name'];
}
