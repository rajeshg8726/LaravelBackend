<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobMatch extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'job_id', 'match_score', 'ai_feedback','missing_keywords', 
    'cover_letter'];
}
