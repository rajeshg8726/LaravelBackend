<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\JobMatch;
use App\Models\Jobs;
use Illuminate\Support\Facades\Log;

class AiMatchController  extends Controller
{
    /* ──────────────────────────────────────────────────────────────────
     * MODEL CONFIG
     * Free users  → Groq + Llama 3.3 70B   (cheap & fast)
     * PRO  users  → Gemini 2.5 Flash        (premium quality)
     * ────────────────────────────────────────────────────────────── */
    private string $groqModel   = 'llama-3.3-70b-versatile';
    private string $geminiModel = 'gemini-2.5-flash';

    public function generateMatch(Request $request)
    {
        $request->validate(['job_id' => 'required|integer']);

        $user   = $request->user();
        $jobId  = $request->job_id;

        $forceRefresh = $request->boolean('force_refresh', false);

        // 1. Return cached result unless force refresh is requested
        $existingMatch = JobMatch::where('user_id', $user->id)
            ->where('job_id', $jobId)
            ->first();

        if ($existingMatch && !$forceRefresh) {
            return response()->json(['success' => true, 'data' => $existingMatch]);
        }

        if ($existingMatch && $forceRefresh) {
            $existingMatch->delete();
        }

        // 2. Check and deduct credits (first analysis is free)
        // Evaluate PRO status here too — expired PRO users must use credits
        $isActivePro = $user->is_pro
            && $user->pro_expires_at !== null
            && $user->pro_expires_at->isFuture();

        $isFirstFree = !$user->is_first_analysis_free_used;
        $creditDeducted = false;

        if (!$isActivePro) {
            if (!$isFirstFree) {
                if ($user->ai_credits <= 0) {
                    return response()->json([
                        'success'          => false,
                        'message'          => 'Out of credits',
                        'requires_upgrade' => true
                    ], 402);
                }
                // Deduct credit upfront to prevent concurrency exploits
                $user->decrement('ai_credits');
                $creditDeducted = true;
            }
        }

        // 3. Load job and build profile strings
        $job = Jobs::findOrFail($jobId);

        $formatField = function ($field) {
            if (is_array($field)) return implode(', ', $field);
            return $field ?? 'Not provided';
        };

        $formatExperience = function ($exp) {
            if (empty($exp) || !is_array($exp)) return "Not provided";
            $formatted = [];
            foreach ($exp as $item) {
                $role = $item['role'] ?? 'Role';
                $company = $item['company'] ?? 'Company';
                $from = $item['from_year'] ?? '';
                $to = !empty($item['is_current']) ? 'Present' : ($item['to_year'] ?? 'N/A');
                $formatted[] = "- {$role} at {$company} ({$from} – {$to})";
            }
            return implode("\n", $formatted);
        };

        $formatEducation = function ($edu) {
            if (empty($edu) || !is_array($edu)) return "Not provided";
            $formatted = [];
            foreach ($edu as $item) {
                $degree = $item['degree'] ?? 'Degree';
                $institution = $item['institution'] ?? 'Institution';
                $year = $item['year'] ?? '';
                $formatted[] = "- {$degree}, {$institution} ({$year})";
            }
            return implode("\n", $formatted);
        };

        // Self-heal/lazy-parse resume if not already done
        $resumeText = $user->parseResumeText();

        $candidateProfile = "Name: " . ($user->full_name ?? 'Candidate') .
            "\nSkills: " . $formatField($user->skills) .
            "\nBio: " . $formatField($user->bio) .
            "\n\nWork Experience:\n" . $formatExperience($user->work_experience) .
            "\n\nEducation:\n" . $formatEducation($user->education);

        if (!empty($resumeText)) {
            $candidateProfile .= "\n\nExtracted Resume Text Content:\n" . $resumeText;
        }

        $jobDescription   = "Title: "        . $job->title                      .
            "\nRequirements: " . $formatField($job->requirements) .
            "\nDescription: "  . $formatField($job->description);

        $userName = $user->full_name ?? 'Candidate';

        // 4. Build system instruction and user prompt
        $systemInstruction = 'You are a precise JSON API. You only ever respond with a single valid raw JSON object. Never use markdown code blocks. Never add explanations.';

        $prompt = <<<PROMPT
You are a senior technical recruiter with 15+ years of experience hiring for top tech companies in India.
Your analysis must be precise, data-driven, and actionable.

## INPUTS
<candidate>
{$candidateProfile}
</candidate>

<job>
{$jobDescription}
</job>

## SCORING RUBRIC (Total: 100 points)
- Technical Skills Match (40pts): How well do their skills match the JD requirements?
- Experience Relevance (25pts): Is their experience level and domain relevant?
- Education & Certifications (10pts): Does their background meet the role expectations?
- Soft Skills & Culture Fit (15pts): Communication, leadership, problem-solving indicators?
- Keyword & ATS Match (10pts): How well does their profile match JD keywords?

## STRICT RULES
1. Return ONLY a single raw JSON object — no markdown, no explanation, no text before or after.
2. All string values must use escaped newlines (\n) not actual line breaks.
3. Do NOT truncate any field. Complete every array and every string fully.
4. The score field must equal the exact sum of all score_breakdown values.

## OUTPUT JSON STRUCTURE
{
  "score": 0,
  "score_breakdown": {
    "technical_skills": 0,
    "experience_relevance": 0,
    "education": 0,
    "soft_skills": 0,
    "keyword_match": 0
  },
  "verdict": "Strong Match | Good Match | Partial Match | Weak Match | Not Suitable",
  "feedback": "2-3 sentences: strongest alignment first, then biggest gap, then one actionable tip. Mention actual skill names.",
  "strengths": [
    "Specific strength 1 mapped to a JD requirement",
    "Specific strength 2",
    "Specific strength 3"
  ],
  "missing_keywords": [
    "Exact skill from JD not found in candidate profile"
  ],
  "cover_letter": "Dear Hiring Manager,\n\nParagraph 1: Strong interest in the role with a specific detail from the JD.\n\nParagraph 2: 2-3 experiences from the candidate that directly map to JD requirements with metrics if available.\n\nParagraph 3: Address one gap and how the candidate is bridging it. Confident call to action.\n\nSincerely,\n{$userName}",
  "interview_questions": [
    "Technical question on the most critical JD skill",
    "Technical question on the second most critical JD skill",
    "Situational: Tell me about a time you handled a key JD responsibility",
    "System design question relevant to the role level",
    "Question probing the candidate's biggest missing skill",
    "Behavioral: How do you handle a challenge relevant to the role",
    "Question about a specific tool or stack from the JD",
    "Question about the candidate's strongest skill to let them shine",
    "Culture or growth question relevant to the company type",
    "Where do you see yourself in 3 years, tailored to role seniority"
  ],
  "optimized_profile": "Paragraph 1: Current role and top 2-3 strengths matching the JD, first person, professional.\n\nParagraph 2: A specific achievement from their experience with qualitative or quantitative impact.\n\nParagraph 3: Forward-looking sentence connecting their growth to this role, weaving in 2-3 missing keywords naturally.",
  "salary_benchmark": {
    "min": 800000,
    "max": 1400000,
    "currency": "INR",
    "experience_level": "Mid-level",
    "advice": "Sentence 1: Justify the range based on their specific skills. Sentence 2: Name the one missing skill that would push them to the max."
  }
}
PROMPT;

        // 5. Route to correct AI provider based on user tier
        // Check both the is_pro flag AND that the 30-day pass has not expired
        $isPro = $user->is_pro
            && $user->pro_expires_at !== null
            && $user->pro_expires_at->isFuture();

        if ($isPro) {
            // ── PRO users: Gemini 2.5 Flash (premium quality) ──
            $rawText = $this->callGemini($systemInstruction, $prompt);

            // If Gemini fails, fall back to Groq so PRO users are never blocked
            if ($rawText === null) {
                Log::warning('Gemini failed for PRO user, falling back to Groq', ['user_id' => $user->id]);
                $rawText = $this->callGroq($systemInstruction, $prompt);
            }
        } else {
            // ── Free users: Groq + Llama 3.3 70B (fast & cheap) ──
            $rawText = $this->callGroq($systemInstruction, $prompt);
        }

        // 6. Handle total AI failure
        if ($rawText === null) {
            return response()->json([
                'success' => false,
                'message' => 'AI service is temporarily unavailable. Please try again in a moment.'
            ], 503);
        }

        // 7. Parse and save
        try {
            Log::info('AI Raw Response', [
                'provider' => $isPro ? 'gemini' : 'groq',
                'text'     => substr($rawText, 0, 500),
            ]);

            if (empty($rawText)) {
                throw new \Exception('Empty response from AI API');
            }

            // Strip markdown fences if model ignores the json_object format
            $cleanText = trim(preg_replace('/^```json|^```|```$/m', '', $rawText));

            $aiData = json_decode($cleanText, true);

            // Validate the decode worked and has the required field
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode failed: ' . json_last_error_msg() . ' | Raw: ' . substr($cleanText, 0, 500));
            }

            if (!isset($aiData['score'])) {
                throw new \Exception('Missing required score field. Got keys: ' . implode(', ', array_keys($aiData ?? [])));
            }

            // Ensure score matches breakdown sum (self-healing fallback)
            if (isset($aiData['score_breakdown'])) {
                $breakdown = $aiData['score_breakdown'];
                $computedScore = ($breakdown['technical_skills'] ?? 0)
                    + ($breakdown['experience_relevance'] ?? 0)
                    + ($breakdown['education'] ?? 0)
                    + ($breakdown['soft_skills'] ?? 0)
                    + ($breakdown['keyword_match'] ?? 0);
                // Use computed score if model made arithmetic error
                if (abs($aiData['score'] - $computedScore) > 2) {
                    $aiData['score'] = $computedScore;
                }
            }

            // Helper to safely encode — handles both arrays and already-encoded strings
            $safeJson = fn($val) => is_array($val) ? json_encode($val) : ($val ?? null);

            $match = JobMatch::create([
                'user_id'             => $user->id,
                'job_id'              => $job->id,
                'match_score'         => $aiData['score'],
                'ai_feedback'         => $aiData['feedback']          ?? 'Score calculated.',
                'missing_keywords'    => $safeJson($aiData['missing_keywords']    ?? null),
                'cover_letter'        => $aiData['cover_letter']      ?? null,
                'optimized_profile'   => $aiData['optimized_profile'] ?? null,
                'interview_questions' => $safeJson($aiData['interview_questions'] ?? null),
                'salary_benchmark'    => $safeJson($aiData['salary_benchmark']    ?? null),
                'score_breakdown'     => $safeJson($aiData['score_breakdown']     ?? null),
            ]);

            // Mark the first free analysis as used on successful AI completion
            if (!$isPro && $isFirstFree) {
                $user->is_first_analysis_free_used = true;
                $user->save();
            }

            return response()->json([
                'success'     => true,
                'data'        => $match,
                'ai_provider' => $isPro ? 'premium' : 'standard',
            ]);
        } catch (\Exception $e) {
            // Refund credit if it was deducted upfront but AI match failed
            if (isset($creditDeducted) && $creditDeducted) {
                $user->increment('ai_credits');
            }

            Log::error('AI Parsing Error', [
                'error'    => $e->getMessage(),
                'raw_text' => $rawText ?? 'not captured',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse AI response.',
                'debug'   => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /* ──────────────────────────────────────────────────────────────────
     * GEMINI 2.5 FLASH — Premium AI for PRO users
     * Uses Google's native Gemini REST API with JSON mode
     * Returns raw text or null on failure
     * ────────────────────────────────────────────────────────────── */
    private function callGemini(string $systemInstruction, string $prompt): ?string
    {
        try {
            $apiKey = config('services.gemini.key');

            if (empty($apiKey)) {
                Log::error('Gemini API key not configured');
                return null;
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$apiKey}";

            $response = Http::withoutVerifying()
                ->timeout(90) // Gemini can take a bit longer but delivers higher quality
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemInstruction]]
                    ],
                    'contents' => [
                        [
                            'role'  => 'user',
                            'parts' => [['text' => $prompt]]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json', // Native JSON mode — more reliable than json_object
                        'temperature'      => 0.3,
                        'maxOutputTokens'  => 8192, // Higher limit for richer cover letters and feedback
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 1000),
                ]);
                return null;
            }

