<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiParserService
{
    /**
     * Parses raw HTML job description into distinct fields using Gemini 1.5 Flash.
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

        $prompt = "You are an expert HR data extractor. Extract the following from the job description text below.
Constraints:
- Return ONLY valid JSON matching the schema exactly.
- NO HTML tags (no <ul>, <li>, <strong>, etc). Use simple, plain English text.
- The TOTAL length of all combined text in your response MUST be around 300 words or less. Keep it very concise.
- If a section is not present in the text, leave it as an empty string.

JSON Schema:
{
    \"rolesAndResponsibilities\": \"string (Plain English list of responsibilities. You MUST separate each distinct point with a newline character '\\n' so it can be formatted as bullet points)\",
    \"requirements\": \"string (Plain English list of mandatory requirements. You MUST separate each distinct point with a newline character '\\n' so it can be formatted as bullet points)\",
    \"niceToHave\": \"string (Plain English list of preferred/bonus qualifications. You MUST separate each distinct point with a newline character '\\n' so it can be formatted as bullet points)\",
    \"eligibility\": \"string (e.g. B.Tech/M.Tech/MCA or 2023/2024 passout if mentioned, else empty)\",
    \"expectedSalary\": \"string (Expected salary range in INR LPA if mentioned, else empty)\",
    \"jobRoleCategory\": \"string (Classify strictly as: fresher, intern, or experienced)\",
    \"eligibleBatches\": \"string (Batches Description String e.g. 2024, 2025, 2026, 2027 passout batches)\",
    \"jobDescription\": \"string (A short, concise summary about the company and the overall role)\",
    \"seoTitle\": \"string (Best SEO-optimized job title for this job)\"
}

JOB TEXT:
" . strip_tags($htmlContent);

        $url = "https://api.groq.com/openai/v1/chat/completions";

        $payload = [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that outputs only valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object']
        ];

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
            } else {
                Log::error('Groq API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Groq API Exception: ' . $e->getMessage());
        }

        return null;
    }
}
