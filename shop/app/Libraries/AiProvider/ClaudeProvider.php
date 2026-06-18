<?php

namespace App\Libraries\AiProvider;

class ClaudeProvider implements AiProviderInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-haiku-4-5-20251001';

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('ANTHROPIC_API_KEY', '');
    }

    public function suggestCategories(string $name, string $description, array $tree): array
    {
        $list = $this->flattenTree($tree);
        $desc = mb_substr(strip_tags($description), 0, 500);

        $systemPrompt = <<<PROMPT
당신은 쇼핑몰 상품 카테고리 분류 전문가입니다.
아래 카테고리 목록을 참고하여 상품에 가장 적합한 카테고리를 최대 3개 추천하세요.

카테고리 목록 (id: 이름):
{$list}

반드시 JSON 형식으로만 응답하세요: {"category_ids": [1, 2]}
카테고리 목록에 없는 ID는 절대 포함하지 마세요.
PROMPT;

        $payload = json_encode([
            'model'      => self::MODEL,
            'max_tokens' => 128,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => "상품명: {$name}\n상품 설명: {$desc}"],
            ],
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
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

    private function flattenTree(array $tree): string
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

    private function parseResponse(string $raw): array
    {
        $data    = json_decode($raw, true);
        $content = $data['content'][0]['text'] ?? '';
        // JSON 블록만 추출
        if (preg_match('/\{.*\}/s', $content, $m)) {
            $content = $m[0];
        }
        $parsed = json_decode($content, true);
        $ids    = $parsed['category_ids'] ?? [];
        return array_values(array_filter(array_map('intval', (array) $ids)));
    }
}
