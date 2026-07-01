<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PageModel;
use Tests\Support\FeatureTestCase;

/**
 * @internal
 */
final class SitemapControllerTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cache()->delete('seo_sitemap');
    }

    public function testSitemapReturnsXml(): void
    {
        $result = $this->get('sitemap.xml');

        $result->assertStatus(200);
        $this->assertStringContainsString('application/xml', $result->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<urlset', $result->getBody());
        $this->assertStringContainsString('<loc>', $result->getBody());
    }

    public function testSitemapUsesAbsoluteUrls(): void
    {
        $body = $this->get('sitemap.xml')->getBody();

        // 홈 URL 이 base_url() 절대경로로 포함
        $this->assertStringContainsString('<loc>' . rtrim(base_url('/'), '/'), $body);
        $this->assertStringNotContainsString('<loc>/', $body);
    }

    public function testSitemapIncludesPublishedPage(): void
    {
        (new PageModel())->insert([
            'slug'   => 'sitemap-visible',
            'title'  => '노출 페이지',
            'status' => 'published',
        ]);
        cache()->delete('seo_sitemap');

        $body = $this->get('sitemap.xml')->getBody();

        $this->assertStringContainsString(base_url('sitemap-visible'), $body);
    }

    public function testSitemapExcludesDraftPage(): void
    {
        (new PageModel())->insert([
            'slug'   => 'sitemap-draft',
            'title'  => '초안 페이지',
            'status' => 'draft',
        ]);
        cache()->delete('seo_sitemap');

        $body = $this->get('sitemap.xml')->getBody();

        $this->assertStringNotContainsString('sitemap-draft', $body);
    }
}
