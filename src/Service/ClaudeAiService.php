<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClaudeAiService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly string $model = 'anthropic/claude-sonnet-4-5',
    ) {}

    public function suggestReward(string $businessName, string $businessType, string $businessDescription): array
    {
        $prompt = <<<PROMPT
You are a loyalty program expert. Suggest one compelling reward for a loyalty stamp card for the following business.

Business Name: {$businessName}
Business Type: {$businessType}
Business Description: {$businessDescription}

Respond ONLY with a valid JSON object (no markdown, no extra text) with these exact keys:
- title: short reward title (max 60 chars)
- description: engaging reward description (max 200 chars)
- rewardType: one of DISCOUNT, FREE_ITEM, CASHBACK, EXPERIENCE
- value: numeric value (e.g. 10 for 10% off or 100 for ₹100 cashback)
- stampRequirement: integer between 5 and 20 (stamps needed to earn the reward)
- terms: short terms and conditions (max 100 chars)
PROMPT;

        $content = $this->call($prompt, 300);

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('AI returned invalid JSON for reward suggestion.');
        }

        return $decoded;
    }

    public function generateBusinessDescription(string $businessName, string $businessType, string $city): string
    {
        $prompt = <<<PROMPT
Write a short, friendly business description for the following business profile on a loyalty rewards app.
Keep it under 150 words. Do not use bullet points. Write in second person ("Your business...") or third person.

Business Name: {$businessName}
Business Type: {$businessType}
City: {$city}

Respond with only the description text, no extra commentary.
PROMPT;

        return trim($this->call($prompt, 200));
    }

    public function getAnalyticsInsights(array $stats): string
    {
        $statsJson = json_encode($stats, JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a business analytics assistant for a digital loyalty platform. Analyze these merchant dashboard stats and provide 3–5 concise, actionable insights in plain language.

Stats:
{$statsJson}

Write insights as short bullet points starting with "•". Be specific, practical, and encouraging. No markdown headers.
PROMPT;

        return trim($this->call($prompt, 400));
    }

    public function getRewardRecommendations(array $stampCards, array $availableRewards, string $tier): array
    {
        $cardsJson = json_encode($stampCards, JSON_PRETTY_PRINT);
        $rewardsJson = json_encode($availableRewards, JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a loyalty rewards advisor. A customer with tier "{$tier}" has the following stamp cards and available rewards.

Stamp Cards (progress):
{$cardsJson}

Available Rewards:
{$rewardsJson}

Pick up to 3 rewards the customer should focus on. Respond ONLY with a valid JSON array (no markdown) of objects with:
- rewardId: the reward's id field
- reason: one short sentence explaining why this reward is a great pick (max 80 chars)

Order by best opportunity first.
PROMPT;

        $content = $this->call($prompt, 300);

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('AI returned invalid JSON for recommendations.');
        }

        return $decoded;
    }

    private function call(string $userPrompt, int $maxTokens = 512): string
    {
        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'content-type'  => 'application/json',
            ],
            'json' => [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'messages'   => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $body = $response->toArray(false);
            $message = $body['error']['message'] ?? 'Unknown API error';
            throw new \RuntimeException("AI API error ({$statusCode}): {$message}");
        }

        $data = $response->toArray();

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
