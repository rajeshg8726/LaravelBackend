<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasFactory;

    protected $table = 'blogpost'; // ✅ Your schema says singular

    public $timestamps = true; // ✅ required since you have created_at & updated_at

    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'category',
        'type',
        'tags',
        'image',
        'publish_date',
        'status',
        'estimated_read_time',
        'anonymous',
    ];
}
