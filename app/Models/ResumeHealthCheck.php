<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumeHealthCheck extends Model
{
    use HasFactory;

    protected $table = 'resume_health_checks';

    protected $fillable = [
        'user_id',
        'overall_score',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
