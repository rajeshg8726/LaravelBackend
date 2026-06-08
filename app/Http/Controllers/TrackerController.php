<?php

namespace App\Http\Controllers;

use App\Models\ApplicationTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrackerController extends Controller
{
    private string $groqModel   = 'llama-3.3-70b-versatile';
    private string $geminiModel = 'gemini-2.5-flash';

    public function index(Request $request)
    {
        $user = $request->user();
        $applications = ApplicationTracker::where('user_id', $user->id)
            ->orderBy('applied_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $applications,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Credit / application limit check for non-PRO users (Free max 5, Top-up max 10)
        $isPro = $user->is_pro
            && $user->pro_expires_at !== null
            && $user->pro_expires_at->isFuture();

        if (!$isPro) {
            // Check if they ever purchased a TOPUP package
            $hasTopUp = \App\Models\Transaction::where('user_id', $user->id)
                ->where('plan_type', 'TOPUP')
                ->where('status', 'SUCCESS')
                ->exists();

            $limit = $hasTopUp ? 10 : 5;
            $count = ApplicationTracker::where('user_id', $user->id)->count();

            if ($count >= $limit) {
                $tierName = $hasTopUp ? 'Top-up Pack' : 'Free Plan';
                $nextStep = $hasTopUp ? 'PRO plan' : 'Top-up Pack or PRO plan';
                return response()->json([
                    'success'          => false,
                    'message'          => "Your {$tierName} is limited to tracking {$limit} active applications. Please upgrade to {$nextStep} to track more active applications and get premium AI follow-ups.",
                    'requires_upgrade' => true
                ], 402);
            }
        }

        $validated = $request->validate([
            'company_name'    => 'required|string|max:255',
            'job_title'       => 'required|string|max:255',
            'job_description' => 'nullable|string',
            'job_url'         => 'nullable|string|url',
            'status'          => 'nullable|in:applied,interviewing,offered,rejected',
            'notes'           => 'nullable|string',
            'applied_at'      => 'nullable|date',
        ]);

        $app = ApplicationTracker::create([
            'user_id'         => $user->id,
            'company_name'    => $validated['company_name'],
            'job_title'       => $validated['job_title'],
            'job_description' => $validated['job_description'] ?? null,
            'job_url'         => $validated['job_url'] ?? null,
            'status'          => $validated['status'] ?? 'applied',
            'notes'           => $validated['notes'] ?? null,
            'applied_at'      => $validated['applied_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $app,
            'message' => 'Application tracked successfully.'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $app = ApplicationTracker::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'company_name'    => 'sometimes|required|string|max:255',
            'job_title'       => 'sometimes|required|string|max:255',
            'job_description' => 'nullable|string',
            'job_url'         => 'nullable|string|url|max:255',
            'status'          => 'sometimes|required|in:applied,interviewing,offered,rejected',
            'notes'           => 'nullable|string',
            'applied_at'      => 'nullable|date',
        ]);

        $app->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $app,
            'message' => 'Application updated successfully.'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $app = ApplicationTracker::where('user_id', $user->id)->findOrFail($id);
        $app->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application deleted from tracker.'
        ]);
    }

    /* ─── FLAGSHIP AI INSIGHT ENGINE ─── */
    public function getAiInsights(Request $request, $id)
    {
        $user = $request->user();
        $app = ApplicationTracker::where('user_id', $user->id)->findOrFail($id);

        // 1. Check if user has uploaded a resume
        $resumeText = $user->parseResumeText();

        // 2. Build candidate context
        $formatField = function ($field) {
            if (is_array($field)) return implode(', ', $field);
            return $field ?? 'Not provided';
        };

        $candidateProfile = "Name: " . ($user->full_name ?? 'Candidate') .
            "\nSkills: " . $formatField($user->skills) .
            "\nBio: " . $formatField($user->bio);

        if (!empty($resumeText)) {
            $candidateProfile .= "\n\nExtracted Resume Text Content:\n" . $resumeText;
        }

        // 2b. Check caching layer to protect against redundant expensive AI API calls
        $cacheKey = "tracker_insights_{$app->id}_status_{$app->status}" .
            "_co_" . md5($app->company_name) .
            "_title_" . md5($app->job_title) .
            "_resume_" . md5($resumeText ?? '') .
            "_jd_" . md5($app->job_description ?? '');

        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $cachedData = \Illuminate\Support\Facades\Cache::get($cacheKey);
            return response()->json([
                'success'     => true,
                'data'        => $cachedData['data'],
                'ai_provider' => $cachedData['ai_provider'] ?? 'cached',
                'cached'      => true
            ]);
        }

        $systemInstruction = "You are a precise JSON API. You only ever respond with a single valid raw JSON object. Never use markdown code blocks. Never add explanations.";

        // 3. Tailor Prompt based on current pipeline status
        if ($app->status === 'applied') {
            $prompt = <<<PROMPT
You are a senior technical recruiter. The candidate has applied for:
Company: {$app->company_name}
Role: {$app->job_title}

Candidate Profile:
{$candidateProfile}

Generate a highly professional Recruiter Cold Outreach campaign containing:
1. A brief LinkedIn Connection request note (strictly under 300 characters, highly professional).
2. A formal Cold Follow-up Email (subject line, professional greeting, 2 paragraphs showcasing the candidate's top skill relevance, and call to action).

STRICT RULES:
- Do NOT include any intro or outro text.
- Do NOT wrap in ```json block.
- Return EXACTLY this JSON structure:
{
  "linkedin_note": "Your connection note under 300 characters...",
  "cold_email_subject": "SDE Follow-up...",
  "cold_email_body": "Dear Recruiting Team,\\n\\n..."
}
PROMPT;
        } elseif ($app->status === 'interviewing') {
            $prompt = <<<PROMPT
You are a lead software engineer at a top tech firm. The candidate is interviewing at:
Company: {$app->company_name}
Role: {$app->job_title}

Candidate Profile:
{$candidateProfile}

Generate exactly 5 company-specific and role-relevant mock technical preparation questions. For each question, provide a short, actionable tip on how the candidate should answer based on their resume experience.

STRICT RULES:
- Return EXACTLY this JSON structure:
{
  "questions": [
    {
      "question": "Question 1",
      "tip": "Tip on how to answer..."
    },
    ...
  ]
}
PROMPT;
        } elseif ($app->status === 'rejected') {
            if (empty($app->job_description) || strlen($app->job_description) < 40) {
                return response()->json([
                    'success' => true,
                    'data'    => [
                        'error_message' => 'To run a detailed keyword and skill gap Rejection Diagnosis, please click edit on this application and paste the job description text first!'
                    ]
                ]);
            }

            $prompt = <<<PROMPT
You are an expert ATS advisor and resume reviewer. The candidate was rejected for:
Company: {$app->company_name}
Role: {$app->job_title}
Job Description:
{$app->job_description}

Candidate Profile:
{$candidateProfile}

Analyze the candidate's resume against the Job Description. Identify exactly:
1. Top 4 critical keywords/skills that are present in the JD but missing or weak in the candidate's resume.
2. A 3-4 sentence encouraging, constructive diagnostics summary of why they got rejected and what they should fix next time.

STRICT RULES:
- Return EXACTLY this JSON structure:
{
  "missing_keywords": ["Keyword 1", "Keyword 2", "Keyword 3", "Keyword 4"],
  "diagnosis_summary": "Constructive diagnostics summary..."
}
PROMPT;
        } else {
            // Offered
            return response()->json([
                'success' => true,
                'data'    => [
                    'message' => 'Congratulations on your job offer at ' . $app->company_name . '! You have unlocked the ultimate milestone. Focus on salary benchmarks and negotiation tips next!'
                ]
            ]);
        }

        // 4. Route to proper AI model
        $isPro = $user->is_pro
            && $user->pro_expires_at !== null
            && $user->pro_expires_at->isFuture();

        if ($isPro) {
            $rawText = $this->callGemini($systemInstruction, $prompt);
            if ($rawText === null) {
                $rawText = $this->callGroq($systemInstruction, $prompt);
            }
        } else {
            $rawText = $this->callGroq($systemInstruction, $prompt);
        }

        if ($rawText === null) {
            return response()->json([
                'success' => false,
                'message' => 'AI insights engine is temporarily offline. Please try again in a moment.'
            ], 503);
        }

        // 5. Parse and return
        try {
            $cleanText = trim(preg_replace('/^```json|^```|```$/m', '', $rawText));
            $aiData = json_decode($cleanText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON parse error: ' . json_last_error_msg());
            }

            // Save inside persistent cache for 30 days to limit API costs
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'data'        => $aiData,
                'ai_provider' => $isPro ? 'premium' : 'standard'
            ], now()->addDays(30));

            return response()->json([
                'success'     => true,
                'data'        => $aiData,
                'ai_provider' => $isPro ? 'premium' : 'standard',
            ]);
        } catch (\Exception $e) {
            Log::error('Tracker AI Parsing Error', [
                'error'    => $e->getMessage(),
                'raw_text' => $rawText,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse AI coaching feedback.',
                'debug'   => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function callGemini(string $systemInstruction, string $prompt): ?string
    {
        try {
            $apiKey = config('services.gemini.key');
            if (empty($apiKey)) return null;

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$apiKey}";

            $response = Http::withoutVerifying()
                ->timeout(60)
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
                        'responseMimeType' => 'application/json',
                        'temperature'      => 0.3,
                        'maxOutputTokens'  => 4000,
                    ],
                ]);

            if ($response->failed()) return null;
            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Exception $e) {
            Log::error('Tracker Gemini Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function callGroq(string $systemInstruction, string $prompt): ?string
    {
        try {
            $apiKey = config('services.groq.key');
            if (empty($apiKey)) return null;

            $response = Http::withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(45)
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
                    'max_tokens'      => 2000,
                    'temperature'     => 0.3,
                ]);

            if ($response->failed()) return null;
            return $response->json('choices.0.message.content');
        } catch (\Exception $e) {
            Log::error('Tracker Groq Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
