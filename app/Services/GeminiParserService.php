<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiParserService
{
    /**
     * Parses raw HTML job description using Gemini (primary) + Groq (fallback).
     * Never returns "Not specified" — always generates realistic and accurate content.
     */
    public function parseJobDescription(string $htmlContent, string $jobTitle = '', string $location = ''): ?array
    {
        // -- Clean HTML ------------------------------------------------
        $cleanText = strip_tags($htmlContent);
        $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        $cleanText = trim($cleanText);
        if (mb_strlen($cleanText) > 2500) {
            $cleanText = mb_substr($cleanText, 0, 2500) . '...';
        }

        $prompt = $this->buildPrompt($cleanText, $jobTitle, $location);

        // -- Strategy 1: Google Gemini API (gemini-2.5-flash) --
        $result = $this->callGemini($prompt);
        if ($result !== null) {
            Log::info("Gemini parsed successfully: {$jobTitle}");
            return $this->sanitizeResult($result, $jobTitle, $location);
        }

        // -- Strategy 2: Groq API (openai/gpt-oss-120b) --
        Log::warning("Gemini failed for '{$jobTitle}', trying Groq...");
        $result = $this->callGroq($prompt);
        if ($result !== null) {
            Log::info("Groq parsed successfully: {$jobTitle}");
            return $this->sanitizeResult($result, $jobTitle, $location);
        }

        Log::error("Both Gemini and Groq failed for: {$jobTitle}");
        return null;
    }

    /**
     * Build the extraction prompt with strong "no empty fields" instruction.
     */
    private function buildPrompt(string $cleanText, string $jobTitle, string $location): string
    {
        return <<<PROMPT
You are an expert Indian tech recruiter. Extract and GENERATE job listing data from the text below.

CRITICAL RULES:
1. Return ONLY a valid JSON object. No markdown code blocks, no explanation.
2. EVERY field MUST have meaningful content. NEVER use "Not specified", "N/A", "None", or empty strings.
3. If information is not explicitly in the text, GENERATE realistic and relevant content based on the job title "{$jobTitle}", location "{$location}", and Indian tech industry standards.
4. For estimatedPayRange: ALWAYS provide a realistic INR LPA estimate even if salary is not mentioned (e.g. 4-8 LPA, 6-12 LPA). Use Indian market standards.
5. Use \\n to separate bullet points within text fields.

REQUIRED JSON FIELDS:
{
  "rolesAndResponsibilities": "3-5 key responsibilities based on the role, separated by \\n",
  "requirements": "3-5 mandatory skills/qualifications, separated by \\n",
  "niceToHave": "2-3 preferred/bonus qualifications, separated by \\n",
  "eligibility": "Degree requirements and passout years e.g. B.Tech/M.Tech/MCA/BCA, 2023-2026 passout",
  "estimatedPayRange": "Salary range in INR LPA e.g. 4-8 LPA",
  "jobRoleCategory": "EXACTLY one of: fresher, intern, experienced",
  "eligibleBatches": "Graduation years e.g. 2023, 2024, 2025, 2026",
  "jobDescription": "60-80 word professional summary about the company and role",
  "seoTitle": "Company name ONLY e.g. Google, Flipkart, TCS, InMobi",
  "companyType": "EXACTLY one of: Product-Based, MNC, Service-Based, Startup, Gaming Studio"
}

JOB TEXT:
{$cleanText}
PROMPT;
    }

    /**
     * Call Google Gemini API (gemini-2.5-flash — ultra-fast & high quality).
     */
    private function callGemini(string $prompt): ?array
    {
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            Log::error('Gemini API key not configured');
            return null;
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

            $response = Http::withoutVerifying()
                ->timeout(40)
                ->post($url, [
                    'systemInstruction' => [
                        'parts' => [['text' => 'You are a JSON-only data extractor for Indian tech jobs. Output ONLY valid JSON. No markdown, no explanations.']]
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [['text' => $prompt]]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.1,
                        'maxOutputTokens' => 2000,
                    ],
                ]);

            if ($response->successful()) {
                $rawText = $response->json('candidates.0.content.parts.0.text');
                if (!empty($rawText)) {
                    $parsed = $this->extractJson($rawText);
                    if ($parsed !== null) return $parsed;
                }
            }

            Log::warning('Gemini API Error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);
        } catch (\Exception $e) {
            Log::error('Gemini Exception', ['msg' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Call Groq API (fallback — openai/gpt-oss-120b).
     */
    private function callGroq(string $prompt): ?array
    {
        $apiKey = config('services.groq.key') ?? env('GROQ_API_KEY');
        if (empty($apiKey)) return null;

        // Try primary Groq model first, then secondary
        $models = ['openai/gpt-oss-120b', 'openai/gpt-oss-20b'];

        foreach ($models as $model) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(25)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a JSON-only extractor for Indian tech jobs. Return ONLY valid JSON.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 2000,
                    'response_format' => ['type' => 'json_object']
                ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content') ?? '';
                    if (!empty($content)) {
                        $parsed = $this->extractJson($content);
                        if ($parsed !== null) return $parsed;
                    }
                }

                Log::warning("Groq API issue with model {$model}", [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 200),
                ]);
            } catch (\Exception $e) {
                Log::error("Groq Exception with model {$model}", ['msg' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Robust JSON extractor that handles direct JSON, code fences, and substring matches.
     */
    private function extractJson(string $text): ?array
    {
        // 1. Direct JSON decode
        $parsed = json_decode($text, true);
        if (is_array($parsed) && !empty($parsed)) return $parsed;

        // 2. Strip Markdown code blocks (```json ... ```)
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $parsed = json_decode($matches[1], true);
            if (is_array($parsed) && !empty($parsed)) return $parsed;
        }

        // 3. Match outer { ... }
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $parsed = json_decode($matches[0], true);
            if (is_array($parsed) && !empty($parsed)) return $parsed;
        }

        return null;
    }

    /**
     * Sanitize parsed result — replace any remaining placeholders with rich defaults.
     */
    private function sanitizeResult(array $result, string $jobTitle, string $location): array
    {
        $placeholders = ['not specified', 'n/a', 'none', 'not mentioned', 'not available', 'not provided', '', 'null'];

        foreach ($result as $key => $value) {
            if (is_string($value) && in_array(strtolower(trim($value)), $placeholders)) {
                $result[$key] = $this->generateFallback($key, $jobTitle, $location);
            }
        }

        // Ensure critical fields exist
        $defaults = [
            'rolesAndResponsibilities' => "Design and develop software solutions for {$jobTitle}\nWrite clean, maintainable, and efficient code\nCollaborate with cross-functional product and engineering teams\nParticipate in code reviews and architectural discussions",
            'requirements' => "B.Tech/B.E./MCA in Computer Science, IT, or related technical field\nStrong problem-solving and analytical abilities\nSolid foundation in data structures, algorithms, and software design\nGood communication and teamwork skills",
            'niceToHave' => "Experience with cloud platforms (AWS, GCP, Azure)\nFamiliarity with CI/CD and containerization tools (Docker, Kubernetes)\nActive GitHub profile or personal projects",
            'eligibility' => 'B.Tech / B.E. / MCA / M.Tech, 2023 - 2026 batches',
            'estimatedPayRange' => '5-10 LPA',
            'jobRoleCategory' => 'fresher',
            'eligibleBatches' => '2023, 2024, 2025, 2026',
            'jobDescription' => "Exciting opportunity for {$jobTitle} in {$location}. Join a forward-thinking engineering team building scalable, high-performance applications.",
            'seoTitle' => explode(' ', $jobTitle)[0] ?? 'Tech Company',
            'companyType' => 'Product-Based',
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($result[$key]) || empty(trim((string)$result[$key]))) {
                $result[$key] = $default;
            }
        }

        return $result;
    }

    /**
     * Generate intelligent fallback content for a specific field.
     */
    private function generateFallback(string $field, string $jobTitle, string $location): string
    {
        return match ($field) {
            'rolesAndResponsibilities' => "Develop and maintain robust features for {$jobTitle}\nWrite well-tested, clean code adhering to best industry practices\nWork with QA and DevOps to ensure seamless deployments\nTroubleshoot and resolve production issues promptly",
            'requirements' => "Bachelor's/Master's degree in Computer Science, Engineering, or related field\nProficiency in modern programming languages\nUnderstanding of database design and RESTful APIs\nStrong debugging and problem-solving skills",
            'niceToHave' => "Prior internship or hands-on project experience\nKnowledge of modern frameworks and tools\nContributions to open-source software",
            'eligibility' => 'B.Tech / M.Tech / MCA / BCA, 2023 - 2026 passouts',
            'estimatedPayRange' => '4-9 LPA',
            'jobRoleCategory' => 'fresher',
            'eligibleBatches' => '2023, 2024, 2025, 2026',
            'jobDescription' => "Join as {$jobTitle} in {$location}. Be part of a dynamic team driving innovation and engineering excellence.",
            'seoTitle' => explode(' ', $jobTitle)[0] ?? 'Tech Company',
            'companyType' => 'Product-Based',
            default => 'Available on company careers page',
        };
    }
}
