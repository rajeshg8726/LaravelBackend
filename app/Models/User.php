<?php

// In app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- Crucial for tokens!

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'is_employer',
        'company_name',
        'profile_image',
        'resume',
        'resume_text',
        'phone',
        'location',
        'bio',
        'skills',
        'work_experience',
        'education',
        'last_credit_refresh_at',
        'has_received_profile_bonus',
        'is_first_analysis_free_used',
        'is_first_resume_health_free_used',
        'is_pro',
        'pro_expires_at',
        'ai_credits',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_employer'               => 'boolean',
        'is_pro'                    => 'boolean',
        'skills'                    => 'array',
        'work_experience'           => 'array',
        'education'                 => 'array',
        'last_credit_refresh_at'    => 'datetime',
        'pro_expires_at'            => 'datetime',
        'has_received_profile_bonus'  => 'boolean',
        'is_first_analysis_free_used' => 'boolean',
        'is_first_resume_health_free_used' => 'boolean',
    ];

    protected $appends = [
        'profile_completeness',
    ];

    /* ── PROFILE COMPLETENESS CALCULATOR ── */
    public function getProfileCompletenessAttribute()
    {
        $checks = [
            !empty($this->full_name),
            !empty($this->phone),
            !empty($this->location),
            !empty($this->bio),
            (is_array($this->skills) ? count($this->skills) > 0 : !empty($this->skills)),
            !empty($this->profile_image),
            !empty($this->resume),
        ];

        $total = count($checks);
        $completed = count(array_filter($checks));

        return (int) round(($completed / $total) * 100);
    }

    /* ── LAZY/ON-DEMAND CREDIT REFRESH (OPTION A) ── */
    public function refreshCreditsIfEligible()
    {
        if ($this->is_pro) {
            return;
        }

        $now = now();

        if (empty($this->last_credit_refresh_at)) {
            $this->last_credit_refresh_at = $now;
            $this->save();
            return;
        }

        $lastRefresh = \Carbon\Carbon::parse($this->last_credit_refresh_at);
        $diffInDays = $lastRefresh->diffInDays($now);

        if ($diffInDays >= 7) {
            $weeksPassed = (int) floor($diffInDays / 7);

            if ($weeksPassed > 0) {
                // Increment credits by +1 per week, capped at a maximum of 6 total credits
                $newCredits = min($this->ai_credits + $weeksPassed, 6);
                
                $this->ai_credits = $newCredits;
                $this->last_credit_refresh_at = $lastRefresh->addDays($weeksPassed * 7);
                $this->save();
            }
        }
    }

    /* ── ONE-TIME PROFILE COMPLETION BONUS (+3 CREDITS) ── */
    public function checkAndApplyProfileBonus()
    {
        if (!$this->is_pro && !$this->has_received_profile_bonus) {
            if ($this->profile_completeness >= 80) {
                $this->increment('ai_credits', 3);
                $this->has_received_profile_bonus = true;
                $this->save();
            }
        }
    }

    /* ── AUTOMATED RESUME PARSING ── */
    public function parseResumeText()
    {
        if (empty($this->resume)) {
            return null;
        }

        // Avoid re-parsing if we already have it
        if (!empty($this->resume_text)) {
            return $this->resume_text;
        }

        // Get relative path on the public disk (strip storage/)
        $relativeDiskPath = str_replace('storage/', '', $this->resume);
        
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($relativeDiskPath)) {
            return null;
        }

        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($relativeDiskPath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $extractedText = '';

        try {
            if ($extension === 'pdf') {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($fullPath);
                $extractedText = $pdf->getText();
            } elseif ($extension === 'docx') {
                $extractedText = $this->extractDocxText($fullPath);
            } elseif ($extension === 'doc') {
                $extractedText = strip_tags(file_get_contents($fullPath));
                $extractedText = preg_replace('/[^\x20-\x7E\s]/', '', $extractedText);
            }

            // Clean up whitespace
            $extractedText = preg_replace('/\s+/', ' ', $extractedText);
            $extractedText = trim($extractedText);

            if (!empty($extractedText)) {
                $this->resume_text = $extractedText;
                $this->save();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Resume parsing failed: ' . $e->getMessage());
        }

        return $this->resume_text;
    }

    private function extractDocxText(string $filePath): string
    {
        $text = "";
        if (file_exists($filePath)) {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    $data = $zip->getFromIndex($index);
                    $xml = new \SimpleXMLElement($data);
                    $namespaces = $xml->getNamespaces(true);
                    if (isset($namespaces['w'])) {
                        $xml->registerXPathNamespace('w', $namespaces['w']);
                        $paragraphs = $xml->xpath('//w:p');
                        foreach ($paragraphs as $p) {
                            $pText = '';
                            $runs = $p->xpath('.//w:t');
                            foreach ($runs as $r) {
                                $pText .= (string) $r;
                            }
                            if (!empty($pText)) {
                                $text .= $pText . "\n";
                            }
                        }
                    } else {
                        $text = strip_tags($data);
                    }
                }
                $zip->close();
            }
        }
        return trim($text);
    }

    public function applicationTracker()
    {
        return $this->hasMany(ApplicationTracker::class, 'user_id');
    }

    public function savedJobs()
    {
        return $this->belongsToMany(Jobs::class, 'saved_jobs', 'user_id', 'job_id')->withTimestamps();
    }

    /* ── RESUME TEXT SANITIZERS FOR UTF-8 COMPATIBILITY ── */
    public function getResumeTextAttribute($value)
    {
        if (empty($value)) {
            return '';
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
        if (function_exists('iconv')) {
            return iconv('UTF-8', 'UTF-8//IGNORE', $value);
        }
        return $value;
    }

    public function setResumeTextAttribute($value)
    {
        if (empty($value)) {
            $sanitized = '';
        } elseif (function_exists('mb_convert_encoding')) {
            $sanitized = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        } elseif (function_exists('iconv')) {
            $sanitized = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        } else {
            $sanitized = $value;
        }
        $this->attributes['resume_text'] = $sanitized;
    }
}



