<?php

namespace App\Libraries\AiProvider;

class ClaudeProvider implements AiProviderInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-haiku-4-5-20251001';

    private string $apiKey;

    public function __construct()
    {
        $settings     = model('SettingModel')->getAllAsMap();
        $this->apiKey = ($settings['anthropic_api_key'] ?? '') ?: env('ANTHROPIC_API_KEY', '');
    }

    public function suggestCategories(string $name, string $description, array $tree): array
    {
        $list = $this->flattenTree($tree);
        $desc = mb_substr(strip_tags($description), 0, 500);

        $systemPrompt = AiPrompts::render('category', ['categories' => $list]);

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

    public function generateDescription(string $name, string $description): string
    {
        $cleanDesc = mb_substr(strip_tags($description), 0, 1000);

        $systemPrompt = AiPrompts::get('description');

        $payload = json_encode([
            'model'      => self::MODEL,
            'max_tokens' => 800,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => "상품명: {$name}\n기존 설명 참고: {$cleanDesc}"],
            ],
        ]);

        $raw = $this->callApi($payload, 30);
        if ($raw === false) {
            return '';
        }

        $data = json_decode($raw, true);
        $text = $data['content'][0]['text'] ?? '';
        return $text !== '' ? $this->convertToHtml($text) : '';
    }

    private function convertToHtml(string $text): string
    {
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/u',     '<strong>$1</strong>', $text);

        $lines     = explode("\n", $text);
        $result    = [];
        $listItems = [];

        $flushList = static function (array &$items, array &$out): void {
            if ($items !== []) {
                $out[]  = '<ul>' . implode('', array_map(fn ($i) => "<li>{$i}</li>", $items)) . '</ul>';
                $items  = [];
            }
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') { $flushList($listItems, $result); continue; }

            if (preg_match('/^#{1,3}\s+(.+)/u', $line, $m)) {
                $flushList($listItems, $result);
                $result[] = '<p><strong>' . $m[1] . '</strong></p>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)/u', $line, $m)) {
                $listItems[] = $m[1];
                continue;
            }

            $flushList($listItems, $result);

            if (preg_match('/<(p|ul|li|strong|br)[^>]*>/i', $line)) {
                $result[] = $line;
            } else {
                $result[] = "<p>{$line}</p>";
            }
        }

        $flushList($listItems, $result);

        return implode("\n", $result);
    }

    public function generateQnaAnswer(string $productName, string $productDescription, string $questionTitle, string $questionContent): string
    {
        $cleanDesc = mb_substr(strip_tags($productDescription), 0, 500);

        $systemPrompt = AiPrompts::get('qna');

        $payload = json_encode([
            'model'      => self::MODEL,
            'max_tokens' => 400,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => "상품명: {$productName}\n상품 설명: {$cleanDesc}\n\n문의 제목: {$questionTitle}\n문의 내용: {$questionContent}"],
            ],
        ]);

        $raw = $this->callApi($payload, 20);
        if ($raw === false) {
            return '';
        }

        $data = json_decode($raw, true);
        return $data['content'][0]['text'] ?? '';
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
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200 && $response !== false) ? $response : false;
    }
}