            $rawText = $response->json('candidates.0.content.parts.0.text');

            if (empty($rawText)) {
                Log::error('Gemini returned empty content', [
                    'response' => substr($response->body(), 0, 500),
                ]);
                return null;
            }

            return $rawText;
        } catch (\Exception $e) {
            Log::error('Gemini Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /* ──────────────────────────────────────────────────────────────────
     * GROQ — Fast AI for Free users (also serves as PRO fallback)
     * Uses OpenAI-compatible API via Groq's LPU hardware
     * Returns raw text or null on failure
     * ────────────────────────────────────────────────────────────── */
    private function callGroq(string $systemInstruction, string $prompt): ?string
    {
        try {
            $apiKey = config('services.groq.key');

            if (empty($apiKey)) {
                Log::error('Groq API key not configured');
                return null;
            }

            $response = Http::withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(60)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'           => $this->groqModel,
                    'messages'        => [
                        [
                            'role'    => 'system',
                            'content' => $systemInstruction
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens'      => 4000,
                    'temperature'     => 0.3,
                ]);

            if ($response->failed()) {
                Log::error('Groq API Error', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 1000),
                ]);
                return null;
            }

            $rawText = $response->json('choices.0.message.content');

            if (empty($rawText)) {
                Log::error('Groq returned empty content');
                return null;
            }

            return $rawText;
        } catch (\Exception $e) {
            Log::error('Groq Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /* ──────────────────────────────────────────────────────────────────
     * RESUME HEALTH SCORE — Standalone ATS Readiness Analysis
     * No job description required. Scores resume across 6 dimensions.
     * Uses same AI routing: Gemini for PRO, Groq for Free.
     * ────────────────────────────────────────────────────────────── */
    public function resumeHealth(Request $request)
    {
        $user = $request->user();

        // 1. Ensure resume exists
        $resumeText = $user->parseResumeText();

        if (empty($resumeText) || strlen($resumeText) < 50) {
            return response()->json([
                'success' => false,
                'message' => 'No resume found or resume has too little readable text. Please upload a text-based PDF resume in your profile settings first.',
            ], 422);
        }

        // 2. Credit check (same logic as generateMatch)
        $isActivePro = $user->is_pro
            && $user->pro_expires_at !== null
            && $user->pro_expires_at->isFuture();

        $isFirstFree = !$user->is_first_resume_health_free_used;
        $creditDeducted = false;

        if (!$isActivePro) {
            if (!$isFirstFree) {
                if ($user->ai_credits <= 0) {
                    return response()->json([
                        'success'          => false,
                        'message'          => 'Out of credits',
                        'requires_upgrade' => true,
                    ], 402);
                }
                $user->decrement('ai_credits');
                $creditDeducted = true;
            }
        }

        // 3. Build candidate profile context
        $formatField = function ($field) {
            if (is_array($field)) return implode(', ', $field);
            return $field ?? 'Not provided';
        };

        $formatExperience = function ($exp) {
            if (empty($exp) || !is_array($exp)) return "Not provided";
            $formatted = [];
            foreach ($exp as $item) {
                $role = $item['role'] ?? 'Role';
                $company = $item['company'] ?? 'Company';
                $from = $item['from_year'] ?? '';
                $to = !empty($item['is_current']) ? 'Present' : ($item['to_year'] ?? 'N/A');
                $formatted[] = "- {$role} at {$company} ({$from} – {$to})";
            }
            return implode("\n", $formatted);
        };

        $formatEducation = function ($edu) {
            if (empty($edu) || !is_array($edu)) return "Not provided";
            $formatted = [];
            foreach ($edu as $item) {
                $degree = $item['degree'] ?? 'Degree';
                $institution = $item['institution'] ?? 'Institution';
                $year = $item['year'] ?? '';
                $formatted[] = "- {$degree}, {$institution} ({$year})";
            }
            return implode("\n", $formatted);
        };

        $profileContext = "Name: " . ($user->full_name ?? 'Candidate') .
            "\nEmail: " . ($user->email ?? 'Not provided') .
            "\nPhone: " . ($user->phone ?? 'Not provided') .
            "\nLocation: " . ($user->location ?? 'Not provided') .
            "\nSkills: " . $formatField($user->skills) .
            "\nBio: " . $formatField($user->bio) .
            "\n\nWork Experience:\n" . $formatExperience($user->work_experience) .
            "\n\nEducation:\n" . $formatEducation($user->education) .
            "\n\nFull Resume Text:\n" . $resumeText;

        // 4. Build the Resume Health prompt
        $systemInstruction = 'You are a precise JSON API. You only ever respond with a single valid raw JSON object. Never use markdown code blocks. Never add explanations.';

        $prompt = <<<PROMPT
You are a senior ATS (Applicant Tracking System) specialist and resume reviewer with 15+ years of experience.
You have deep knowledge of how automated resume scanners work at companies like Amazon, Google, Infosys, TCS, Razorpay, Flipkart, and other Indian and global tech companies.

Your task: Analyze this candidate's resume for ATS readiness — independent of any specific job description.
Rate it honestly. Do not be generous. A bad resume should score low. A good resume should score high.
Be specific in your feedback — mention actual words, sections, and patterns from the resume.

## CANDIDATE PROFILE + RESUME
<resume>
{$profileContext}
</resume>

## SCORING DIMENSIONS (each out of 10, total = sum of all 6)

### 1. ATS Parseability (0-10)
Can standard ATS software (Workday, Greenhouse, Lever, iCIMS, Taleo) parse this resume correctly?
Check for:
- Is it a text-based PDF (not a scanned image)?
- Does it avoid complex tables, columns, headers/footers, text boxes, graphics that break parsing?
- Are section headings standard and recognizable (e.g., "Experience", "Education", "Skills")?
- Does it use standard fonts and formatting?
Score 8-10 if cleanly parseable. Score 0-3 if it would break most parsers.

### 2. Contact Information (0-10)
- Is full name clearly visible at the top?
- Is a professional email address present?
- Is a phone number present?
- Is LinkedIn URL or portfolio/GitHub link present?
- Is location (city, state) mentioned?
Score 10 if all are present. Deduct 2 for each missing critical item.

### 3. Section Structure (0-10)
Does the resume have clearly labeled, ATS-recognizable sections?
Required: Summary/Objective, Work Experience, Education, Skills
Bonus: Projects, Certifications, Awards
Score 8-10 if all required sections exist with clear headings. Score low if sections are missing or ambiguously labeled.

### 4. Technical Keyword Density (0-10)
- Does the resume contain enough technical/role-specific keywords?
- Are skills mentioned in context (within experience descriptions), not just listed?
- Would ATS keyword-matching algorithms find relevant terms?
- Are both acronyms and full forms used (e.g., "Machine Learning (ML)")?
Score 8-10 if keyword-rich and contextual. Score low if skills are vague or absent.

### 5. Measurable Achievements (0-10)
- Does the resume include quantifiable results (numbers, percentages, metrics)?
- Examples: "Improved API response time by 40%", "Managed team of 8", "Reduced costs by 30%"
- Action verbs (Led, Built, Designed, Optimized, Automated) at the start of bullet points?
Score 8-10 if multiple achievements with metrics. Score 0-3 if all bullets are task descriptions with no impact.

### 6. Length & Formatting (0-10)
- Is the resume 1-2 pages (not too short, not too long)?
- Consistent formatting (fonts, bullet styles, date formats)?
- Clean whitespace and readability?
- No spelling or grammar errors visible?
- Professional tone throughout?
Score 8-10 if clean and professional. Score low if messy, too long, or inconsistent.

## STRICT OUTPUT RULES
1. Return ONLY a single raw JSON object — no markdown, no explanation, no text before or after.
2. All string values must use escaped newlines (\\n) not actual line breaks.
3. "overall_score" MUST equal the exact sum of all 6 dimension scores (check your arithmetic).
4. Each dimension score must be between 0 and 10 (integers only).
5. "status" must be exactly "good" (score >= 7), "warning" (score 4-6), or "poor" (score <= 3).
6. "top_fixes" must have exactly 5 items, ordered from highest impact to lowest.
7. Each feedback string must reference specific content from the actual resume — never be generic.

## OUTPUT JSON STRUCTURE
{
  "overall_score": 0,
  "dimensions": {
    "ats_parseability": {
      "score": 0,
      "max": 10,
      "status": "good|warning|poor",
      "feedback": "2-3 sentences explaining exactly what's right or wrong with parseability in THIS specific resume. Reference actual patterns observed."
    },
    "contact_info": {
      "score": 0,
      "max": 10,
      "status": "good|warning|poor",
      "feedback": "2-3 sentences. Name what's present and what's missing specifically."
    },
    "section_structure": {
      "score": 0,
      "max": 10,
      "status": "good|warning|poor",
      "feedback": "2-3 sentences. Name the sections found and which critical ones are missing."
    },
    "keyword_density": {
      "score": 0,
      "max": 10,
      "status": "good|warning|poor",
      "feedback": "2-3 sentences. Mention specific keywords found and suggest 3-5 keywords that should be added based on their skills and experience."
    },
    "achievements": {
      "score": 0,
      "max": 10,
      "status": "good|warning|poor",
      "feedback": "2-3 sentences. Quote any metrics found or explain why the bullets feel weak. Give 1-2 specific examples of how to rewrite a bullet with metrics."
    },
    "formatting": {
      "score": 0,
      "max": 10,
      "status": "good|warning|poor",
      "feedback": "2-3 sentences about length, consistency, and professional tone."
    }
  },
  "top_fixes": [
    "Most impactful fix with specific action to take",
    "Second most impactful fix",
    "Third fix",
    "Fourth fix",
    "Fifth fix"
  ],
  "summary": "3-4 sentence executive summary: what this resume does well, what's holding it back, and the single most important thing to fix right now."
}
PROMPT;

        // 5. Route to AI provider
        $isPro = $user->is_pro
            && $user->pro_expires_at !== null
            && $user->pro_expires_at->isFuture();

        if ($isPro) {
            $rawText = $this->callGemini($systemInstruction, $prompt);
            if ($rawText === null) {
                Log::warning('Gemini failed for resume-health PRO user, falling back to Groq', ['user_id' => $user->id]);
                $rawText = $this->callGroq($systemInstruction, $prompt);
            }
        } else {
            $rawText = $this->callGroq($systemInstruction, $prompt);
        }

        // 6. Handle total AI failure
        if ($rawText === null) {
            // Refund credit
            if ($creditDeducted) {
                $user->increment('ai_credits');
            }
            return response()->json([
                'success' => false,
                'message' => 'AI service is temporarily unavailable. Please try again in a moment.',
            ], 503);
        }

        // 7. Parse response
        try {
            Log::info('Resume Health Raw Response', [
                'provider' => $isPro ? 'gemini' : 'groq',
                'text'     => substr($rawText, 0, 500),
            ]);

            if (empty($rawText)) {
                throw new \Exception('Empty response from AI API');
            }

            $cleanText = trim(preg_replace('/^```json|^```|```$/m', '', $rawText));
            $aiData = json_decode($cleanText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode failed: ' . json_last_error_msg());
            }

            if (!isset($aiData['overall_score']) || !isset($aiData['dimensions'])) {
                throw new \Exception('Missing required fields. Got keys: ' . implode(', ', array_keys($aiData ?? [])));
            }

            // Self-heal: recalculate overall_score from dimensions
            $dims = $aiData['dimensions'];
            $computedScore = 0;
            foreach ($dims as $key => $dim) {
                $s = (int) ($dim['score'] ?? 0);
                $computedScore += $s;

                // Auto-fix status if model got it wrong
                if ($s >= 7) {
                    $aiData['dimensions'][$key]['status'] = 'good';
                } elseif ($s >= 4) {
                    $aiData['dimensions'][$key]['status'] = 'warning';
                } else {
                    $aiData['dimensions'][$key]['status'] = 'poor';
                }

                // Ensure max is always 10
                $aiData['dimensions'][$key]['max'] = 10;
            }

            // Fix arithmetic if model made errors
            if (abs(($aiData['overall_score'] ?? 0) - $computedScore) > 2) {
                $aiData['overall_score'] = $computedScore;
            }

            // Mark first free analysis as used
            if (!$isActivePro && $isFirstFree) {
                $user->is_first_resume_health_free_used = true;
                $user->save();
            }

            return response()->json([
                'success'     => true,
                'data'        => $aiData,
                'ai_provider' => $isPro ? 'premium' : 'standard',
            ]);
        } catch (\Exception $e) {
            // Refund credit on parse failure
            if ($creditDeducted) {
                $user->increment('ai_credits');
            }

            Log::error('Resume Health Parse Error', [
                'error'    => $e->getMessage(),
                'raw_text' => $rawText ?? 'not captured',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze resume. Please try again.',
                'debug'   => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function myMatches(Request $request)
    {
        $user = $request->user();
        
        $matches = JobMatch::with('job:id,title,role,location,image')
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'job' => $match->job,
                    'match_score' => $match->match_score,
                    'ai_feedback' => $match->ai_feedback,
                    'updated_at' => $match->updated_at,
                ];
            });

        return response()->json(['success' => true, 'data' => $matches]);
    }
}
