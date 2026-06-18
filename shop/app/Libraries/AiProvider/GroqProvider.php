<?php

namespace App\Libraries\AiProvider;

class GroqProvider implements AiProviderInterface
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL   = 'llama-3.1-8b-instant';

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GROQ_API_KEY', '');
    }

    public function suggestCategories(string $name, string $description, array $tree): array
    {
        $prompt = $this->buildPrompt($name, $description, $tree);

        $payload = json_encode([
            'model'       => self::MODEL,
            'temperature' => 0.1,
            'max_tokens'  => 128,
            'messages'    => [
                ['role' => 'system', 'content' => $this->systemPrompt($tree)],
                ['role' => 'user',   'content' => $prompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return [];
        }

        return $this->parseResponse($response);
    }

    protected function systemPrompt(array $tree): string
    {
        $list = $this->flattenTree($tree);
        return <<<PROMPT
당신은 쇼핑몰 상품 카테고리 분류 전문가입니다.
아래 카테고리 목록을 참고하여 상품에 가장 적합한 카테고리를 최대 3개 추천하세요.

카테고리 목록 (id: 이름):
{$list}

반드시 JSON 형식으로만 응답하세요: {"category_ids": [1, 2]}
카테고리 목록에 없는 ID는 절대 포함하지 마세요.
PROMPT;
    }

    protected function buildPrompt(string $name, string $description, array $tree): string
    {
        $desc = mb_substr(strip_tags($description), 0, 500);
        return "상품명: {$name}\n상품 설명: {$desc}";
    }

    protected function flattenTree(array $tree): string
    {
        $lines = [];
        foreach ($tree as $parent) {
            $lines[] = "- {$parent['id']}: {$parent['name']}";
            foreach ($parent['children'] as $child) {
                $lines[] = "  - {$child['id']}: {$child['name']}";
            }
        }
        return implode("\n", $lines);
    }

    protected function parseResponse(string $raw): array
    {
        $data = json_decode($raw, true);
        $content = $data['choices'][0]['message']['content'] ?? '';
        $parsed  = json_decode($content, true);
        $ids     = $parsed['category_ids'] ?? [];
        return array_values(array_filter(array_map('intval', (array) $ids)));
    }

    public function generateDescription(string $name, string $description): string
    {
        $cleanDesc = mb_substr(strip_tags($description), 0, 1000);

        $payload = json_encode([
            'model'       => self::MODEL,
            'temperature' => 0.7,
            'max_tokens'  => 800,
            'messages'    => [
                ['role' => 'system', 'content' => $this->descriptionSystemPrompt()],
                ['role' => 'user',   'content' => "상품명: {$name}\n기존 설명 참고: {$cleanDesc}"],
            ],
        ]);

        $raw = $this->callApi($payload, 30);
        if ($raw === false) {
            return '';
        }

        $data = json_decode($raw, true);
        return $data['choices'][0]['message']['content'] ?? '';
    }

    protected function descriptionSystemPrompt(): string
    {
        return <<<PROMPT
당신은 쇼핑몰 상품 설명 작성 전문가입니다.
상품명과 기존 설명을 참고하여 고객의 구매욕을 자극하는 매력적인 상품 설명을 한국어로 작성하세요.

규칙:
- HTML 형식으로 작성하되 <p>, <strong>, <ul>, <li>, <br> 태그만 사용
- 상품의 특징과 장점을 명확하게 강조
- 자연스럽고 설득력 있는 문체 사용
- 300~500자 내외로 간결하게 작성
- 마크다운(**, ##, ``` 등)은 절대 사용하지 마세요
PROMPT;
    }

    protected function callApi(string $payload, int $timeout = 15): string|false
    {
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200 && $response !== false) ? $response : false;
    }
}
