<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationTracker extends Model
{
    use HasFactory;

    protected $table = 'application_tracker';

    protected $fillable = [
        'user_id',
        'company_name',
        'job_title',
        'job_description',
        'job_url',
        'status',
        'notes',
        'applied_at',
        'last_status_change',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'last_status_change' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
