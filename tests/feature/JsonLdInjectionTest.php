<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BoardModel;
use App\Models\PostModel;
use Tests\Support\FeatureTestCase;

/**
 * JSON-LD 레이아웃 주입 (#203).
 *
 * @internal
 */
final class JsonLdInjectionTest extends FeatureTestCase
{
    private array $adminSession = [
        'user_id'       => 1,
        'user_nickname' => '관리자',
        'user_role'     => 'admin',
    ];

    public function testHomeHasOrganizationAndWebsite(): void
    {
        $body = $this->get('/')->getBody();

        $this->assertStringContainsString('application/ld+json', $body);
        $this->assertStringContainsString('"@type":"WebSite"', $body);
        $this->assertStringContainsString('#organization', $body);
    }

    public function testDynamicPageHasWebPage(): void
    {
        // 시드 페이지 'about'
        $body = $this->get('about')->getBody();

        $this->assertStringContainsString('"@type":"WebPage"', $body);
    }

    public function testPublicPostHasBlogPostingAndBreadcrumb(): void
    {
        $boardId = (int) (new BoardModel())->getBySlug('notice')['id'];
        $postId  = (new PostModel())->insert([
            'board_id'    => $boardId,
            'user_id'     => 1,
            'title'       => '공개 공지',
            'content'     => '<p>본문</p>',
            'author_name' => '관리자',
            'is_secret'   => 0,
        ], true);

        $body = $this->get('board/notice/' . $postId)->getBody();

        $this->assertStringContainsString('"@type":"BlogPosting"', $body);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $body);
    }

    public function testSecretPostOmitsBlogPosting(): void
    {
        $boardId = (int) (new BoardModel())->getBySlug('notice')['id'];
        $postId  = (new PostModel())->insert([
            'board_id'    => $boardId,
            'user_id'     => 1,
            'title'       => '비밀 공지',
            'content'     => '비밀',
            'author_name' => '관리자',
            'is_secret'   => 1,
        ], true);

        $body = $this->withSession($this->adminSession)->get('board/notice/' . $postId)->getBody();

        // 색인 제외 글: 페이지별 그래프 없음 (단, 전역 Organization 은 존재)
        $this->assertStringNotContainsString('"@type":"BlogPosting"', $body);
        $this->assertStringContainsString('#organization', $body);
    }

    public function testKoreanEncodedAsAscii(): void
    {
        // 사이트명이 한글이어도 JSON-LD 는 \uXXXX 로 (테스트 하네스 엔티티 변환 우회)
        $body = $this->get('/')->getBody();

        $script = strstr($body, 'application/ld+json') ?: '';
        $this->assertStringContainsString('\u', $script);
    }
}
