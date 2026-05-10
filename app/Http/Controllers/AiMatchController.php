<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\JobMatch;
use App\Models\Jobs;
use Illuminate\Support\Facades\Log;

class AiMatchController extends Controller
{
    public function generateMatch(Request $request)
    {
        $request->validate(['job_id' => 'required|integer']);
        $user = $request->user();
        $jobId = $request->job_id;
        // 1. Check if they already matched this job (don't charge credits if they already did it!)
        $existingMatch = JobMatch::where('user_id', $user->id)->where('job_id', $jobId)->first();
        if ($existingMatch) {
            return response()->json(['success' => true, 'data' => $existingMatch]);
        }
        // 2. CHECK CREDITS: If they aren't PRO and have 0 credits, block them.
        if (!$user->is_pro && $user->ai_credits <= 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Out of credits', 
                'requires_upgrade' => true // The Next.js frontend looks for this exact flag!
            ], 402); // 402 Payment Required
        }

        $job = Jobs::findOrFail($jobId);

        $formatField = function ($field) {
            if (is_array($field)) return implode(', ', $field);
            return $field ?? 'Not provided';
        };

        $candidateProfile = "Skills: " . $formatField($user->skills) .
                            "\nExperience: " . $formatField($user->experience) .
                            "\nBio: " . $formatField($user->bio);

        $jobDescription = "Title: " . $job->title .
                          "\nRequirements: " . $formatField($job->requirements) .
                          "\nDescription: " . $formatField($job->description);

        // 1. The Ultra-Premium Prompt
        $prompt = "You are an expert technical recruiter and career coach. Analyze this Candidate Profile against the Job Description. 
        
        Return ONLY valid JSON in this EXACT format with no markdown blocks:
        {
          \"score\": 85,
          \"feedback\": \"You have great React skills, but are missing cloud experience.\",
          \"missing_keywords\": [\"Docker\", \"AWS\", \"GraphQL\"],
          \"cover_letter\": \"Dear Hiring Manager,\\n\\nI am writing to apply for the position... (write a highly tailored 3-paragraph cover letter based specifically on the candidate's skills that match the job)\"
        }
        
        Candidate: {$candidateProfile} 
        Job: {$jobDescription}";

        // Groq API Call

        $apiKey = env('GROQ_API_KEY');
        
        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->timeout(30)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'response_format' => ['type' => 'json_object']
            ]);

        if ($response->failed()) {
            Log::error('Groq API Error', ['body' => $response->body()]);
            return response()->json(['success' => false, 'message' => 'AI service error. Please try again later.'], 500);
        }

        try {
            $rawText = $response->json('choices.0.message.content');
            $cleanText = trim(str_replace(['```json', '```'], '', $rawText));
            $aiData = json_decode($cleanText, true);
            $match = JobMatch::create([
                'user_id'          => $user->id,
                'job_id'           => $job->id,
                'match_score'      => $aiData['score'] ?? 50,
                'ai_feedback'      => $aiData['feedback'] ?? 'Score calculated successfully.',
                'missing_keywords' => isset($aiData['missing_keywords']) ? json_encode($aiData['missing_keywords']) : null,
                'cover_letter'     => $aiData['cover_letter'] ?? null
            ]);
            // Deduct Credit (If you added the credit system earlier)
            if (!$user->is_pro) {
                $user->decrement('ai_credits');
            }
            return response()->json(['success' => true, 'data' => $match]);
        } catch (\Exception $e) {
            \Log::error('AI Parsing Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to parse AI response.'], 500);
        }
    }
}
