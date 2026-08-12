<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Support\SiteSettings;

class SchoolSeoService
{
    public function __construct(private SiteSettings $siteSettings)
    {
    }

    public function generateSeoData(string $pageName, string $dynamicContext = ''): array
    {
        $apiKey = config('services.openai.key');

        // 1. Check if key even exists
        if (empty($apiKey)) {
            throw new \Exception("OpenAI API Key is missing. Please add OPENAI_API_KEY to your .env file.");
        }

        $formattedPageName = ucwords(str_replace('_', ' ', $pageName));
        $schoolName = $this->siteSettings->localized('site_name', config('app.name'));
        $schoolAddress = $this->siteSettings->localized('site_address', __('site.location'));
        $schoolRegion = $this->siteSettings->get('school_region');
        $schoolLocality = $this->siteSettings->get('school_locality');
        $schoolKeywords = $this->siteSettings->localized('seo_default_keywords', '');

        $facts = trim(implode("\n", array_filter([
            "School Name: {$schoolName}",
            "Location: {$schoolAddress}",
            "Locality: {$schoolLocality}",
            "Region: {$schoolRegion}",
            "Page Name: {$formattedPageName}",
            "Page Specific Data: {$dynamicContext}",
            "Existing Target Keywords: {$schoolKeywords}",
        ])));

        $messages = [
            [
                'role' => 'system',
                'content' => "You are an elite SEO expert for educational institutions in Nepal. Return ONLY valid JSON."
            ],
            [
                'role' => 'user',
                'content' => 
                    "Generate a highly optimized SEO pack for a specific web page of {$schoolName}.\n\n" .
                    "CRITICAL RULES:\n" .
                    "1. meta_title: 50-60 characters. Make it unique to the Page Name and include the school name or the most relevant locality/region from the facts.\n" .
                    "2. meta_description: 150-160 characters. Write a compelling, natural sentence specific to the Page Specific Data and include a call to action.\n" .
                    "3. meta_keywords: Provide 15 to 20 comma-separated keywords. Mix high-volume school terms with long-tail local terms specific to the configured school and page.\n" .
                    "4. Do not mention another school, another domain, or a location that is not present in the facts.\n" .
                    "5. Return ONLY a JSON object with keys: meta_title, meta_description, meta_keywords.\n\n" .
                    "FACTS TO USE:\n{$facts}"
            ],
        ];

        // 2. Make the HTTP request via OpenRouter (OpenAI-compatible endpoint)
        $model = config('services.openai.model', 'openai/gpt-4o-mini');
        if (! str_contains($model, '/')) {
            $model = 'openai/'.$model;
        }

        $response = Http::withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title'      => config('app.name'),
            ])
            ->timeout(45)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'           => $model,
                'response_format' => ['type' => 'json_object'],
                'messages'        => $messages,
                'temperature'     => 0.4,
            ]);

        // 3. Catch API errors
        if ($response->failed()) {
            $errorDetails = $response->json('error.message') ?? $response->body();
            throw new \Exception("OpenRouter API Error: " . $errorDetails);
        }

        $text = $response->json('choices.0.message.content');
        $data = json_decode($text, true);

        // 4. Ensure the response is in the right format
        if (!is_array($data) || empty($data['meta_title'])) {
            throw new \Exception("API returned invalid data format. Please try again.");
        }

        return [
            'meta_title'       => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'meta_keywords'    => $data['meta_keywords'],
        ];
    }
}
