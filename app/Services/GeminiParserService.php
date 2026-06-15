<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiParserService
{
    /**
     * Parses raw HTML job description into distinct fields using Groq LLM.
     * 
     * @param string $htmlContent
     * @return array|null
     */
    public function parseJobDescription(string $htmlContent)
    {
        $apiKey = env('GROQ_API_KEY');
        if (!$apiKey) {
            Log::error('Groq API key is missing.');
            return null;
        }

        // ── Aggressive HTML Cleaning ──────────────────────────────────
        // 1. Strip all HTML tags
        $cleanText = strip_tags($htmlContent);
        // 2. Decode HTML entities (&amp; &nbsp; etc.)
        $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // 3. Collapse all whitespace (multiple spaces, newlines, tabs → single space)
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        // 4. Trim leading/trailing whitespace
        $cleanText = trim($cleanText);
        // 5. Truncate to ~2000 characters to stay within token limits
        if (mb_strlen($cleanText) > 2000) {
            $cleanText = mb_substr($cleanText, 0, 2000) . '...';
        }

        $prompt = "You are an expert HR data extractor for the Indian tech job market. Extract the following from the job description text below.

STRICT RULES:
- Return ONLY valid JSON matching the schema exactly.
- NO HTML tags anywhere. Use plain English text only.
- The TOTAL length of all combined text in your response MUST be under 300 words. Be concise.
- Separate list items with newline characters '\\n' for bullet point formatting.
- If a field is not mentioned in the text, re-write roles/responsibilities and requirements based on the job description context. NEVER leave any field empty or null. 
- For estimatedPayRange: ALWAYS provide a realistic INR LPA range based on the role, company reputation, and Indian tech market standards. Example: '4-8 LPA' for a fresher SDE at a mid-tier company, '12-20 LPA' for a fresher at a top product company.
- For companyType: Classify based on the company name and role context.

JSON Schema:
{
    \"rolesAndResponsibilities\": \"string (Plain text list, each point separated by \\n)\",
    \"requirements\": \"string (Plain text list of mandatory requirements, each point separated by \\n)\",
    \"niceToHave\": \"string (Preferred/bonus qualifications, each point separated by \\n)\",
    \"eligibility\": \"string (e.g. B.Tech/M.Tech/MCA, 2023-2025 passout, etc.)\",
    \"estimatedPayRange\": \"string (Estimated salary range in INR LPA based on Indian market. ALWAYS provide an estimate even if not in JD. Format: 'X-Y LPA')\",
    \"jobRoleCategory\": \"string (Classify strictly as: fresher, intern, or experienced)\",
    \"eligibleBatches\": \"string (e.g. 2023, 2024, 2025 passout batches)\",
    \"jobDescription\": \"string (50-80 word concise summary about the company and role)\",
    \"seoTitle\": \"string (Company name only, e.g. 'Google', 'Flipkart', 'Razorpay')\",
    \"companyType\": \"string (Classify as: Product-Based, MNC, Service-Based, Startup, or Gaming Studio)\"
}

JOB TEXT:
" . $cleanText;

        $url = "https://api.groq.com/openai/v1/chat/completions";

        $payload = [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that outputs only valid JSON. Never include HTML tags or markdown formatting in your response.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'max_tokens' => 800,
            'response_format' => ['type' => 'json_object']
        ];

        // ── Retry Logic with Exponential Backoff ──────────────────────
        $maxRetries = 2;
        $retryDelay = 30; // seconds

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['choices'][0]['message']['content'])) {
                        $jsonString = $data['choices'][0]['message']['content'];
                        return json_decode($jsonString, true);
                    }
                }

                // Handle rate limiting (429)
                $statusCode = $response->status();
                if ($statusCode === 429 && $attempt < $maxRetries) {
                    Log::warning("Groq API rate limited (429). Retry attempt " . ($attempt + 1) . " after {$retryDelay}s...");
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff: 30s → 60s
                    continue;
                }

                Log::error('Groq API Error', [
                    'status' => $statusCode,
                    'body' => mb_substr($response->body(), 0, 500),
                    'attempt' => $attempt + 1,
                ]);

                // Non-429 errors: don't retry
                if ($statusCode !== 429) {
                    return null;
                }

            } catch (\Exception $e) {
                Log::error('Groq API Exception', [
                    'message' => $e->getMessage(),
                    'attempt' => $attempt + 1,
                ]);

                // On connection errors, retry with backoff
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                }
            }
        }

        return null;
    }
}
