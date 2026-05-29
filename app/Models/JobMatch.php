<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobMatch extends Model
{
    use HasFactory;
    
    protected $fillable = [
    'user_id',
    'job_id',
    'match_score',
    'ai_feedback',
    'missing_keywords',
    'cover_letter',
    'interview_questions',
    'optimized_profile',
    'salary_benchmark',
    'score_breakdown',
];

    public function job()
    {
        return $this->belongsTo(Jobs::class, 'job_id');
    }
}
