<?php

declare(strict_types=1);

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 동적 robots.txt — Sitemap 절대경로·관리자/인증 차단·AI 크롤러 정책.
 * AI 크롤러 허용 여부는 settings.ai_crawlers_allow(기본 허용) 로 제어.
 * 결과는 seo_robots 로 캐시(설정 저장 시 SettingModel 이 무효화).
 */
class RobotsController extends BaseController
{
    private const CACHE_KEY = 'seo_robots';
    private const CACHE_TTL = 3600;

    /**
     * AI 검색·학습 크롤러 (전략문서 §6)
     */
    private const AI_CRAWLERS = [
        'GPTBot',          // OpenAI
        'OAI-SearchBot',   // ChatGPT Search
        'PerplexityBot',
        'Google-Extended', // Gemini / AI Overviews
        'ClaudeBot',
        'Bingbot',         // Bing (ChatGPT Search 전제)
    ];

    public function index(): ResponseInterface
    {
        $body = cache()->remember(self::CACHE_KEY, self::CACHE_TTL, fn (): string => $this->build());

        return $this->response
            ->setContentType('text/plain')
            ->setBody($body);
    }

    private function build(): string
    {
        $settings = (new SettingModel())->getAllAsMap();
        $aiAllow  = ($settings['ai_crawlers_allow'] ?? '1') !== '0';

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /auth/',
            '',
        ];

        // AI 검색/학습 크롤러 정책
        $lines[] = $aiAllow
            ? '# AI 검색/학습 크롤러 — 허용 (노출 극대화)'
            : '# AI 검색/학습 크롤러 — 차단';

        foreach (self::AI_CRAWLERS as $bot) {
            $lines[] = 'User-agent: ' . $bot;
            $lines[] = $aiAllow ? 'Allow: /' : 'Disallow: /';
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . base_url('sitemap.xml');

        return implode("\n", $lines) . "\n";
    }
}
