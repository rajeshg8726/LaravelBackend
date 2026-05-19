<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\JobMatch;
use App\Models\Jobs;
use Illuminate\Support\Facades\Log;

class AiMatchController  extends Controller
{
    // ✅ Use a larger model — 8b is too small for complex structured JSON
    private string $model = 'llama-3.3-70b-versatile';

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

        // 2. Check credits
        if (!$user->is_pro && $user->ai_credits <= 0) {
            return response()->json([
                'success'          => false,
                'message'          => 'Out of credits',
                'requires_upgrade' => true
            ], 402);
        }

        // 3. Load job and build profile strings
        $job = Jobs::findOrFail($jobId);

        $formatField = function ($field) {
            if (is_array($field)) return implode(', ', $field);
            return $field ?? 'Not provided';
        };

        $candidateProfile = "Skills: "    . $formatField($user->skills)     .
            "\nBio: "        . $formatField($user->bio);

        $jobDescription   = "Title: "        . $job->title                      .
            "\nRequirements: " . $formatField($job->requirements) .
            "\nDescription: "  . $formatField($job->description);

        $userName = $user->full_name ?? 'Candidate';

        // 4. Prompt
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

        // 5. Groq API call
        $apiKey = config('services.groq.key');


        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->timeout(60) // ✅ Increased — 70b model needs more time
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'           => $this->model,
                'messages'        => [
                    // ✅ Split into system + user roles for better instruction following
                    [
                        'role'    => 'system',
                        'content' => 'You are a precise JSON API. You only ever respond with a single valid raw JSON object. Never use markdown code blocks. Never add explanations.'
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens'      => 4000, // ✅ Critical — was missing before
                'temperature'     => 0.3,  // ✅ Lower = more consistent structured output
            ]);

        // 6. Handle API-level failure
        if ($response->failed()) {
            Log::error('Groq API Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'AI service error: ' . $response->status()
            ], 500);
        }

        // 7. Parse and save
        try {
            $rawText = $response->json('choices.0.message.content');

            // Log the raw response for debugging during development
            Log::info('Groq Raw Response', ['text' => $rawText]);

            if (empty($rawText)) {
                throw new \Exception('Empty response from Groq API');
            }

            // Strip markdown fences if model ignores the json_object format
            $cleanText = trim(preg_replace('/^```json|^```|```$/m', '', $rawText));

            $aiData = json_decode($cleanText, true);

            // ✅ Validate the decode worked and has the required field
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode failed: ' . json_last_error_msg() . ' | Raw: ' . substr($cleanText, 0, 500));
            }

            if (!isset($aiData['score'])) {
                throw new \Exception('Missing required score field. Got keys: ' . implode(', ', array_keys($aiData ?? [])));
            }

            // ✅ Ensure score matches breakdown sum (self-healing fallback)
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

            // ✅ Helper to safely encode — handles both arrays and already-encoded strings
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
            ]);

            // Deduct credit for free users
            if (!$user->is_pro) {
                $user->decrement('ai_credits');
            }

            return response()->json(['success' => true, 'data' => $match]);
        } catch (\Exception $e) {
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

    public function myMatches(Request $request)
    {
        $user = $request->user();
        
        $matches = JobMatch::with('job:id,title,role,location,image')
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc') // Best to show recently updated/analyzed first
            ->get()
            ->map(function ($match) {
                // Parse strings back to JSON if needed for frontend mapping, though Laravel 
                // cast could do this. Since they are stored as JSON strings:
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
