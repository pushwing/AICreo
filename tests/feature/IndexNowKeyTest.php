<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\FeatureTestCase;

/**
 * IndexNow 키 검증 파일 GET /indexnow-key.txt (#205).
 *
 * @internal
 */
final class IndexNowKeyTest extends FeatureTestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['INDEXNOW_KEY']);
        parent::tearDown();
    }

    public function testKeyFileIs404WithoutKey(): void
    {
        unset($_ENV['INDEXNOW_KEY']);

        $this->expectException(PageNotFoundException::class);
        $this->get('indexnow-key.txt');
    }

    public function testKeyFileReturnsConfiguredKey(): void
    {
        $_ENV['INDEXNOW_KEY'] = 'abc123def456';

        $result = $this->get('indexnow-key.txt');

        $result->assertStatus(200);
        $this->assertStringContainsString('text/plain', $result->response()->getHeaderLine('Content-Type'));
        // (테스트 하네스가 plain-text 를 HTML 로 감싸므로 부분 일치로 검증 — 운영은 raw)
        $this->assertStringContainsString('abc123def456', $result->getBody());
    }
}
