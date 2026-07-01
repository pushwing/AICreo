<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SettingModel;
use Tests\Support\FeatureTestCase;

/**
 * @internal
 */
final class RobotsControllerTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cache()->delete('seo_robots');
    }

    public function testRobotsReturnsPlainText(): void
    {
        $result = $this->get('robots.txt');

        $result->assertStatus(200);
        $this->assertStringContainsString('text/plain', $result->response()->getHeaderLine('Content-Type'));
    }

    public function testRobotsBlocksAdminAndAuth(): void
    {
        $body = $this->get('robots.txt')->getBody();

        $this->assertStringContainsString('Disallow: /admin/', $body);
        $this->assertStringContainsString('Disallow: /auth/', $body);
    }

    public function testRobotsIncludesAbsoluteSitemap(): void
    {
        $body = $this->get('robots.txt')->getBody();

        $this->assertStringContainsString('Sitemap: ' . base_url('sitemap.xml'), $body);
    }

    public function testRobotsAllowsAiCrawlersByDefault(): void
    {
        $body = $this->get('robots.txt')->getBody();

        $this->assertStringContainsString('User-agent: GPTBot', $body);
        $this->assertStringContainsString('User-agent: ClaudeBot', $body);
        $this->assertStringNotContainsString("Disallow: /\n", ltrim(strstr($body, 'GPTBot') ?: ''));
    }

    public function testRobotsBlocksAiCrawlersWhenDisabled(): void
    {
        (new SettingModel())->saveSettings(['ai_crawlers_allow' => '0']);
        cache()->delete('seo_robots');

        $body = $this->get('robots.txt')->getBody();

        // AI 크롤러 섹션에서 GPTBot 뒤에 Disallow: / 가 나와야 함
        $section = strstr($body, 'User-agent: GPTBot') ?: '';
        $this->assertStringContainsString('Disallow: /', $section);
    }
}
